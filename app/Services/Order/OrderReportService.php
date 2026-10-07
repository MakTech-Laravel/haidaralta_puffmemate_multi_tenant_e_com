<?php

namespace App\Services\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\DeliveryAttempt;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\StockTransfer;
use App\Models\User;
use Carbon\CarbonInterface;

class OrderReportService
{
    /**
     * @return list<string>
     */
    public static function periods(): array
    {
        return ['7d', '30d', '90d', '1y'];
    }

    public function resolvePeriod(?string $period): string
    {
        $period = is_string($period) ? trim($period) : '';

        return in_array($period, self::periods(), true) ? $period : '30d';
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function periodRange(string $period): array
    {
        $period = $this->resolvePeriod($period);
        $end = now()->endOfDay();

        $start = match ($period) {
            '7d' => now()->subDays(7)->startOfDay(),
            '90d' => now()->subDays(90)->startOfDay(),
            '1y' => now()->subYear()->startOfDay(),
            default => now()->subDays(30)->startOfDay(),
        };

        return [$start, $end];
    }

    /**
     * @return array{
     *     period: string,
     *     summary: array{
     *         orders: int,
     *         delivered: int,
     *         waiting: int,
     *         failed: int,
     *         sales: float,
     *         cod_collected: float
     *     },
     *     products: list<array{name: string, sold: int}>,
     *     branches: list<array{id: int, branch: string, name: string, sales: float}>,
     *     drivers: list<array{id: int, driver: string, name: string, deliveries: int}>
     * }
     */
    public function platformDashboard(string $period = '30d'): array
    {
        $period = $this->resolvePeriod($period);
        [$from, $to] = $this->periodRange($period);

        return [
            'period' => $period,
            'summary' => $this->platformSummary($from, $to),
            'products' => $this->topProducts($from, $to),
            'branches' => $this->topBranches($from, $to),
            'drivers' => $this->topDrivers($from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function platform(): array
    {
        return [
            'orders' => Order::query()->count(),
            'delivered' => Order::query()->where('status', OrderStatus::Delivered)->count(),
            'sales' => (float) Order::query()->where('status', OrderStatus::Delivered)->sum('total_amount'),
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
        $collections = Payment::query()
            ->where('collected_by', $driver->id)
            ->whereIn('status', [PaymentStatus::Collected, PaymentStatus::Verified]);

        return [
            'delivered' => OrderFulfillment::query()
                ->where('driver_id', $driver->id)
                ->where('status', FulfillmentStatus::Delivered)
                ->count(),
            'failedAttempts' => DeliveryAttempt::query()->where('driver_id', $driver->id)->count(),
            'collected' => (float) $collections->clone()->sum('amount'),
            'collectedCod' => (float) $collections->clone()->where('gateway', 'cod')->sum('amount'),
            'collectedPos' => (float) $collections->clone()->where('gateway', 'pos')->sum('amount'),
        ];
    }

    /**
     * @return array{
     *     orders: int,
     *     delivered: int,
     *     waiting: int,
     *     failed: int,
     *     sales: float,
     *     cod_collected: float
     * }
     */
    private function platformSummary(CarbonInterface $from, CarbonInterface $to): array
    {
        $fulfillmentsInPeriod = OrderFulfillment::query()
            ->whereBetween('created_at', [$from, $to]);

        return [
            'orders' => Order::query()->whereBetween('created_at', [$from, $to])->count(),
            'delivered' => $fulfillmentsInPeriod
                ->clone()
                ->where('status', FulfillmentStatus::Delivered)
                ->count(),
            'waiting' => $fulfillmentsInPeriod
                ->clone()
                ->whereNotIn('status', [
                    FulfillmentStatus::Delivered,
                    FulfillmentStatus::Cancelled,
                    FulfillmentStatus::DeliveryFailed,
                ])
                ->count(),
            'failed' => $fulfillmentsInPeriod
                ->clone()
                ->where('status', FulfillmentStatus::DeliveryFailed)
                ->count(),
            'sales' => (float) Order::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', OrderStatus::Delivered)
                ->sum('total_amount'),
            'cod_collected' => (float) Payment::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('gateway', 'cod')
                ->whereIn('status', [PaymentStatus::Collected, PaymentStatus::Verified])
                ->sum('amount'),
        ];
    }

    /**
     * @return list<array{name: string, sold: int}>
     */
    private function topProducts(CarbonInterface $from, CarbonInterface $to): array
    {
        return OrderItem::query()
            ->selectRaw('product_name_snapshot as name, SUM(quantity) as sold')
            ->whereHas('order', fn ($query) => $query->whereBetween('created_at', [$from, $to]))
            ->groupBy('product_name_snapshot')
            ->orderByDesc('sold')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'name' => filled($row->name) ? (string) $row->name : 'Product',
                'sold' => (int) $row->sold,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, branch: string, name: string, sales: float}>
     */
    private function topBranches(CarbonInterface $from, CarbonInterface $to): array
    {
        return OrderFulfillment::query()
            ->selectRaw('order_fulfillments.branch_id, branches.name as branch_name, branches.slug as branch_slug, SUM(orders.total_amount) as sales')
            ->join('orders', 'orders.id', '=', 'order_fulfillments.order_id')
            ->join('branches', 'branches.id', '=', 'order_fulfillments.branch_id')
            ->whereBetween('order_fulfillments.created_at', [$from, $to])
            ->where('order_fulfillments.status', FulfillmentStatus::Delivered)
            ->groupBy('order_fulfillments.branch_id', 'branches.name', 'branches.slug')
            ->orderByDesc('sales')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->branch_id,
                'branch' => (string) $row->branch_slug,
                'name' => (string) $row->branch_name,
                'sales' => (float) $row->sales,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, driver: string, name: string, deliveries: int}>
     */
    private function topDrivers(CarbonInterface $from, CarbonInterface $to): array
    {
        return OrderFulfillment::query()
            ->selectRaw('order_fulfillments.driver_id, users.name as driver_name, COUNT(*) as deliveries')
            ->join('users', 'users.id', '=', 'order_fulfillments.driver_id')
            ->whereBetween('order_fulfillments.created_at', [$from, $to])
            ->where('order_fulfillments.status', FulfillmentStatus::Delivered)
            ->whereNotNull('order_fulfillments.driver_id')
            ->groupBy('order_fulfillments.driver_id', 'users.name')
            ->orderByDesc('deliveries')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->driver_id,
                'driver' => 'driver-'.$row->driver_id,
                'name' => (string) $row->driver_name,
                'deliveries' => (int) $row->deliveries,
            ])
            ->values()
            ->all();
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
