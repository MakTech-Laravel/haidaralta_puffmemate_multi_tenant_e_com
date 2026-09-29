<?php

namespace App\Services\Order;

use App\Enums\StockTransferStatus;
use App\Models\Inventory;
use App\Models\OrderFulfillment;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(private InventoryReservationService $reservations) {}

    /**
     * @param  array{
     *     source_branch_id: int,
     *     destination_branch_id: int,
     *     distance_km: float,
     *     eta_hours: float,
     *     estimated_cost: float,
     *     items: list<array{product_id: int, variant_id: int|null, quantity: int}>
     * }  $leg
     */
    public function createForFulfillment(OrderFulfillment $fulfillment, array $leg): StockTransfer
    {
        $transfer = StockTransfer::query()->create([
            'order_id' => $fulfillment->order_id,
            'fulfillment_id' => $fulfillment->id,
            'source_branch_id' => $leg['source_branch_id'],
            'destination_branch_id' => $leg['destination_branch_id'],
            'status' => StockTransferStatus::Requested,
            'estimated_cost' => $leg['estimated_cost'],
            'distance_km' => $leg['distance_km'],
            'eta_hours' => $leg['eta_hours'],
        ]);

        foreach ($leg['items'] as $item) {
            StockTransferItem::query()->create([
                'stock_transfer_id' => $transfer->id,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return $transfer->load('items');
    }

    public function approve(StockTransfer $transfer, User $actor): StockTransfer
    {
        $this->assertSource($transfer, $actor);

        return $this->move($transfer, StockTransferStatus::Requested, StockTransferStatus::Approved);
    }

    public function pick(StockTransfer $transfer, User $actor): StockTransfer
    {
        $this->assertSource($transfer, $actor);

        return $this->move($transfer, StockTransferStatus::Approved, StockTransferStatus::Picking);
    }

    public function dispatch(StockTransfer $transfer, User $actor): StockTransfer
    {
        $this->assertSource($transfer, $actor);

        $this->move($transfer, StockTransferStatus::Picking, StockTransferStatus::Dispatched);

        return $this->move($transfer->refresh(), StockTransferStatus::Dispatched, StockTransferStatus::InTransit);
    }

    public function receive(StockTransfer $transfer, User $actor): StockTransfer
    {
        $this->assertDestination($transfer, $actor);

        if ($transfer->status !== StockTransferStatus::InTransit) {
            throw ValidationException::withMessages([
                'status' => 'This transfer cannot be received yet.',
            ]);
        }

        return DB::transaction(function () use ($transfer, $actor): StockTransfer {
            $transfer->load('items');

            foreach ($transfer->items as $item) {
                $source = $this->lockInventory(
                    $transfer->source_branch_id,
                    $item->product_id,
                    $item->variant_id,
                );
                $this->reservations->transferOut($source, $item->quantity, $transfer, $actor);

                $destination = $this->reservations->firstOrCreateLocked(
                    $transfer->destination_branch_id,
                    $item->product_id,
                    $item->variant_id,
                );
                $this->reservations->transferIn($destination, $item->quantity, $transfer, $actor);
            }

            $transfer->forceFill(['status' => StockTransferStatus::Received])->save();

            return $transfer->refresh();
        });
    }

    public function cancel(StockTransfer $transfer, User $actor): StockTransfer
    {
        if (! in_array($transfer->status, [StockTransferStatus::Requested, StockTransferStatus::Approved], true)) {
            throw ValidationException::withMessages([
                'status' => 'This transfer can no longer be cancelled.',
            ]);
        }

        $transfer->forceFill(['status' => StockTransferStatus::Cancelled])->save();

        return $transfer->refresh();
    }

    public function fulfillmentTransfersReceived(OrderFulfillment $fulfillment): bool
    {
        return ! $fulfillment->stockTransfers()
            ->where('status', '!=', StockTransferStatus::Received)
            ->where('status', '!=', StockTransferStatus::Cancelled)
            ->exists();
    }

    private function move(
        StockTransfer $transfer,
        StockTransferStatus $from,
        StockTransferStatus $to,
    ): StockTransfer {
        if ($transfer->status !== $from) {
            throw ValidationException::withMessages([
                'status' => 'This transfer cannot move to '.$to->value.'.',
            ]);
        }

        $transfer->forceFill(['status' => $to])->save();

        return $transfer->refresh();
    }

    private function assertSource(StockTransfer $transfer, User $actor): void
    {
        if ($actor->branch_id !== $transfer->source_branch_id && ! $actor->isPlatform()) {
            abort(404);
        }
    }

    private function assertDestination(StockTransfer $transfer, User $actor): void
    {
        if ($actor->branch_id !== $transfer->destination_branch_id && ! $actor->isPlatform()) {
            abort(404);
        }
    }

    private function lockInventory(int $branchId, int $productId, ?int $variantId): Inventory
    {
        $query = Inventory::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId);

        if ($variantId === null) {
            $query->whereNull('variant_id');
        } else {
            $query->where('variant_id', $variantId);
        }

        $inventory = $query->lockForUpdate()->first();

        if ($inventory === null) {
            throw ValidationException::withMessages([
                'status' => 'Source inventory is missing for this transfer.',
            ]);
        }

        return $inventory;
    }
}
