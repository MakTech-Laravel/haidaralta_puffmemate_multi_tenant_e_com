<?php

namespace App\Actions\Order;

use App\Enums\CartStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Notifications\NotificationService;
use App\Services\Order\AllocationDecisionRecorder;
use App\Services\Order\OrderAllocationEngine;
use App\Services\Order\OrderPlacementNotifier;
use App\Services\Order\StockTransferService;
use App\Support\BranchProductPriceDisplay;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrderAction
{
    public function __construct(
        private OrderAllocationEngine $allocator,
        private InventoryReservationService $reservations,
        private StockTransferService $transfers,
        private NotificationService $notifications,
        private AllocationDecisionRecorder $decisions,
        private OrderPlacementNotifier $placements,
    ) {}

    /**
     * @param  array{
     *     name: string,
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
     *     time_slot?: string|null,
     *     payment_method: string,
     *     coupon?: Coupon|null,
     *     discount?: float,
     *     notes?: string|null
     * }  $data
     */
    public function handle(User $user, Cart $cart, array $data): Order
    {
        $cart->load(['items.product.media']);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];
        $coupon = $data['coupon'] ?? null;
        $discount = (float) ($data['discount'] ?? 0);
        $subtotal = $cart->items->sum(
            fn ($item): float => BranchProductPriceDisplay::catalogEffectiveAmount($item->product) * $item->quantity,
        );

        $order = DB::transaction(function () use (
            $user,
            $cart,
            $data,
            $latitude,
            $longitude,
            $coupon,
            $discount,
            $subtotal,
        ): Order {
            $plan = $this->allocator->allocate(
                $latitude,
                $longitude,
                $cart->items,
                $data['delivery_method'],
            );

            $inventoryMap = $this->loadInventoryMap($plan);
            $locked = $this->lockPlan($plan, $inventoryMap);

            $address = $this->persistAddress($user, $data);
            $deliveryFee = $plan['shipping_cost'];
            $total = round(max($subtotal - $discount + $deliveryFee, 0), 2);
            $opening = $this->openingStatus($user);

            $order = Order::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'customer_id' => $user->id,
                'placed_by' => null,
                'source' => OrderSource::CustomerCheckout,
                'address_id' => $address->id,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'status' => OrderStatus::from($opening->value),
                'shipping_address_snapshot' => [
                    'name' => trim($data['name']),
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
                'payment_status' => PaymentStatus::PendingCollection,
                'payment_method' => $data['payment_method'],
                'delivery_method' => $data['delivery_method'],
                'delivery_date' => $data['delivery_date'] ?? null,
                'time_slot' => $data['time_slot'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->decisions->record($order, $plan['branch']->id, $plan['decision_trace']);

            $fulfillment = OrderFulfillment::query()->create([
                'order_id' => $order->id,
                'branch_id' => $plan['branch']->id,
                'fulfillment_number' => $order->order_number,
                'status' => $opening,
                'distance_km' => $plan['distance_km'],
                'shipping_cost' => $plan['shipping_cost'],
            ]);

            foreach ($plan['lines'] as $line) {
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

            foreach ($plan['transfers'] as $leg) {
                $this->transfers->createForFulfillment($fulfillment, $leg);
            }

            $this->reservePlan($plan, $inventoryMap, $locked, $order);

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'fulfillment_id' => $fulfillment->id,
                'old_status' => null,
                'new_status' => $opening->value,
                'comment' => $opening === FulfillmentStatus::Placed ? 'Order placed' : 'Awaiting confirmation',
                'changed_by' => $user->id,
            ]);

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'gateway' => $data['payment_method'],
                'amount' => $total,
                'currency' => 'AUD',
                'status' => PaymentStatus::PendingCollection,
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

        $placed = $order->status === OrderStatus::Placed;

        if ($placed) {
            $this->placements->issue($order);
        }

        $this->notifications->send(
            [$user->id],
            NotificationType::Order,
            $placed ? 'Order placed' : 'Order received',
            $placed
                ? 'Your order '.$order->order_number.' has been placed.'
                : 'Your order '.$order->order_number.' is awaiting confirmation.',
            ['order_number' => $order->order_number],
            $user,
        );

        return $order;
    }

    /**
     * Batch-loads every inventory row this plan could touch (local lines plus every
     * transfer leg's source) in a single query, keyed by "branch:product:variant".
     * Replaces the previous per-line/per-leg lookups that caused N+1 queries.
     *
     * @param  array<string, mixed>  $plan
     * @return array<string, Inventory>
     */
    private function loadInventoryMap(array $plan): array
    {
        $keys = [];

        foreach ($plan['lines'] as $line) {
            if ($line['local_quantity'] > 0) {
                $keys[] = [$plan['branch']->id, $line['product_id'], $line['variant_id']];
            }
        }

        foreach ($plan['transfers'] as $leg) {
            foreach ($leg['items'] as $item) {
                $keys[] = [$leg['source_branch_id'], $item['product_id'], $item['variant_id']];
            }
        }

        if ($keys === []) {
            return [];
        }

        $branchIds = array_values(array_unique(array_column($keys, 0)));
        $productIds = array_values(array_unique(array_column($keys, 1)));

        $map = [];

        foreach (Inventory::query()->whereIn('branch_id', $branchIds)->whereIn('product_id', $productIds)->get() as $row) {
            $map[$this->inventoryKey($row->branch_id, $row->product_id, $row->variant_id)] = $row;
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, Inventory>  $inventoryMap
     * @return Collection<int, Inventory>
     */
    private function lockPlan(array $plan, array $inventoryMap): Collection
    {
        $ids = [];

        foreach ($plan['lines'] as $line) {
            if ($line['local_quantity'] > 0) {
                $ids[] = $this->resolveInventory($inventoryMap, $plan['branch']->id, $line['product_id'], $line['variant_id'])->id;
            }
        }

        foreach ($plan['transfers'] as $leg) {
            foreach ($leg['items'] as $item) {
                $ids[] = $this->resolveInventory($inventoryMap, $leg['source_branch_id'], $item['product_id'], $item['variant_id'])->id;
            }
        }

        return $this->reservations->lockOrdered($ids);
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, Inventory>  $inventoryMap
     * @param  Collection<int, Inventory>  $locked
     */
    private function reservePlan(array $plan, array $inventoryMap, $locked, Order $order): void
    {
        foreach ($plan['lines'] as $line) {
            if ($line['local_quantity'] <= 0) {
                continue;
            }

            $inventory = $this->resolveInventory($inventoryMap, $plan['branch']->id, $line['product_id'], $line['variant_id']);
            $this->reservations->reserve($locked[$inventory->id], $line['local_quantity'], $order);
        }

        foreach ($plan['transfers'] as $leg) {
            foreach ($leg['items'] as $item) {
                $inventory = $this->resolveInventory($inventoryMap, $leg['source_branch_id'], $item['product_id'], $item['variant_id']);
                $this->reservations->reserve($locked[$inventory->id], $item['quantity'], $order);
            }
        }
    }

    /**
     * @param  array<string, Inventory>  $inventoryMap
     */
    private function resolveInventory(array $inventoryMap, int $branchId, int $productId, ?int $variantId): Inventory
    {
        $inventory = $inventoryMap[$this->inventoryKey($branchId, $productId, $variantId)] ?? null;

        if ($inventory === null) {
            throw ValidationException::withMessages([
                'cart' => 'One or more items are no longer available.',
            ]);
        }

        return $inventory;
    }

    /**
     * A customer with a completed order is already confirmed. Everyone else
     * waits for a phone confirmation before the order is placed.
     */
    private function openingStatus(User $user): FulfillmentStatus
    {
        $trusted = Order::query()
            ->where('customer_id', $user->id)
            ->where('status', OrderStatus::Delivered)
            ->exists();

        return $trusted ? FulfillmentStatus::Placed : FulfillmentStatus::Pending;
    }

    private function inventoryKey(int $branchId, int $productId, ?int $variantId): string
    {
        return $branchId.':'.$productId.':'.($variantId ?? '0');
    }

    /**
     * @return array<string, mixed>
     */
    private function productSnapshot(Product $product): array
    {
        return [
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'image' => $product->imageUrl(MediaUrl::DEFAULT_FALLBACK),
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
            'receiver_name' => trim($data['name']),
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
