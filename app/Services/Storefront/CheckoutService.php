<?php

namespace App\Services\Storefront;

use App\Actions\Order\PlaceOrderAction;
use App\Actions\Order\PlacePackOrderAction;
use App\Enums\CustomerRegistrationStatus;
use App\Models\DeliveryMethod;
use App\Models\DeliveryShift;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pack;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Customer\CustomerResolutionService;
use App\Services\Order\Allocation\AreaCheckoutAllocator;
use App\Services\Order\BranchDeliveryQuote;
use App\Services\Order\ShippingCalculator;
use App\Support\Delivery\DeliveryShiftAvailability;
use App\Support\MediaUrl;
use App\Support\OrderItemPackageDisplay;
use App\Support\ProductPackagePricing;
use App\Support\Storefront\PackCheckoutSession;
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
        private readonly AreaCheckoutAllocator $allocator,
        private readonly ShippingCalculator $shipping,
        private readonly BranchDeliveryQuote $branchDelivery,
        private readonly PlaceOrderAction $placeOrder,
        private readonly PlacePackOrderAction $placePackOrder,
        private readonly CustomerResolutionService $customers,
        private readonly DeliveryShiftAvailability $deliveryShifts,
    ) {}

    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     description: string,
     *     requiresSlot: bool,
     *     requiresDate: bool,
     *     minDate: string|null,
     *     maxDate: string|null,
     *     timeSlots: list<array{id: string, label: string, name: string, start_time: string, end_time: string}>
     * }>
     */
    public function deliveryMethods(): array
    {
        $now = $this->deliveryShifts->now();
        $sameDaySlots = $this->deliveryShifts->eligibleForSameDay($now)
            ->map(fn (DeliveryShift $shift): array => $this->deliveryShifts->payloadSlot($shift))
            ->values()
            ->all();
        $allSlots = $this->deliveryShifts->activeShifts()
            ->map(fn (DeliveryShift $shift): array => $this->deliveryShifts->payloadSlot($shift))
            ->values()
            ->all();
        $lateMin = $this->deliveryShifts->lateMinDate($now);
        $lateMax = $this->deliveryShifts->lateMaxDate($now);

        return $this->activeDeliveryMethods()
            ->filter(function (DeliveryMethod $method) use ($sameDaySlots): bool {
                if ($method->key === DeliveryShiftAvailability::METHOD_SAME_DAY) {
                    return $sameDaySlots !== [];
                }

                return true;
            })
            ->map(function (DeliveryMethod $method) use ($sameDaySlots, $allSlots, $lateMin, $lateMax): array {
                $isLate = $method->key === DeliveryShiftAvailability::METHOD_LATE;
                $slots = $isLate ? $allSlots : $sameDaySlots;

                return [
                    'id' => $method->key,
                    'label' => $method->label,
                    'description' => $method->description ?? '',
                    'requiresSlot' => true,
                    'requiresDate' => $isLate,
                    'minDate' => $isLate ? $lateMin : null,
                    'maxDate' => $isLate ? $lateMax : null,
                    'timeSlots' => $slots,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, label: string, name: string, start_time: string, end_time: string}>
     */
    public function timeSlots(?string $methodKey = null): array
    {
        $shifts = $methodKey === DeliveryShiftAvailability::METHOD_SAME_DAY
            ? $this->deliveryShifts->eligibleForSameDay()
            : $this->deliveryShifts->activeShifts();

        return $shifts
            ->map(fn (DeliveryShift $shift): array => $this->deliveryShifts->payloadSlot($shift))
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

        $cart = $this->carts->activeCart($request)->load([
            'items.product.media',
            'items.package',
        ]);

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
                ->map(function (array $line) use ($cart): array {
                    $cartItem = $cart->items->first(
                        fn ($item): bool => $item->product_id === $line['product_id']
                            && $item->variant_id === $line['variant_id']
                            && $item->product_package_id === ($line['product_package_id'] ?? null),
                    );
                    $package = $cartItem?->package;
                    $orderQuantity = (int) ($line['order_quantity'] ?? $cartItem?->quantity ?? $line['quantity']);
                    $packageQty = $package?->quantity;
                    $shortfallPackages = $packageQty !== null && $packageQty > 0
                        ? (int) ceil($line['shortfall'] / $packageQty)
                        : $line['shortfall'];

                    return [
                        'productId' => $line['product_id'],
                        'variantId' => $line['variant_id'],
                        'productPackageId' => $line['product_package_id'] ?? null,
                        'productName' => $line['product_name'],
                        'packageLabel' => $package?->label,
                        'quantity' => $orderQuantity,
                        'inventoryQuantity' => $line['quantity'],
                        'localQuantity' => $line['local_quantity'],
                        'shortfall' => $shortfallPackages,
                        'shortfallUnits' => $line['shortfall'],
                    ];
                })
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
     *     viewOrderUrl: string|null
     * }
     */
    public function confirmation(Order $order, bool $canViewOrder = true): array
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
                    'packageLabel' => OrderItemPackageDisplay::subtitle($item),
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
            'viewOrderUrl' => $canViewOrder ? route('customer.orders.show', $order) : null,
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
    public function place(Request $request, ?User $user, array $data): Order
    {
        [$slotLabel, $deliveryDate, $deliveryShiftId] = $this->resolveDeliverySlot($data);

        $enabledKeys = collect($this->payments->enabledMethods())->pluck('id')->all();

        if (! in_array($data['payment_method'], $enabledKeys, true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Choose an available payment method.',
            ]);
        }

        if ($user === null) {
            $user = $this->resolveGuestCustomer($data);
            $data['save_as_default'] = false;
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
                    'delivery_shift_id' => $deliveryShiftId,
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

        $cart = $this->carts->activeCart($request)->load([
            'items.product.media',
            'items.product.packages',
            'items.package',
        ]);

        foreach ($cart->items as $item) {
            $product = $item->product;

            if ($product === null || ! $product->status) {
                throw ValidationException::withMessages([
                    'cart' => 'One or more items are no longer available.',
                ]);
            }

            try {
                ProductPackagePricing::requirePackageSelection($product, $item->product_package_id);
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    'cart' => $exception->getMessage(),
                ]);
            }

            if (ProductPackagePricing::unitAmountForCartItem($item) <= 0) {
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
            'delivery_shift_id' => $deliveryShiftId,
            'coupon' => $coupon,
            'discount' => $discount,
        ]);
    }

    /**
     * Reuse or create a provisional customer for a guest checkout. A phone that
     * already belongs to a registered account must log in instead.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveGuestCustomer(array $data): User
    {
        $existing = $this->customers->findByPhone((string) $data['mobile']);

        if ($existing !== null && $existing->registration_status === CustomerRegistrationStatus::Registered) {
            throw ValidationException::withMessages([
                'mobile' => 'This number already has an account. Please log in to place your order.',
            ]);
        }

        return DB::transaction(fn (): User => $this->customers->resolve(
            null,
            (string) $data['mobile'],
            (string) $data['name'],
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string, 2: int}
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

        if ($method->key === DeliveryShiftAvailability::METHOD_SAME_DAY
            && $this->deliveryShifts->eligibleForSameDay()->isEmpty()) {
            throw ValidationException::withMessages([
                'delivery_method' => 'Same-day delivery is not available right now.',
            ]);
        }

        [$shift, $label, $deliveryDate] = $this->deliveryShifts->assertSelectable(
            $method->key,
            $data['delivery_date'] ?? null,
            $data['time_slot_id'] ?? null,
        );

        return [$label, $deliveryDate, $shift->id];
    }
}
