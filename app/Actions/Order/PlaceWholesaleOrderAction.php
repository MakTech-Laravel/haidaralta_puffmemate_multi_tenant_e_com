<?php

namespace App\Actions\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\WholesaleInquiryStatus;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WholesaleInquiry;
use App\Services\Customer\CustomerResolutionService;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Notifications\NotificationService;
use App\Services\Order\BranchDeliveryQuote;
use App\Services\Order\BranchOrderNotifier;
use App\Services\Order\OrderPlacementNotifier;
use App\Support\MediaUrl;
use App\Support\Order\OrderNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin-reviewed wholesale order: one branch, admin-set unit prices, placed
 * immediately (no phone confirmation) with stock reserved at that branch.
 */
class PlaceWholesaleOrderAction
{
    public const DELIVERY_METHOD = 'wholesale';

    public function __construct(
        private CustomerResolutionService $customers,
        private InventoryReservationService $reservations,
        private BranchDeliveryQuote $deliveryQuote,
        private OrderPlacementNotifier $placements,
        private NotificationService $notifications,
        private BranchOrderNotifier $branchNotifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $admin, array $data): Order
    {
        $key = (string) $data['idempotency_key'];
        $created = false;

        try {
            $order = DB::transaction(function () use ($admin, $data, $key, &$created): Order {
                $existing = Order::query()
                    ->where('idempotency_key', $key)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    return $this->replay($existing, $admin);
                }

                $inquiry = $this->openInquiry($data);
                $branch = Branch::query()->whereKey((int) $data['branch_id'])->where('status', true)->first();

                if ($branch === null) {
                    throw ValidationException::withMessages([
                        'branch_id' => 'Choose an active branch.',
                    ]);
                }

                $customer = $this->customers->resolve(
                    $this->customerId($data, $inquiry),
                    (string) $data['phone'],
                    (string) $data['name'],
                );

                $lines = $this->pricedLines($data['items']);
                $locked = $this->lockStock($branch, $lines);

                $subtotal = round(array_sum(array_column($lines, 'subtotal')), 2);
                $discount = round(min(max((float) ($data['discount'] ?? 0), 0), $subtotal), 2);
                $quote = $this->deliveryQuote->quote($branch, $data['latitude'], $data['longitude']);
                $deliveryFee = isset($data['delivery_fee'])
                    ? round((float) $data['delivery_fee'], 2)
                    : $quote['fee'];
                $total = round(max($subtotal - $discount + $deliveryFee, 0), 2);

                $order = Order::query()->create([
                    'order_number' => OrderNumber::next(),
                    'customer_id' => $customer->id,
                    'placed_by' => $admin->id,
                    'source' => OrderSource::Wholesale,
                    'idempotency_key' => $key,
                    'status' => OrderStatus::Placed,
                    'shipping_address_snapshot' => [
                        'name' => trim((string) $data['name']),
                        'phone' => (string) $data['phone'],
                        'email' => $data['email'] ?? null,
                        'company' => $data['company'] ?? null,
                        'address' => (string) $data['address'],
                    ],
                    'latitude' => (float) $data['latitude'],
                    'longitude' => (float) $data['longitude'],
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'delivery_fee' => $deliveryFee,
                    'tax' => 0,
                    'total_amount' => $total,
                    'payment_status' => PaymentStatus::PendingCollection,
                    'payment_method' => $data['payment_method'],
                    'delivery_method' => self::DELIVERY_METHOD,
                    'delivery_date' => $data['delivery_date'] ?? null,
                    'time_slot' => $data['time_slot'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $fulfillment = OrderFulfillment::query()->create([
                    'order_id' => $order->id,
                    'branch_id' => $branch->id,
                    'fulfillment_number' => $order->order_number,
                    'status' => FulfillmentStatus::Placed,
                    'distance_km' => $quote['distance_km'],
                    'shipping_cost' => $deliveryFee,
                ]);

                foreach ($lines as $line) {
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'fulfillment_id' => $fulfillment->id,
                        'product_id' => $line['product_id'],
                        'variant_id' => $line['variant_id'],
                        'product_name_snapshot' => $line['name'],
                        'sku_snapshot' => $line['sku'],
                        'product_snapshot' => $this->productSnapshot($line['product']),
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'subtotal' => $line['subtotal'],
                    ]);

                    $this->reservations->reserve(
                        $locked[$line['inventory_id']],
                        $line['quantity'],
                        $order,
                        $admin,
                    );
                }

                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'fulfillment_id' => $fulfillment->id,
                    'old_status' => null,
                    'new_status' => FulfillmentStatus::Placed->value,
                    'comment' => 'Wholesale order placed',
                    'changed_by' => $admin->id,
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
                    'response' => ['offline' => true, 'wholesale' => true],
                ]);

                $inquiry?->forceFill([
                    'status' => WholesaleInquiryStatus::Converted,
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'read_at' => $inquiry->read_at ?? now(),
                ])->save();

                $created = true;

                return $order->refresh();
            });
        } catch (UniqueConstraintViolationException) {
            $existing = Order::query()->where('idempotency_key', $key)->first();

            if ($existing === null) {
                throw ValidationException::withMessages([
                    'idempotency_key' => 'Another order was placed at the same moment. Please place this order again.',
                ]);
            }

            return $this->replay($existing, $admin);
        }

        if ($created) {
            $this->announce($order, $admin);
        }

        return $order;
    }

    private function replay(Order $existing, User $admin): Order
    {
        if ($existing->placed_by !== $admin->id || $existing->source !== OrderSource::Wholesale) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'This order was already submitted.',
            ]);
        }

        return $existing;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function openInquiry(array $data): ?WholesaleInquiry
    {
        if (empty($data['wholesale_inquiry_id'])) {
            return null;
        }

        $inquiry = WholesaleInquiry::query()
            ->whereKey((int) $data['wholesale_inquiry_id'])
            ->lockForUpdate()
            ->first();

        if ($inquiry === null || ! $inquiry->status->isOpen()) {
            throw ValidationException::withMessages([
                'wholesale_inquiry_id' => 'This inquiry is already closed.',
            ]);
        }

        return $inquiry;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function customerId(array $data, ?WholesaleInquiry $inquiry): ?int
    {
        if ($inquiry?->customer_id !== null) {
            return (int) $inquiry->customer_id;
        }

        return empty($data['customer_id']) ? null : (int) $data['customer_id'];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{index: int, product_id: int, variant_id: int|null, quantity: int, unit_price: float, subtotal: float, product: Product, name: string, sku: string|null, inventory_id?: int}>
     */
    private function pricedLines(array $items): array
    {
        $merged = [];

        foreach (array_values($items) as $index => $item) {
            $productId = (int) $item['product_id'];
            $variantId = isset($item['variant_id']) && $item['variant_id'] !== '' ? (int) $item['variant_id'] : null;
            $key = $productId.'|'.($variantId ?? 'null');

            if (isset($merged[$key])) {
                $merged[$key]['quantity'] += (int) $item['quantity'];

                continue;
            }

            $merged[$key] = [
                'index' => $index,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => (int) $item['quantity'],
                'unit_price' => round((float) $item['unit_price'], 2),
            ];
        }

        $products = Product::query()
            ->with('media')
            ->whereIn('id', array_column($merged, 'product_id'))
            ->where('status', true)
            ->get()
            ->keyBy('id');

        $variants = ProductVariant::query()
            ->whereIn('id', array_filter(array_column($merged, 'variant_id')))
            ->where('status', true)
            ->get(['id', 'product_id', 'name', 'sku'])
            ->keyBy('id');

        $lines = [];

        foreach ($merged as $line) {
            $product = $products->get($line['product_id']);

            if ($product === null) {
                throw ValidationException::withMessages([
                    "items.{$line['index']}.product_id" => 'This product is no longer available.',
                ]);
            }

            $variant = $line['variant_id'] === null ? null : $variants->get($line['variant_id']);

            if ($line['variant_id'] !== null && ($variant === null || $variant->product_id !== $product->id)) {
                throw ValidationException::withMessages([
                    "items.{$line['index']}.product_id" => 'This product option is no longer available.',
                ]);
            }

            $lines[] = [
                ...$line,
                'subtotal' => round($line['unit_price'] * $line['quantity'], 2),
                'product' => $product,
                'name' => $variant === null ? $product->name : $product->name.' · '.$variant->name,
                'sku' => $variant?->sku ?: $product->sku,
            ];
        }

        return $lines;
    }

    /**
     * Locks the branch inventory rows and checks free stock for every line.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return Collection<int, Inventory>
     */
    private function lockStock(Branch $branch, array &$lines)
    {
        $rows = Inventory::query()
            ->where('branch_id', $branch->id)
            ->whereIn('product_id', array_column($lines, 'product_id'))
            ->where('status', true)
            ->get();

        foreach ($lines as &$line) {
            $inventory = $rows->first(
                fn (Inventory $row): bool => $row->product_id === $line['product_id']
                    && $row->variant_id === $line['variant_id'],
            );

            if ($inventory === null) {
                throw ValidationException::withMessages([
                    "items.{$line['index']}.quantity" => $line['name'].' is not stocked at '.$branch->name.'.',
                ]);
            }

            $line['inventory_id'] = $inventory->id;
        }
        unset($line);

        $locked = $this->reservations->lockOrdered(array_column($lines, 'inventory_id'));

        foreach ($lines as $line) {
            $available = $this->reservations->available($locked[$line['inventory_id']]);

            if ($available < $line['quantity']) {
                throw ValidationException::withMessages([
                    "items.{$line['index']}.quantity" => 'Only '.$available.' of '.$line['name'].' available at '.$branch->name.'.',
                ]);
            }
        }

        return $locked;
    }

    /**
     * The order is already committed, so a failed SMS, mail, or notification is
     * reported instead of failing the request. Staff can resend the code from
     * the order page.
     */
    private function announce(Order $order, User $admin): void
    {
        rescue(fn () => $this->placements->issue($order));

        rescue(fn () => $this->notifications->send(
            [$order->customer_id],
            NotificationType::Order,
            'Order placed',
            'Your wholesale order '.$order->order_number.' has been placed.',
            [
                'order_number' => $order->order_number,
                'link_url' => route('customer.orders.show', $order),
                'link_label' => 'Track order',
            ],
            $admin,
        ));

        $fulfillment = $order->fulfillments()->first();

        if ($fulfillment !== null) {
            rescue(fn () => $this->branchNotifier->newOrder(
                $fulfillment,
                'New wholesale order',
                'Wholesale order '.$order->order_number.' is ready for your branch to confirm.',
                $admin,
            ));
        }
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
}
