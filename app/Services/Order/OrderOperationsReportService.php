<?php

namespace App\Services\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\DeliveryAttempt;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\StockTransfer;
use App\Models\User;

class OrderOperationsReportService
{
    /**
     * @return array<string, mixed>
     */
    public function platform(): array
    {
        return [
            'orders' => Order::query()->count(),
            'delivered' => Order::query()->where('status', FulfillmentStatus::Delivered)->count(),
            'sales' => (float) Order::query()->where('status', FulfillmentStatus::Delivered)->sum('total_amount'),
            'transferCost' => (float) StockTransfer::query()->sum('estimated_cost'),
            'payments' => $this->paymentTotals(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function branch(Branch $branch): array
    {
        $fulfillmentIds = OrderFulfillment::query()->where('branch_id', $branch->id)->pluck('id');

        return [
            'fulfillments' => $fulfillmentIds->count(),
            'delivered' => OrderFulfillment::query()
                ->where('branch_id', $branch->id)
                ->where('status', FulfillmentStatus::Delivered)
                ->count(),
            'sales' => (float) Order::query()
                ->whereHas('fulfillment', fn ($query) => $query->where('branch_id', $branch->id)->where('status', FulfillmentStatus::Delivered))
                ->sum('total_amount'),
            'outboundTransfers' => StockTransfer::query()->where('source_branch_id', $branch->id)->count(),
            'inboundTransfers' => StockTransfer::query()->where('destination_branch_id', $branch->id)->count(),
            'outboundTransferCost' => (float) StockTransfer::query()->where('source_branch_id', $branch->id)->sum('estimated_cost'),
            'unverifiedCollections' => Payment::query()
                ->where('status', PaymentStatus::Collected)
                ->whereHas('order.fulfillment', fn ($query) => $query->where('branch_id', $branch->id))
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function driver(User $driver): array
    {
        return [
            'delivered' => OrderFulfillment::query()
                ->where('driver_id', $driver->id)
                ->where('status', FulfillmentStatus::Delivered)
                ->count(),
            'failedAttempts' => DeliveryAttempt::query()->where('driver_id', $driver->id)->count(),
            'collected' => (float) Payment::query()
                ->where('collected_by', $driver->id)
                ->whereIn('status', [PaymentStatus::Collected, PaymentStatus::Verified])
                ->sum('amount'),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function paymentTotals(): array
    {
        $totals = [];

        foreach (PaymentStatus::cases() as $status) {
            $totals[$status->value] = (float) Payment::query()->where('status', $status)->sum('amount');
        }

        return $totals;
    }
}
