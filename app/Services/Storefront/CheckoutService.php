<?php

namespace App\Services\Storefront;

use App\Actions\Order\PlaceOrderAction;
use App\Actions\Order\PlacePackOrderAction;
use App\Models\DeliveryMethod;
use App\Models\DeliveryTimeSlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pack;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Order\Allocation\AreaCheckoutAllocator;
use App\Services\Order\BranchDeliveryQuote;
use App\Services\Order\ShippingCalculator;
use App\Support\BranchProductPriceDisplay;
use App\Support\MediaUrl;
use App\Support\Storefront\PackCheckoutSession;
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
        private readonly AreaCheckoutAllocator $allocator,
        private readonly ShippingCalculator $shipping,
        private readonly BranchDeliveryQuote $branchDelivery,
        private readonly PlaceOrderAction $placeOrder,
        private readonly PlacePackOrderAction $placePackOrder,
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

    public function isPackCheckout(Request $request): bool
    {
        return PackCheckoutSession::resolveActivePack($request) !== null;
    }

    /**
     * Checkout line payload for a pack buy-now session (single summary line).
     *
     * @return array{
     *     itemCount: int,
     *     items: list<array<string, mixed>>,
     *     subtotal: float,
     *     formattedSubtotal: string,
     *     checkoutMode: string,
     *     couponsAllowed: bool
     * }|null
     */
    public function packPayload(Request $request): ?array
    {
        $session = PackCheckoutSession::get($request);

        if ($session === null) {
            return null;
        }

        $pack = PackCheckoutSession::resolveActivePack($request);

        if ($pack === null) {
            return null;
        }

        $quantity = $session['quantity'];
        $unit = round((float) $pack->pack_price, 2);
        $line = round($unit * $quantity, 2);
        $compareAt = round((float) $pack->subtotal, 2);
        $compareAt = $compareAt > $unit ? $compareAt : null;

        return [
            'itemCount' => $quantity,
            'items' => [[
                'id' => 'pack:'.$pack->id,
                'productId' => null,
                'slug' => $pack->slug,
                'name' => $pack->name,
                'category' => 'Packs & Saves',
                'description' => (string) ($pack->description ?? ''),
                'image' => $pack->imageUrl(MediaUrl::DEFAULT_FALLBACK) ?? MediaUrl::DEFAULT_FALLBACK,
                'unitPrice' => $unit,
                'formattedUnitPrice' => '$'.number_format($unit, 2),
                'compareAtUnitPrice' => $compareAt,
                'formattedCompareAtUnitPrice' => $compareAt === null ? null : '$'.number_format($compareAt, 2),
                'quantity' => $quantity,
                'maxQuantity' => $pack->quantity,
                'lineTotal' => $line,
                'formattedLineTotal' => '$'.number_format($line, 2),
            ]],
            'subtotal' => $line,
            'formattedSubtotal' => '$'.number_format($line, 2),
            'checkoutMode' => 'pack',
            'couponsAllowed' => false,
        ];
    }

    /**
     * Preview the distance-based delivery fee for the current cart or pack.
     *
     * @return array{
     *     fee: float,
     *     formattedFee: string,
     *     requiresLateDelivery?: bool,
     *     shortfallLines?: list<array{
     *         productId: int,
     *         variantId: int|null,
     *         productName: string,
     *         quantity: int,
     *         localQuantity: int,
     *         shortfall: int
     *     }>,
     *     distanceKm?: float,
     *     matchedBranchId?: int
     * }
     */
    public function quoteShipping(Request $request, float $latitude, float $longitude): array
    {
        if ($this->isPackCheckout($request)) {
            $resolved = PackCheckoutSession::requireActivePack($request);
            /** @var Pack $pack */
            $pack = $resolved['pack'];
            $pack->loadMissing('branch');
            $branch = $pack->branch;

            if ($branch === null) {
                throw ValidationException::withMessages([
                    'pack' => 'This pack is no longer available.',
                ]);
            }

            $quote = $this->branchDelivery->quote($branch, $latitude, $longitude);
            $fee = round($quote['fee'], 2);

            return [
                'fee' => $fee,
                'formattedFee' => '$'.number_format($fee, 2),
                'requiresLateDelivery' => false,
                'shortfallLines' => [],
            ];
        }

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
            (bool) $request->boolean('accept_late_delivery'),
            false,
        );
        $fee = round($this->shipping->cost($plan['branch'], $plan['distance_km']), 2);

        return [
            'fee' => $fee,
            'formattedFee' => '$'.number_format($fee, 2),
            'requiresLateDelivery' => $plan['requires_late_delivery'],
            'shortfallLines' => collect($plan['lines'])
                ->filter(fn (array $line): bool => $line['shortfall'] > 0)
                ->map(fn (array $line): array => [
                    'productId' => $line['product_id'],
                    'variantId' => $line['variant_id'],
                    'productName' => $line['product_name'],
                    'quantity' => $line['quantity'],
                    'localQuantity' => $line['local_quantity'],
                    'shortfall' => $line['shortfall'],
                ])
                ->values()
                ->all(),
            'distanceKm' => $plan['distance_km'],
            'matchedBranchId' => $plan['branch']->id,
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
     *     items: list<array{name: string, image: string, quantity: int, formattedLineTotal: string, isLateDelivery: bool}>,
     *     hasLateDeliveryItems: bool,
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
                    'isLateDelivery' => (bool) $item->is_late_delivery,
                ])
                ->values()
                ->all(),
            'hasLateDeliveryItems' => $order->items->contains(
                fn (OrderItem $item): bool => (bool) $item->is_late_delivery,
            ),
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
     *     notes?: string|null,
     *     accept_late_delivery?: bool
     * }  $data
     */
    public function place(Request $request, User $user, array $data): Order
    {
        [$slotLabel, $deliveryDate] = $this->resolveDeliverySlot($data);

        $enabledKeys = collect($this->payments->enabledMethods())->pluck('id')->all();

        if (! in_array($data['payment_method'], $enabledKeys, true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Choose an available payment method.',
            ]);
        }

        if ($this->isPackCheckout($request)) {
            if (filled($data['coupon'] ?? null)) {
                throw ValidationException::withMessages([
                    'coupon' => 'Coupons cannot be applied to Packs & Saves orders.',
                ]);
            }

            $resolved = PackCheckoutSession::requireActivePack($request);

            return $this->placePackOrder->handle(
                $user,
                $resolved['pack'],
                $resolved['quantity'],
                [
                    ...$data,
                    'delivery_date' => $deliveryDate,
                    'time_slot' => $slotLabel,
                ],
                $request,
            );
        }

        $cartPayload = $this->carts->payload($request);

        if ($cartPayload['items'] === []) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
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

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveDeliverySlot(array $data): array
    {
        $method = DeliveryMethod::query()
            ->where('key', $data['delivery_method'])
            ->where('status', true)
            ->first();

        if ($method === null) {
            throw ValidationException::withMessages([
                'delivery_method' => 'Choose a delivery method.',
            ]);
        }

        if (! $method->requires_slot) {
            return [null, null];
        }

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

        return [$slot->label, now()->toDateString()];
    }
}
