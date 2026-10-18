<?php

namespace App\Services\Storefront;

use App\Actions\Order\PlaceOrderAction;
use App\Models\DeliveryMethod;
use App\Models\DeliveryTimeSlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Order\OrderAllocationEngine;
use App\Services\Order\ShippingCalculator;
use App\Support\BranchProductPriceDisplay;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /** @var Collection<int, DeliveryMethod>|null */
    private ?Collection $activeDeliveryMethods = null;

    public function __construct(
        private readonly CartService $carts,
        private readonly CouponService $coupons,
        private readonly PaymentGatewaySettingsService $payments,
        private readonly OrderAllocationEngine $allocator,
        private readonly ShippingCalculator $shipping,
        private readonly PlaceOrderAction $placeOrder,
    ) {}

    /**
     * @return list<array{id: string, label: string, description: string, requiresSlot: bool, timeSlots: list<array{id: string, label: string}>}>
     */
    public function deliveryMethods(): array
    {
        return $this->activeDeliveryMethods()
            ->map(fn (DeliveryMethod $method): array => [
                'id' => $method->key,
                'label' => $method->label,
                'description' => $method->description ?? '',
                'requiresSlot' => $method->requires_slot,
                'timeSlots' => $method->timeSlots
                    ->map(fn (DeliveryTimeSlot $slot): array => [
                        'id' => (string) $slot->id,
                        'label' => $slot->label,
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function timeSlots(?string $methodKey = null): array
    {
        $methods = $this->activeDeliveryMethods();

        if (is_string($methodKey) && $methodKey !== '') {
            $methods = $methods->where('key', $methodKey);
        }

        return $methods
            ->flatMap(fn (DeliveryMethod $method) => $method->timeSlots)
            ->unique('id')
            ->sortBy([
                ['sort', 'asc'],
                ['id', 'asc'],
            ])
            ->map(fn (DeliveryTimeSlot $slot): array => [
                'id' => (string) $slot->id,
                'label' => $slot->label,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, DeliveryMethod>
     */
    private function activeDeliveryMethods(): Collection
    {
        return $this->activeDeliveryMethods ??= DeliveryMethod::query()
            ->where('status', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->with(['timeSlots' => fn ($query) => $query->where('status', true)->orderBy('sort')->orderBy('id')])
            ->get();
    }

    /**
     * Preview the distance-based delivery fee for the current cart.
     *
     * @return array{fee: float, formattedFee: string}
     */
    public function quoteShipping(Request $request, float $latitude, float $longitude): array
    {
        $cart = $this->carts->activeCart($request)->load(['items.product.media']);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $plan = $this->allocator->allocate(
            $latitude,
            $longitude,
            $cart->items,
            $request->input('delivery_method'),
        );
        $fee = round($this->shipping->cost($plan['branch'], $plan['distance_km']), 2);

        return [
            'fee' => $fee,
            'formattedFee' => '$'.number_format($fee, 2),
        ];
    }

    /**
     * @return array{
     *     orderNumber: string,
     *     itemCount: int,
     *     paymentMethod: string,
     *     deliveryMethod: string,
     *     deliveryDate: string|null,
     *     timeSlot: string|null,
     *     name: string,
     *     mobile: string,
     *     address: string,
     *     city: string|null,
     *     postalCode: string|null,
     *     couponCode: string|null,
     *     items: list<array{name: string, image: string, quantity: int, formattedLineTotal: string}>,
     *     subtotal: string,
     *     discount: string|null,
     *     deliveryFee: string,
     *     total: string,
     *     viewOrderUrl: string
     * }
     */
    public function confirmation(Order $order): array
    {
        $order->loadMissing('items');

        $snapshot = $order->shipping_address_snapshot ?? [];
        $delivery = $this->activeDeliveryMethods()->firstWhere('key', $order->delivery_method);

        return [
            'orderNumber' => $order->order_number,
            'itemCount' => (int) $order->items->sum('quantity'),
            'paymentMethod' => $this->payments->labelFor((string) $order->payment_method, $order->payment_method),
            'deliveryMethod' => $delivery?->label ?? (string) $order->delivery_method,
            'deliveryDate' => $order->delivery_date?->format('d M Y'),
            'timeSlot' => $order->time_slot,
            'name' => $snapshot['name'] ?? '',
            'mobile' => $snapshot['phone'] ?? '',
            'address' => $snapshot['address'] ?? '',
            'city' => $snapshot['city'] ?? null,
            'postalCode' => $snapshot['postal_code'] ?? null,
            'couponCode' => $order->coupon_code,
            'items' => $order->items
                ->map(fn (OrderItem $item): array => [
                    'name' => $item->product_name_snapshot,
                    'image' => MediaUrl::resolve($item->product_snapshot['image'] ?? null),
                    'quantity' => $item->quantity,
                    'formattedLineTotal' => '$'.number_format((float) $item->subtotal, 2),
                ])
                ->values()
                ->all(),
            'subtotal' => '$'.number_format((float) $order->subtotal, 2),
            'discount' => (float) $order->discount > 0
                ? '-$'.number_format((float) $order->discount, 2)
                : null,
            'deliveryFee' => '$'.number_format((float) $order->delivery_fee, 2),
            'total' => '$'.number_format((float) $order->total_amount, 2),
            'viewOrderUrl' => route('customer.orders.show', $order),
        ];
    }

    /**
     * @param  array{
     *     name: string,
     *     mobile: string,
     *     address: string,
     *     city?: string|null,
     *     postal_code?: string|null,
     *     latitude: float|string,
     *     longitude: float|string,
     *     save_as_default?: bool,
     *     delivery_method: string,
     *     delivery_date?: string|null,
     *     time_slot_id?: string|null,
     *     payment_method: string,
     *     coupon?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function place(Request $request, User $user, array $data): Order
    {
        $cartPayload = $this->carts->payload($request);

        if ($cartPayload['items'] === []) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $method = DeliveryMethod::query()
            ->where('key', $data['delivery_method'])
            ->where('status', true)
            ->first();

        if ($method === null) {
            throw ValidationException::withMessages([
                'delivery_method' => 'Choose a delivery method.',
            ]);
        }

        $slotLabel = null;
        $deliveryDate = null;

        if ($method->requires_slot) {
            $slot = DeliveryTimeSlot::query()
                ->where('id', $data['time_slot_id'] ?? null)
                ->where('delivery_method_id', $method->id)
                ->where('status', true)
                ->first();

            if ($slot === null) {
                throw ValidationException::withMessages([
                    'time_slot_id' => 'Choose a time slot.',
                ]);
            }

            $slotLabel = $slot->label;
            $deliveryDate = now()->toDateString();
        }

        $enabledKeys = collect($this->payments->enabledMethods())->pluck('id')->all();

        if (! in_array($data['payment_method'], $enabledKeys, true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Choose an available payment method.',
            ]);
        }

        $cart = $this->carts->activeCart($request)->load(['items.product.media']);

        foreach ($cart->items as $item) {
            $product = $item->product;

            if ($product === null || ! $product->status) {
                throw ValidationException::withMessages([
                    'cart' => 'One or more items are no longer available.',
                ]);
            }

            if (BranchProductPriceDisplay::catalogEffectiveAmount($product) <= 0) {
                throw ValidationException::withMessages([
                    'cart' => 'One or more items cannot be purchased at this time.',
                ]);
            }
        }

        $subtotal = (float) $cartPayload['subtotal'];
        $coupon = null;
        $discount = 0.0;

        if (filled($data['coupon'] ?? null)) {
            $quoted = $this->coupons->quote((string) $data['coupon'], $subtotal, $user);
            $coupon = $quoted['coupon'];
            $discount = $quoted['discount'];
        }

        return $this->placeOrder->handle($user, $cart, [
            ...$data,
            'delivery_date' => $deliveryDate,
            'time_slot' => $slotLabel,
            'coupon' => $coupon,
            'discount' => $discount,
        ]);
    }
}
