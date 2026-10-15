<?php

namespace App\Services\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\StockTransferStatus;
use App\Models\Inventory;
use App\Models\OrderFulfillment;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\Conversation\OrderConversationCloser;
use App\Services\Driver\DriverInventorySaleService;
use App\Services\Inventory\InventoryReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillmentStatusService
{
    public function __construct(
        private InventoryReservationService $reservations,
        private StockTransferService $transfers,
        private OrderConversationCloser $conversations,
        private DriverInventorySaleService $driverSales,
        private OrderPlacementNotifier $placements,
    ) {}

    public function transition(
        OrderFulfillment $fulfillment,
        FulfillmentStatus $status,
        ?User $actor = null,
        ?string $comment = null,
    ): OrderFulfillment {
        if ($fulfillment->status === $status) {
            return $fulfillment;
        }

        $this->assertStaffTransition($fulfillment, $status);

        if ($status === FulfillmentStatus::Processing && ! $this->transfers->fulfillmentTransfersReceived($fulfillment)) {
            throw ValidationException::withMessages([
                'status' => 'Every stock transfer must be received before packaging can start.',
            ]);
        }

        if ($status === FulfillmentStatus::Cancelled) {
            return $this->cancel($fulfillment, $actor, $comment ?? 'Order cancelled');
        }

        return $this->apply($fulfillment, $status, $actor, $comment ?? 'Status updated');
    }

    public function cancel(OrderFulfillment $fulfillment, ?User $actor = null, string $comment = 'Order cancelled', bool $force = false): OrderFulfillment
    {
        $from = $fulfillment->status;

        if (! $force && $actor?->isCustomer()) {
            if (! $from->allowsCustomerCancel()) {
                throw ValidationException::withMessages([
                    'status' => 'This order can no longer be cancelled.',
                ]);
            }
        } elseif (! $force && ! $from->allowsStaffCancel() && $from !== FulfillmentStatus::Returning) {
            throw ValidationException::withMessages([
                'status' => 'This order can no longer be cancelled.',
            ]);
        }

        $fulfillment->loadMissing(['stockTransfers', 'order.items']);

        foreach ($fulfillment->stockTransfers as $transfer) {
            if (in_array($transfer->status, [StockTransferStatus::Received, StockTransferStatus::Cancelled], true)) {
                continue;
            }

            if (! in_array($transfer->status, [StockTransferStatus::Requested, StockTransferStatus::Approved], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Receive or reverse the stock transfer before cancelling.',
                ]);
            }
        }

        return DB::transaction(function () use ($fulfillment, $actor, $comment): OrderFulfillment {
            foreach ($fulfillment->stockTransfers as $transfer) {
                if ($transfer->status !== StockTransferStatus::Cancelled) {
                    $this->transfers->cancel($transfer, $actor ?? $fulfillment->order->customer);
                }
            }

            $this->releaseReservations($fulfillment, $actor);

            $order = $fulfillment->order()->first();

            if ($order !== null) {
                $this->driverSales->reverse($order, $actor);
            }

            if ($fulfillment->driver_id !== null) {
                $fulfillment->forceFill(['driver_id' => null])->save();
            }

            $this->apply($fulfillment->refresh(), FulfillmentStatus::Cancelled, $actor, $comment);

            return $fulfillment->refresh();
        });
    }

    public function canStartProcessing(OrderFulfillment $fulfillment): bool
    {
        return $this->transfers->fulfillmentTransfersReceived($fulfillment);
    }

    /**
     * @return list<array{key: string, label: string, status: string}>
     */
    public function staffActions(OrderFulfillment $fulfillment): array
    {
        $actions = $fulfillment->status->staffActions();

        if (! $this->canStartProcessing($fulfillment)) {
            $actions = array_values(array_filter(
                $actions,
                fn (array $action): bool => $action['status'] !== FulfillmentStatus::Processing->value,
            ));
        }

        return $actions;
    }

    public function markReady(OrderFulfillment $fulfillment, User $actor): OrderFulfillment
    {
        return $this->transition($fulfillment, FulfillmentStatus::Ready, $actor, 'Packed and ready');
    }

    public function apply(
        OrderFulfillment $fulfillment,
        FulfillmentStatus $status,
        ?User $actor = null,
        ?string $comment = null,
    ): OrderFulfillment {
        $old = $fulfillment->status;
        $attributes = ['status' => $status];

        if ($status === FulfillmentStatus::Returned) {
            $attributes['driver_id'] = null;
        }

        $fulfillment->forceFill($attributes)->save();

        OrderStatusHistory::query()->create([
            'order_id' => $fulfillment->order_id,
            'fulfillment_id' => $fulfillment->id,
            'old_status' => $old->value,
            'new_status' => $status->value,
            'comment' => $comment ?? 'Status updated',
            'changed_by' => $actor?->id,
        ]);

        $order = $fulfillment->order()->first();

        if ($order !== null) {
            $order->forceFill(['status' => OrderStatus::from($status->value)])->save();
            $this->conversations->transition($order->refresh(), OrderStatus::from($status->value));

            if ($status === FulfillmentStatus::Placed && $old !== FulfillmentStatus::Placed) {
                $placed = $order->refresh();
                DB::afterCommit(function () use ($placed): void {
                    $this->placements->issue($placed);
                });
            }

            if ($status === FulfillmentStatus::Cancelled) {
                $this->placements->discard($order);
            }
        }

        return $fulfillment->refresh();
    }

    private function assertStaffTransition(OrderFulfillment $fulfillment, FulfillmentStatus $status): void
    {
        if ($status === FulfillmentStatus::Cancelled) {
            return;
        }

        $allowed = $fulfillment->status->staffNext();

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'That status change is not allowed.',
            ]);
        }
    }

    private function releaseReservations(OrderFulfillment $fulfillment, ?User $actor): void
    {
        $order = $fulfillment->order()->with('items')->first();

        if ($order === null) {
            return;
        }

        $ids = [];
        $targets = [];
        $destQty = [];

        foreach ($order->items as $item) {
            $key = $item->product_id.':'.($item->variant_id ?? '0');
            $destQty[$key] = $item->quantity;
        }

        foreach ($fulfillment->stockTransfers as $transfer) {
            $transfer->loadMissing('items');

            foreach ($transfer->items as $item) {
                $key = $item->product_id.':'.($item->variant_id ?? '0');

                if ($transfer->status === StockTransferStatus::Received) {
                    continue;
                }

                $destQty[$key] = ($destQty[$key] ?? 0) - $item->quantity;

                $inventory = $this->findInventory($transfer->source_branch_id, $item->product_id, $item->variant_id);

                if ($inventory !== null) {
                    $ids[] = $inventory->id;
                    $targets[] = [$inventory->id, $item->quantity];
                }
            }
        }

        foreach ($order->items as $item) {
            $key = $item->product_id.':'.($item->variant_id ?? '0');
            $local = max(0, $destQty[$key] ?? 0);

            if ($local <= 0) {
                continue;
            }

            $inventory = $this->findInventory($fulfillment->branch_id, $item->product_id, $item->variant_id);

            if ($inventory === null) {
                continue;
            }

            $ids[] = $inventory->id;
            $targets[] = [$inventory->id, $local];
        }

        $locked = $this->reservations->lockOrdered($ids);

        foreach ($targets as [$id, $quantity]) {
            if (! isset($locked[$id]) || $locked[$id]->reserved_quantity < $quantity) {
                continue;
            }

            $this->reservations->release($locked[$id], $quantity, $order, $actor);
        }
    }

    private function findInventory(int $branchId, int $productId, ?int $variantId): ?Inventory
    {
        $query = Inventory::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId);

        if ($variantId === null) {
            $query->whereNull('variant_id');
        } else {
            $query->where('variant_id', $variantId);
        }

        return $query->first();
    }
}
