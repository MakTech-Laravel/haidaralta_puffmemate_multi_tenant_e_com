<?php

namespace App\Services\Storefront;

use App\Enums\CartStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\DeliveryMethod;
use App\Models\DeliveryTimeSlot;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Order\BranchAllocationService;
use App\Services\Order\ShippingCalculator;
use App\Support\BranchProductPriceDisplay;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /** @var Collection<int, DeliveryMethod>|null */
    private ?Collection $activeDeliveryMethods = null;

    public function __construct(
        private readonly CartService $carts,
        private readonly CouponService $coupons,
        private readonly PaymentGatewaySettingsService $payments,
        private readonly BranchAllocationService $allocator,
        private readonly ShippingCalculator $shipping,
    ) {}

    /**
     * @return list<array{id: int, type: string, receiver_name: string, phone: string, address: string, city: string|null, postal_code: string|null, latitude: string|null, longitude: string|null, is_default: bool}>
     */
    public function addressesFor(User $user): array
    {
        return Address::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Address $address): array => [
                'id' => $address->id,
                'type' => $address->type,
                'receiver_name' => $address->receiver_name,
                'phone' => $address->phone,
                'address' => $address->address,
                'city' => $address->city,
                'postal_code' => $address->postal_code,
                'latitude' => $address->latitude === null ? null : (string) $address->latitude,
                'longitude' => $address->longitude === null ? null : (string) $address->longitude,
                'is_default' => $address->is_default,
            ])
            ->all();
    }

    /**
     * @return list<array{id: string, label: string, description: string, fee: float, formattedFee: string, requiresSlot: bool}>
     */
    public function deliveryMethods(): array
    {
        return $this->activeDeliveryMethods()
            ->map(fn (DeliveryMethod $method): array => [
                'id' => $method->key,
                'label' => $method->label,
                'description' => $method->description ?? '',
                'fee' => (float) $method->fee,
                'formattedFee' => ((float) $method->fee) <= 0 ? 'FREE' : '$'.number_format((float) $method->fee, 2),
                'requiresSlot' => $method->requires_slot,
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
     * @param  array{
     *     first_name: string,
     *     last_name: string,
     *     mobile: string,
     *     address: string,
     *     city?: string|null,
     *     postal_code?: string|null,
     *     latitude: float|string,
     *     longitude: float|string,
     *     address_id?: int|null,
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

        if ($method->requires_slot) {
            $slot = DeliveryTimeSlot::query()
                ->where('id', $data['time_slot_id'] ?? null)
                ->where('delivery_method_id', $method->id)
                ->where('status', true)
                ->first();

            if ($slot === null || blank($data['delivery_date'] ?? null)) {
                throw ValidationException::withMessages([
                    'time_slot_id' => 'Choose a delivery date and time slot.',
                ]);
            }

            $slotLabel = $slot->label;
        }

        $enabledKeys = collect($this->payments->enabledMethods())->pluck('id')->all();

        if (! in_array($data['payment_method'], $enabledKeys, true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Choose an available payment method.',
            ]);
        }

        $cart = $this->carts->activeCart($request)->load('items.product');

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

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        $allocations = $this->allocator->allocate($latitude, $longitude, $cart->items);

        $subtotal = (float) $cartPayload['subtotal'];
        $discount = 0.0;
        $coupon = null;

        if (filled($data['coupon'] ?? null)) {
            $quoted = $this->coupons->quote((string) $data['coupon'], $subtotal, $user);
            $coupon = $quoted['coupon'];
            $discount = $quoted['discount'];
        }

        $deliveryFee = 0.0;

        foreach ($allocations as $group) {
            $deliveryFee += $this->shipping->cost($group['branch'], $group['distance_km']);
        }

        $deliveryFee = round($deliveryFee, 2);
        $total = round(max($subtotal - $discount + $deliveryFee, 0), 2);

        return DB::transaction(function () use (
            $user,
            $data,
            $cart,
            $method,
            $slotLabel,
            $coupon,
            $subtotal,
            $discount,
            $deliveryFee,
            $total,
            $allocations,
            $latitude,
            $longitude,
        ): Order {
            $address = $this->persistAddress($user, $data);

            $order = Order::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'customer_id' => $user->id,
                'address_id' => $address->id,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'status' => OrderStatus::Pending,
                'shipping_address_snapshot' => [
                    'name' => trim($data['first_name'].' '.$data['last_name']),
                    'phone' => $data['mobile'],
                    'address' => $data['address'],
                    'city' => $data['city'] ?? null,
                    'postal_code' => $data['postal_code'] ?? null,
                ],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_fee' => $deliveryFee,
                'tax' => 0,
                'total_amount' => $total,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_method' => $data['payment_method'],
                'delivery_method' => $method->key,
                'delivery_date' => $method->requires_slot ? ($data['delivery_date'] ?? null) : null,
                'time_slot' => $slotLabel,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($allocations as $index => $group) {
                $fulfillment = OrderFulfillment::query()->create([
                    'order_id' => $order->id,
                    'branch_id' => $group['branch']->id,
                    'fulfillment_number' => $order->order_number.'-'.chr(65 + $index),
                    'status' => FulfillmentStatus::Pending,
                    'distance_km' => $group['distance_km'],
                    'shipping_cost' => $this->shipping->cost($group['branch'], $group['distance_km']),
                ]);

                foreach ($group['lines'] as $line) {
                    $item = $cart->items->first(
                        fn ($cartItem): bool => $cartItem->product_id === $line['product_id']
                            && $cartItem->variant_id === $line['variant_id'],
                    );
                    $product = $item?->product;

                    if ($product === null) {
                        continue;
                    }

                    $unit = BranchProductPriceDisplay::catalogEffectiveAmount($product);

                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'fulfillment_id' => $fulfillment->id,
                        'product_id' => $product->id,
                        'variant_id' => $line['variant_id'],
                        'product_name_snapshot' => $product->name,
                        'sku_snapshot' => $product->sku,
                        'product_snapshot' => $this->productSnapshot($product),
                        'quantity' => $line['quantity'],
                        'unit_price' => $unit,
                        'subtotal' => $unit * $line['quantity'],
                    ]);
                }

                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'fulfillment_id' => $fulfillment->id,
                    'old_status' => null,
                    'new_status' => FulfillmentStatus::Pending->value,
                    'comment' => 'Fulfillment created',
                    'changed_by' => $user->id,
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'fulfillment_id' => null,
                'old_status' => null,
                'new_status' => OrderStatus::Pending->value,
                'comment' => 'Order placed',
                'changed_by' => $user->id,
            ]);

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'gateway' => $data['payment_method'],
                'amount' => $total,
                'currency' => 'AUD',
                'status' => PaymentStatus::Unpaid,
            ]);

            PaymentTransaction::query()->create([
                'payment_id' => $payment->id,
                'event_type' => 'initiated',
                'response' => ['offline' => true],
            ]);

            if ($coupon instanceof Coupon) {
                CouponRedemption::query()->create([
                    'coupon_id' => $coupon->id,
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'discount_amount' => $discount,
                ]);
            }

            $cart->items()->delete();
            $cart->forceFill(['status' => CartStatus::Converted])->save();

            return $order->refresh();
        });
    }

    /**
     * Full product attributes at time of order, so a later rename/reprice
     * never affects how a historical order renders.
     *
     * @return array<string, mixed>
     */
    private function productSnapshot(Product $product): array
    {
        return [
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'image' => MediaUrl::resolve($product->image),
            'description' => $product->packSummary(),
            'brand' => $product->brand,
            'pack_label' => $product->pack_label,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persistAddress(User $user, array $data): Address
    {
        $saveDefault = (bool) ($data['save_as_default'] ?? false);
        $addressId = isset($data['address_id']) ? (int) $data['address_id'] : null;

        $attributes = [
            'type' => 'home',
            'receiver_name' => trim($data['first_name'].' '.$data['last_name']),
            'phone' => $data['mobile'],
            'address' => $data['address'],
            'city' => $data['city'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_default' => $saveDefault,
        ];

        if ($saveDefault) {
            Address::query()->where('user_id', $user->id)->update(['is_default' => false]);
        }

        if ($addressId) {
            $address = Address::query()
                ->where('user_id', $user->id)
                ->where('id', $addressId)
                ->first();

            if ($address !== null) {
                $address->fill($attributes)->save();

                return $address->refresh();
            }
        }

        return Address::query()->create([
            ...$attributes,
            'user_id' => $user->id,
        ]);
    }

    private function nextOrderNumber(): string
    {
        $prefix = 'PMM-'.now()->format('Y');
        $latest = Order::query()
            ->where('order_number', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('order_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
