<?php

namespace App\Actions\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Pack;
use App\Models\PackItem;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\Customer\CustomerAccountService;
use App\Services\Notifications\NotificationService;
use App\Services\Order\AllocationDecisionRecorder;
use App\Services\Order\BranchDeliveryQuote;
use App\Services\Order\OrderPlacementNotifier;
use App\Support\BranchProductPriceDisplay;
use App\Support\MediaUrl;
use App\Support\Order\OrderNumber;
use App\Support\Storefront\PackCheckoutSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlacePackOrderAction
{
    public function __construct(
        private BranchDeliveryQuote $branchDelivery,
        private NotificationService $notifications,
        private AllocationDecisionRecorder $decisions,
        private OrderPlacementNotifier $placements,
        private CustomerAccountService $accounts,
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
     *     save_as_default?: bool,
     *     delivery_method: string,
     *     delivery_date?: string|null,
     *     time_slot?: string|null,
     *     payment_method: string,
     *     notes?: string|null
     * }  $data
     */
    public function handle(User $user, Pack $pack, int $quantity, array $data, Request $request): Order
    {
        $pack->loadMissing(['branch', 'items.product.media']);

        if ($pack->items->isEmpty()) {
            throw ValidationException::withMessages([
                'pack' => 'This pack has no products.',
            ]);
        }

        $branch = $pack->branch;

        if ($branch === null || ! $branch->status) {
            throw ValidationException::withMessages([
                'pack' => 'This pack is no longer available.',
            ]);
        }

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];
        $subtotal = round((float) $pack->pack_price * $quantity, 2);

        if ($subtotal <= 0) {
            throw ValidationException::withMessages([
                'pack' => 'This pack cannot be purchased at this time.',
            ]);
        }

        $order = DB::transaction(function () use (
            $user,
            $pack,
            $quantity,
            $data,
            $latitude,
            $longitude,
            $subtotal,
            $branch,
            $request,
        ): Order {
            /** @var Pack $locked */
            $locked = Pack::query()->whereKey($pack->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status || $locked->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'pack' => 'This pack is no longer available in the requested quantity.',
                ]);
            }

            $today = now()->toDateString();

            if ($locked->starts_at !== null && $locked->starts_at->toDateString() > $today) {
                throw ValidationException::withMessages([
                    'pack' => 'This pack is not available yet.',
                ]);
            }

            if ($locked->ends_at !== null && $locked->ends_at->toDateString() < $today) {
                throw ValidationException::withMessages([
                    'pack' => 'This pack offer has ended.',
                ]);
            }

            $locked->loadMissing(['items.product.media']);

            $quote = $this->branchDelivery->quote($branch, $latitude, $longitude);
            $deliveryFee = round($quote['fee'], 2);
            $total = round(max($subtotal + $deliveryFee, 0), 2);
            $opening = $this->openingStatus($user);
            $address = $this->saveDefaultAddress($user, $data);

            $order = Order::query()->create([
                'order_number' => OrderNumber::next(),
                'customer_id' => $user->id,
                'placed_by' => null,
                'source' => OrderSource::CustomerCheckout,
                'address_id' => $address?->id,
                'coupon_id' => null,
                'coupon_code' => null,
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
                'discount' => 0,
                'delivery_fee' => $deliveryFee,
                'tax' => 0,
                'total_amount' => $total,
                'payment_status' => PaymentStatus::PendingCollection,
                'payment_method' => $data['payment_method'],
                'delivery_method' => $data['delivery_method'],
                'delivery_date' => $data['delivery_date'] ?? null,
                'time_slot' => $data['time_slot'] ?? null,
                'notes' => $data['notes'] ?? null,
                'pack_id' => $locked->id,
                'pack_quantity' => $quantity,
            ]);

            $this->decisions->record($order, $branch->id, [
                'considered' => [[
                    'branch_id' => $branch->id,
                    'needs_transfer' => 0,
                    'operational_cost' => 0,
                    'distance_km' => $quote['distance_km'],
                    'shipping_cost' => $deliveryFee,
                    'load' => 0,
                    'transferred_units' => 0,
                    'priority' => 0,
                    'reason' => 'pack_checkout',
                ]],
                'rejected' => [],
            ]);

            $fulfillment = OrderFulfillment::query()->create([
                'order_id' => $order->id,
                'branch_id' => $branch->id,
                'fulfillment_number' => $order->order_number,
                'status' => $opening,
                'distance_km' => $quote['distance_km'],
                'shipping_cost' => $deliveryFee,
            ]);

            foreach ($this->apportionedLines($locked, $quantity, $subtotal) as $line) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'fulfillment_id' => $fulfillment->id,
                    'product_id' => $line['product']->id,
                    'variant_id' => null,
                    'product_name_snapshot' => $line['product']->name,
                    'sku_snapshot' => $line['product']->sku,
                    'product_snapshot' => $this->productSnapshot($line['product'], $locked),
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            $remaining = $locked->quantity - $quantity;
            Pack::query()->whereKey($locked->id)->update([
                'quantity' => $remaining,
                'status' => $remaining > 0 ? $locked->status : false,
            ]);

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

            PackCheckoutSession::forget($request);

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
     * Split pack_price × qty across product lines by catalog weight; last line absorbs remainder.
     *
     * @return list<array{product: Product, quantity: int, unit_price: float, subtotal: float}>
     */
    private function apportionedLines(Pack $pack, int $packQuantity, float $orderSubtotal): array
    {
        $rows = [];
        $weights = [];

        foreach ($pack->items as $item) {
            /** @var PackItem $item */
            $product = $item->product;

            if ($product === null) {
                continue;
            }

            $lineQty = $item->quantity * $packQuantity;

            if ($lineQty < 1) {
                continue;
            }

            $catalogUnit = max(0.0, BranchProductPriceDisplay::catalogEffectiveAmount($product));
            $weight = max($catalogUnit * $lineQty, 0.01);
            $weights[] = $weight;
            $rows[] = [
                'product' => $product,
                'quantity' => $lineQty,
                'weight' => $weight,
            ];
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'pack' => 'This pack has no products.',
            ]);
        }

        $weightTotal = array_sum($weights);
        $allocated = 0.0;
        $lines = [];
        $lastIndex = count($rows) - 1;

        foreach ($rows as $index => $row) {
            if ($index === $lastIndex) {
                $lineSubtotal = round($orderSubtotal - $allocated, 2);
            } else {
                $lineSubtotal = round($orderSubtotal * ($row['weight'] / $weightTotal), 2);
                $allocated += $lineSubtotal;
            }

            $unit = $row['quantity'] > 0
                ? round($lineSubtotal / $row['quantity'], 2)
                : 0.0;

            $lines[] = [
                'product' => $row['product'],
                'quantity' => $row['quantity'],
                'unit_price' => $unit,
                'subtotal' => $lineSubtotal,
            ];
        }

        return $lines;
    }

    private function openingStatus(User $user): FulfillmentStatus
    {
        $trusted = Order::query()
            ->where('customer_id', $user->id)
            ->where('status', OrderStatus::Delivered)
            ->exists();

        return $trusted ? FulfillmentStatus::Placed : FulfillmentStatus::Pending;
    }

    /**
     * @return array<string, mixed>
     */
    private function productSnapshot(Product $product, Pack $pack): array
    {
        return [
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'image' => $product->imageUrl(MediaUrl::DEFAULT_FALLBACK),
            'description' => $product->packSummary(),
            'brand' => $product->brand,
            'pack_label' => $product->pack_label,
            'pack_id' => $pack->id,
            'pack_name' => $pack->name,
            'pack_slug' => $pack->slug,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveDefaultAddress(User $user, array $data): ?Address
    {
        if (! (bool) ($data['save_as_default'] ?? false)) {
            return null;
        }

        return $this->accounts->saveDeliveryAddress($user, $data);
    }
}
