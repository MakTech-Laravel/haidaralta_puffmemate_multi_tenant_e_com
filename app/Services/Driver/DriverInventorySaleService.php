<?php

namespace App\Services\Driver;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderSource;
use App\Models\Branch;
use App\Models\DriverInventory;
use App\Models\DriverInventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\BranchProductPriceDisplay;
use Illuminate\Validation\ValidationException;

class DriverInventorySaleService
{
    /**
     * @param  list<array{product_id: int, variant_id: int|null, quantity: int}>  $lines
     */
    public function deduct(User $driver, int $branchId, Order $order, array $lines): void
    {
        foreach ($this->sorted($lines) as $line) {
            $row = $this->lockRow($driver->id, $branchId, $line['product_id'], $line['variant_id']);

            if ($row === null || $row->quantity < $line['quantity']) {
                throw ValidationException::withMessages([
                    'items' => 'Not enough assigned stock for this sale.',
                ]);
            }

            $before = $row->quantity;
            $after = $before - $line['quantity'];
            $row->quantity = $after;
            $row->save();

            $this->writeLedger(
                $row,
                InventoryTransactionType::Sale,
                $line['quantity'],
                $before,
                $after,
                $order,
                $driver,
            );
        }
    }

    public function reverse(Order $order, ?User $actor = null): void
    {
        if ($order->source !== OrderSource::DriverSale) {
            return;
        }

        $alreadyReversed = DriverInventoryTransaction::query()
            ->where('reference_type', $order->getMorphClass())
            ->where('reference_id', $order->id)
            ->where('type', InventoryTransactionType::StockIn)
            ->lockForUpdate()
            ->exists();

        if ($alreadyReversed) {
            throw ValidationException::withMessages([
                'status' => 'Driver stock for this sale was already returned.',
            ]);
        }

        $sales = DriverInventoryTransaction::query()
            ->where('reference_type', $order->getMorphClass())
            ->where('reference_id', $order->id)
            ->where('type', InventoryTransactionType::Sale)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($sales as $sale) {
            $row = $this->lockRow(
                $sale->user_id,
                $sale->branch_id,
                $sale->product_id,
                $sale->variant_id,
            );

            if ($row === null) {
                throw ValidationException::withMessages([
                    'status' => 'Assigned stock for this sale could not be restored.',
                ]);
            }

            $before = $row->quantity;
            $after = $before + $sale->quantity;
            $row->quantity = $after;
            $row->save();

            $this->writeLedger(
                $row,
                InventoryTransactionType::StockIn,
                $sale->quantity,
                $before,
                $after,
                $order,
                $actor,
            );
        }
    }

    public function unitPrice(Branch $branch, Product $product): ?float
    {
        $display = BranchProductPriceDisplay::forBranchProduct($branch, $product);
        $price = $display['effective'] ?? $display['selling'];

        if ($price === null) {
            $catalog = BranchProductPriceDisplay::forCatalogProduct($product);
            $price = $catalog['effective'] ?? $catalog['selling'] ?? $product->selling_price;
        }

        if ($price === null || (float) $price <= 0) {
            return null;
        }

        return round((float) $price, 2);
    }

    /**
     * @param  list<array{product_id: int, variant_id: int|null, quantity: int}>  $lines
     * @return list<array{product_id: int, variant_id: int|null, quantity: int}>
     */
    private function sorted(array $lines): array
    {
        usort($lines, function (array $left, array $right): int {
            return [$left['product_id'], $left['variant_id'] ?? 0]
                <=> [$right['product_id'], $right['variant_id'] ?? 0];
        });

        return $lines;
    }

    private function lockRow(int $userId, int $branchId, int $productId, ?int $variantId): ?DriverInventory
    {
        $query = DriverInventory::query()
            ->where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->lockForUpdate();

        if ($variantId === null) {
            $query->whereNull('variant_id');
        } else {
            $query->where('variant_id', $variantId);
        }

        return $query->first();
    }

    private function writeLedger(
        DriverInventory $row,
        InventoryTransactionType $type,
        int $quantity,
        int $before,
        int $after,
        Order $order,
        ?User $actor,
    ): void {
        DriverInventoryTransaction::query()->create([
            'branch_id' => $row->branch_id,
            'user_id' => $row->user_id,
            'product_id' => $row->product_id,
            'variant_id' => $row->variant_id,
            'type' => $type,
            'reason' => null,
            'reason_note' => $type === InventoryTransactionType::StockIn ? 'Driver sale cancelled' : null,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $after,
            'reference_type' => $order->getMorphClass(),
            'reference_id' => $order->id,
            'created_by' => $actor?->id,
            'created_at' => now(),
        ]);
    }
}
