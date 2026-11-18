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
use Carbon\Carbon;
use Carbon\CarbonInterface;

class OrderReportService
{
    public function resolveMonth(?string $month): string
    {
        $month = is_string($month) ? trim($month) : '';

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1) {
            return $month;
        }

        return now()->format('Y-m');
    }

    /**
     * Null when the compare month is empty, invalid, or equal to the selected month.
     */
    public function resolveCompareMonth(?string $compare, string $month): ?string
    {
        $compare = is_string($compare) ? trim($compare) : '';

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $compare) !== 1 || $compare === $month) {
            return null;
        }

        return $compare;
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function monthRange(string $month): array
    {
        $start = Carbon::createFromFormat('!Y-m', $this->resolveMonth($month))->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    /**
     * @return list<string>
     */
    public static function ranges(): array
    {
        return ['7d', '30d', '90d', '1y'];
    }

    public function resolveRange(?string $range): ?string
    {
        $range = is_string($range) ? trim($range) : '';

        return in_array($range, self::ranges(), true) ? $range : null;
    }

    /**
     * Rolling window ending today, plus the equally long window right before it.
     *
     * @return array{label: string, previous_label: string, from: Carbon, to: Carbon, previous_from: Carbon, previous_to: Carbon}
     */
    private function rangeWindow(string $range): array
    {
        $to = now()->endOfDay();

        [$from, $previousFrom, $label] = match ($range) {
            '7d' => [now()->subDays(7)->startOfDay(), now()->subDays(14)->startOfDay(), '7 days'],
            '90d' => [now()->subDays(90)->startOfDay(), now()->subDays(180)->startOfDay(), '90 days'],
            '1y' => [now()->subYear()->startOfDay(), now()->subYears(2)->startOfDay(), '1 year'],
            default => [now()->subDays(30)->startOfDay(), now()->subDays(60)->startOfDay(), '30 days'],
        };

        return [
            'label' => 'Last '.$label,
            'previous_label' => 'Previous '.$label,
            'from' => $from,
            'to' => $to,
            'previous_from' => $previousFrom,
            'previous_to' => $from->copy()->subSecond(),
        ];
    }

    private function monthLabel(string $month): string
    {
        return Carbon::createFromFormat('!Y-m', $month)->format('F Y');
    }

    /**
     * @return array{
     *     month: string|null,
     *     range: string|null,
     *     label: string,
     *     summary: array{
     *         orders: int,
     *         delivered: int,
     *         waiting: int,
     *         failed: int,
     *         cancelled: int,
     *         refunded: int,
     *         refunded_amount: float,
     *         sales: float,
     *         payments_collected: float,
     *         cod_collected: float
     *     },
     *     products: list<array{name: string, sold: int}>,
     *     branches: list<array{id: int, branch: string, name: string, sales: float}>,
     *     drivers: list<array{id: int, driver: string, name: string, deliveries: int}>,
     *     timeline: list<array{label: string, date: string, orders: int, delivered: int}>,
     *     compare: array{month: string|null, range: string|null, label: string, summary: array<string, int|float>, products: list<array{name: string, sold: int}>, branches: list<array{id: int, branch: string, name: string, sales: float}>, drivers: list<array{id: int, driver: string, name: string, deliveries: int}>, product_comparison: list<array{name: string, current: int, previous: int}>, timeline: list<array{label: string, date: string, orders: int, delivered: int}>}|null,
     *     trend: array{label: string, summary: array<string, int|float>}
     * }
     */
    public function platformDashboard(?string $month = null, ?string $compare = null, ?string $range = null): array
    {
        $range = $this->resolveRange($range);
        $compare = is_string($compare) ? trim($compare) : null;
        $comparison = null;
        $baseline = null;

        if ($range !== null) {
            $window = $this->rangeWindow($range);
            $month = null;
            $from = $window['from'];
            $to = $window['to'];
            $label = $window['label'];
            $baseline = [
                'label' => $window['previous_label'],
                'from' => $window['previous_from'],
                'to' => $window['previous_to'],
            ];

            if ($compare === 'previous') {
                $comparison = [
                    'month' => null,
                    'range' => $range,
                    'label' => $window['previous_label'],
                    'from' => $window['previous_from'],
                    'to' => $window['previous_to'],
                ];
            }
        } else {
            $month = $this->resolveMonth($month);
            [$from, $to] = $this->monthRange($month);
            $label = $this->monthLabel($month);
            $previousMonth = Carbon::createFromFormat('!Y-m', $month)->subMonthNoOverflow()->format('Y-m');
            [$previousFrom, $previousTo] = $this->monthRange($previousMonth);
            $baseline = [
                'label' => $this->monthLabel($previousMonth),
                'from' => $previousFrom,
                'to' => $previousTo,
            ];
            $compareMonth = $this->resolveCompareMonth($compare, $month);

            if ($compareMonth !== null) {
                [$compareFrom, $compareTo] = $this->monthRange($compareMonth);
                $comparison = [
                    'month' => $compareMonth,
                    'range' => null,
                    'label' => $this->monthLabel($compareMonth),
                    'from' => $compareFrom,
                    'to' => $compareTo,
                ];
            }
        }

        $compareBlock = null;

        if ($comparison !== null) {
            $compareBlock = [
                'month' => $comparison['month'],
                'range' => $comparison['range'],
                'label' => $comparison['label'],
                'summary' => $this->platformSummary($comparison['from'], $comparison['to']),
                'products' => $this->topProducts($comparison['from'], $comparison['to']),
                'branches' => $this->topBranches($comparison['from'], $comparison['to']),
                'drivers' => $this->topDrivers($comparison['from'], $comparison['to']),
                'product_comparison' => $this->topProductsComparison($from, $to, $comparison['from'], $comparison['to']),
                'timeline' => $this->ordersTimeline($comparison['from'], $comparison['to']),
            ];
        }

        // Trend baseline for the summary cards: the explicit compare period, else the previous period.
        $trend = [
            'label' => $compareBlock['label'] ?? $baseline['label'],
            'summary' => $compareBlock['summary'] ?? $this->platformSummary($baseline['from'], $baseline['to']),
        ];

        return [
            'month' => $month,
            'range' => $range,
            'label' => $label,
            'summary' => $this->platformSummary($from, $to),
            'products' => $this->topProducts($from, $to),
            'branches' => $this->topBranches($from, $to),
            'drivers' => $this->topDrivers($from, $to),
            'timeline' => $this->ordersTimeline($from, $to),
            'compare' => $compareBlock,
            'trend' => $trend,
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
     *     cancelled: int,
     *     refunded: int,
     *     refunded_amount: float,
     *     sales: float,
     *     payments_collected: float,
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
            'cancelled' => Order::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', OrderStatus::Cancelled)
                ->count(),
            'refunded' => Payment::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', PaymentStatus::Refunded)
                ->distinct()
                ->count('order_id'),
            'refunded_amount' => (float) Payment::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', PaymentStatus::Refunded)
                ->sum('amount'),
            'sales' => (float) Order::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', OrderStatus::Delivered)
                ->sum('total_amount'),
            'payments_collected' => (float) Payment::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereIn('status', [PaymentStatus::Collected, PaymentStatus::Verified])
                ->sum('amount'),
            'cod_collected' => (float) Payment::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('gateway', 'cod')
                ->whereIn('status', [PaymentStatus::Collected, PaymentStatus::Verified])
                ->sum('amount'),
        ];
    }

    /**
     * Orders and deliveries per day (per calendar month for windows longer
     * than 92 days), zero-filled so a line chart stays continuous.
     *
     * Orders use the same definition as the "Total orders" card and delivered
     * the same as the "Delivered" card (fulfillment created in the window).
     *
     * @return list<array{label: string, date: string, orders: int, delivered: int}>
     */
    private function ordersTimeline(CarbonInterface $from, CarbonInterface $to): array
    {
        $orders = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $delivered = OrderFulfillment::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', FulfillmentStatus::Delivered)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $monthly = $from->diffInDays($to) > 92;
        $buckets = [];

        for ($day = Carbon::instance($from)->startOfDay(); $day->lte($to); $day->addDay()) {
            $key = $monthly ? $day->format('Y-m') : $day->format('Y-m-d');
            $dayKey = $day->format('Y-m-d');

            $buckets[$key] ??= [
                'label' => $monthly ? $day->format('M Y') : $day->format('M j'),
                'date' => $monthly ? $day->copy()->startOfMonth()->toDateString() : $dayKey,
                'orders' => 0,
                'delivered' => 0,
            ];

            $buckets[$key]['orders'] += (int) ($orders[$dayKey] ?? 0);
            $buckets[$key]['delivered'] += (int) ($delivered[$dayKey] ?? 0);
        }

        return array_values($buckets);
    }

    /**
     * Top 5 products by combined units across both months, each carrying its
     * units for the selected month and for the compare month.
     *
     * @return list<array{name: string, current: int, previous: int}>
     */
    private function topProductsComparison(
        CarbonInterface $from,
        CarbonInterface $to,
        CarbonInterface $compareFrom,
        CarbonInterface $compareTo,
    ): array {
        $unitsIn = 'SUM(CASE WHEN orders.created_at BETWEEN ? AND ? THEN order_items.quantity ELSE 0 END)';
        $bindings = [$from, $to, $compareFrom, $compareTo];

        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where(function ($query) use ($from, $to, $compareFrom, $compareTo): void {
                $query->whereBetween('orders.created_at', [$from, $to])
                    ->orWhereBetween('orders.created_at', [$compareFrom, $compareTo]);
            })
            ->selectRaw(
                "order_items.product_name_snapshot as name, {$unitsIn} as current_units, {$unitsIn} as previous_units",
                $bindings,
            )
            ->groupBy('order_items.product_name_snapshot')
            // Repeat the aggregates: PostgreSQL cannot use select aliases inside an ORDER BY expression.
            ->orderByRaw("({$unitsIn} + {$unitsIn}) desc", $bindings)
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'name' => filled($row->name) ? (string) $row->name : 'Product',
                'current' => (int) $row->current_units,
                'previous' => (int) $row->previous_units,
            ])
            ->values()
            ->all();
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
