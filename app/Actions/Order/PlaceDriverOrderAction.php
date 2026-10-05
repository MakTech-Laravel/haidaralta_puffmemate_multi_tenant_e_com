<?php

namespace App\Actions\Order;

use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverInventory;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\Customer\CustomerResolutionService;
use App\Services\Driver\DriverInventorySaleService;
use App\Services\Order\PaymentMarkingService;
use App\Support\Driver\DriverSaleRules;
use App\Support\MediaUrl;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceDriverOrderAction
{
    public function __construct(
        private CustomerResolutionService $customers,
        private DriverInventorySaleService $inventory,
        private PaymentMarkingService $payments,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $driver, array $data): Order
    {
        if (! $driver->isDriver()) {
            throw ValidationException::withMessages([
                'driver' => 'Only a driver can place this sale.',
            ]);
        }

        try {
            return DB::transaction(function () use ($driver, $data): Order {
                $key = (string) $data['idempotency_key'];

                $existing = Order::query()
                    ->where('idempotency_key', $key)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    if ($existing->placed_by !== $driver->id) {
                        throw ValidationException::withMessages([
                            'idempotency_key' => 'This sale was already submitted.',
                        ]);
                    }

                    return $existing;
                }

                $branchId = $this->activeBranchId($driver);

                if ($branchId === null) {
                    throw ValidationException::withMessages([
                        'items' => 'You need one active branch assignment before selling.',
                    ]);
                }

                $branch = Branch::query()->findOrFail($branchId);
                $customer = $this->customers->resolve(
                    isset($data['customer_id']) ? (int) $data['customer_id'] : null,
                    isset($data['phone']) ? (string) $data['phone'] : null,
                    isset($data['name']) ? (string) $data['name'] : null,
                );

                $lines = $this->pricedLines($branch, $branchId, $driver, $data['items']);
                $subtotal = round(array_sum(array_column($lines, 'subtotal')), 2);

                $order = Order::query()->create([
                    'order_number' => $this->nextOrderNumber(),
                    'customer_id' => $customer->id,
                    'placed_by' => $driver->id,
                    'source' => OrderSource::DriverSale,
                    'idempotency_key' => $key,
                    'status' => DriverSaleRules::orderStatus(),
                    'shipping_address_snapshot' => [
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                    ],
                    'subtotal' => $subtotal,
                    'discount' => 0,
                    'delivery_fee' => 0,
                    'tax' => 0,
                    'total_amount' => $subtotal,
                    'payment_status' => PaymentStatus::PendingCollection,
                    'payment_method' => $data['payment_method'],
                    'delivery_method' => 'driver_sale',
                    'delivery_date' => now()->toDateString(),
                    'notes' => $data['notes'] ?? null,
                ]);

                $fulfillment = OrderFulfillment::query()->create([
                    'order_id' => $order->id,
                    'branch_id' => $branchId,
                    'driver_id' => null,
                    'fulfillment_number' => $order->order_number,
                    'status' => DriverSaleRules::fulfillmentStatus(),
                    'shipping_cost' => 0,
                ]);

                foreach ($lines as $line) {
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'fulfillment_id' => $fulfillment->id,
                        'product_id' => $line['product_id'],
                        'variant_id' => $line['variant_id'],
                        'product_name_snapshot' => $line['name'],
                        'sku_snapshot' => $line['sku'],
                        'product_snapshot' => $line['snapshot'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'subtotal' => $line['subtotal'],
                    ]);
                }

                $this->inventory->deduct($driver, $branchId, $order, $lines);

                $payment = Payment::query()->create([
                    'order_id' => $order->id,
                    'gateway' => $data['payment_method'],
                    'amount' => $subtotal,
                    'currency' => 'AUD',
                    'status' => PaymentStatus::PendingCollection,
                ]);

                PaymentTransaction::query()->create([
                    'payment_id' => $payment->id,
                    'event_type' => 'initiated',
                    'response' => ['offline' => true, 'driver_sale' => true],
                ]);

                if (DriverSaleRules::collectsImmediately((string) $data['payment_method'])) {
                    $this->payments->collect($order, $driver);
                }

                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'fulfillment_id' => $fulfillment->id,
                    'old_status' => null,
                    'new_status' => OrderStatus::Delivered->value,
                    'comment' => 'Driver sale completed',
                    'changed_by' => $driver->id,
                ]);

                return $order->refresh();
            });
        } catch (UniqueConstraintViolationException) {
            $replay = Order::query()->where('idempotency_key', (string) $data['idempotency_key'])->first();

            if ($replay !== null && $replay->placed_by === $driver->id) {
                return $replay;
            }

            throw ValidationException::withMessages([
                'idempotency_key' => 'This sale was already submitted.',
            ]);
        }
    }

    private function activeBranchId(User $driver): ?int
    {
        $ids = DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('status', DriverAssignmentStatus::Active)
            ->pluck('branch_id')
            ->unique()
            ->values();

        if ($ids->count() !== 1) {
            return null;
        }

        return (int) $ids->first();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function pricedLines(Branch $branch, int $branchId, User $driver, array $items): array
    {
        $merged = [];

        foreach ($items as $item) {
            [$productId, $variantId] = $this->parseLineId((string) ($item['id'] ?? ''));
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId < 1 || $quantity < 1) {
                throw ValidationException::withMessages([
                    'items' => 'Choose a product and quantity.',
                ]);
            }

            $key = $productId.'|'.($variantId ?? 'null');

            if (isset($merged[$key])) {
                $merged[$key]['quantity'] += $quantity;
            } else {
                $merged[$key] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                ];
            }
        }

        $lines = [];

        foreach ($merged as $line) {
            $owned = DriverInventory::query()
                ->where('user_id', $driver->id)
                ->where('branch_id', $branchId)
                ->where('product_id', $line['product_id'])
                ->when(
                    $line['variant_id'] === null,
                    fn ($query) => $query->whereNull('variant_id'),
                    fn ($query) => $query->where('variant_id', $line['variant_id']),
                )
                ->exists();

            if (! $owned) {
                throw ValidationException::withMessages([
                    'items' => 'That product is not in your assigned stock.',
                ]);
            }

            $product = Product::query()->with('media')->find($line['product_id']);

            if ($product === null || ! $product->status) {
                throw ValidationException::withMessages([
                    'items' => 'That product is not available.',
                ]);
            }

            $unit = $this->inventory->unitPrice($branch, $product);

            if ($unit === null) {
                throw ValidationException::withMessages([
                    'items' => 'That product does not have a selling price.',
                ]);
            }

            $lines[] = [
                'product_id' => $line['product_id'],
                'variant_id' => $line['variant_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $unit,
                'subtotal' => round($unit * $line['quantity'], 2),
                'name' => $product->name,
                'sku' => $product->sku,
                'snapshot' => [
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'image' => $product->imageUrl(MediaUrl::DEFAULT_FALLBACK),
                    'description' => $product->packSummary(),
                    'brand' => $product->brand,
                    'pack_label' => $product->pack_label,
                ],
            ];
        }

        return $lines;
    }

    /**
     * @return array{0: int, 1: int|null}
     */
    private function parseLineId(string $id): array
    {
        if (preg_match('/^(\d+)(?::(\d+))?$/', $id, $matches) !== 1) {
            throw ValidationException::withMessages([
                'items' => 'Choose a product and quantity.',
            ]);
        }

        return [(int) $matches[1], isset($matches[2]) ? (int) $matches[2] : null];
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
