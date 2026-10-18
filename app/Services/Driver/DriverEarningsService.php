<?php

namespace App\Services\Driver;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\User;
use App\Support\DateTimeFormat;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DriverEarningsService
{
    /**
     * @return list<string>
     */
    public static function periods(): array
    {
        return ['today', '7d', '30d'];
    }

    public function resolvePeriod(?string $period): string
    {
        $period = is_string($period) ? trim($period) : '';

        return in_array($period, self::periods(), true) ? $period : 'today';
    }

    /**
     * @return array{
     *     period: string,
     *     periodLabel: string,
     *     totalLabel: string,
     *     drops: int,
     *     tipsLabel: string,
     *     perStopLabel: string,
     *     previousTotalLabel: string,
     *     codHeldLabel: string,
     *     deliveries: int,
     *     performance: array{
     *         previousTotalLabel: string,
     *         changePercent: float|null,
     *         changeAmountLabel: string,
     *         direction: string,
     *         comparisonLabel: string
     *     },
     *     deliveriesPerformance: array{
     *         previousTotalLabel: string,
     *         changePercent: float|null,
     *         changeAmountLabel: string,
     *         direction: string,
     *         comparisonLabel: string
     *     },
     *     days: list<array{label: string, totalLabel: string, entries: list<array{id: string, customerName: string, orderNumber: string, areaLabel: string, amountLabel: string, tipLabel: string, paymentMethod: string}>}>
     * }
     */
    public function summary(User $driver, ?string $period = null): array
    {
        $period = $this->resolvePeriod($period);
        [$from, $to] = $this->periodRange($period);
        [$previousFrom, $previousTo] = $this->previousPeriodRange($period);

        $payments = $this->collections($driver, $from, $to);
        $previousPayments = $this->collections($driver, $previousFrom, $previousTo);

        $total = (float) $payments->sum('amount');
        $previousTotal = (float) $previousPayments->sum('amount');
        $cod = (float) $payments->where('gateway', 'cod')->sum('amount');
        $drops = $payments->count();
        $deliveries = $this->deliveredCount($driver, $from, $to);
        $previousDeliveries = $this->deliveredCount($driver, $previousFrom, $previousTo);

        return [
            'period' => $period,
            'periodLabel' => $this->periodLabel($period),
            'totalLabel' => $this->money($total),
            'drops' => $drops,
            'tipsLabel' => $this->money(0),
            'perStopLabel' => $drops === 0 ? $this->money(0) : $this->money($total / $drops),
            'previousTotalLabel' => $this->money($previousTotal),
            'codHeldLabel' => $this->money($cod),
            'deliveries' => $deliveries,
            'performance' => $this->performance($total, $previousTotal, $period, fn (float $value): string => $this->money($value)),
            'deliveriesPerformance' => $this->performance(
                (float) $deliveries,
                (float) $previousDeliveries,
                $period,
                fn (float $value): string => (string) (int) $value,
            ),
            'days' => $this->groupByDay($payments),
        ];
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function periodRange(string $period): array
    {
        $period = $this->resolvePeriod($period);
        $end = DateTimeFormat::now()->endOfDay();

        $start = match ($period) {
            '7d' => DateTimeFormat::now()->subDays(6)->startOfDay(),
            '30d' => DateTimeFormat::now()->subDays(29)->startOfDay(),
            default => DateTimeFormat::now()->startOfDay(),
        };

        return [$start, $end];
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function previousPeriodRange(string $period): array
    {
        $period = $this->resolvePeriod($period);

        return match ($period) {
            '7d' => [
                DateTimeFormat::now()->subDays(13)->startOfDay(),
                DateTimeFormat::now()->subDays(7)->endOfDay(),
            ],
            '30d' => [
                DateTimeFormat::now()->subDays(59)->startOfDay(),
                DateTimeFormat::now()->subDays(30)->endOfDay(),
            ],
            default => [
                DateTimeFormat::now()->subDay()->startOfDay(),
                DateTimeFormat::now()->subDay()->endOfDay(),
            ],
        };
    }

    /**
     * @return Collection<int, Payment>
     */
    private function collections(User $driver, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Payment::query()
            ->where('collected_by', $driver->id)
            ->whereIn('status', [PaymentStatus::Collected, PaymentStatus::Verified])
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('collected_at', [$from, $to])
                    ->orWhere(function ($fallback) use ($from, $to): void {
                        $fallback->whereNull('collected_at')
                            ->whereBetween('paid_at', [$from, $to]);
                    });
            })
            ->with('order')
            ->latest('collected_at')
            ->get();
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return list<array{label: string, totalLabel: string, entries: list<array{id: string, customerName: string, orderNumber: string, areaLabel: string, amountLabel: string, tipLabel: string, paymentMethod: string}>}>
     */
    private function groupByDay(Collection $payments): array
    {
        if ($payments->isEmpty()) {
            return [];
        }

        return $payments
            ->groupBy(function (Payment $payment): string {
                $moment = $payment->collected_at ?? $payment->paid_at;

                return $moment?->toDateString() ?? 'unknown';
            })
            ->map(function (Collection $dayPayments, string $dateKey): array {
                $total = (float) $dayPayments->sum('amount');
                $label = $dateKey === 'unknown'
                    ? 'Collections'
                    : (DateTimeFormat::date($dateKey) ?? $dateKey);

                return [
                    'label' => $label,
                    'totalLabel' => $this->money($total),
                    'entries' => $dayPayments->map(fn (Payment $payment): array => [
                        'id' => (string) ($payment->order?->order_number ?? $payment->id),
                        'customerName' => $payment->order?->shipping_address_snapshot['name'] ?? 'Customer',
                        'orderNumber' => $payment->order?->order_number ?? '',
                        'areaLabel' => $payment->order?->shipping_address_snapshot['city'] ?? '',
                        'amountLabel' => $this->money((float) $payment->amount),
                        'tipLabel' => $this->money(0),
                        'paymentMethod' => $payment->gateway === 'pos' ? 'paid' : 'cod',
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function deliveredCount(User $driver, CarbonInterface $from, CarbonInterface $to): int
    {
        return OrderFulfillment::query()
            ->where('driver_id', $driver->id)
            ->where('status', FulfillmentStatus::Delivered)
            ->whereBetween('updated_at', [$from, $to])
            ->count();
    }

    /**
     * @param  callable(float): string  $formatPrevious
     * @return array{previousTotalLabel: string, changePercent: float|null, changeAmountLabel: string, direction: string, comparisonLabel: string}
     */
    private function performance(float $total, float $previousTotal, string $period, callable $formatPrevious): array
    {
        $comparisonLabel = match ($period) {
            '7d' => 'vs previous 7 days',
            '30d' => 'vs previous 30 days',
            default => 'vs yesterday',
        };

        $changeAmountLabel = $this->signedChangeLabel($total - $previousTotal, $formatPrevious);

        if ($previousTotal <= 0.0) {
            return [
                'previousTotalLabel' => $formatPrevious($previousTotal),
                'changePercent' => $total > 0.0 ? 100.0 : null,
                'changeAmountLabel' => $changeAmountLabel,
                'direction' => $total > 0.0 ? 'new' : 'flat',
                'comparisonLabel' => $comparisonLabel,
            ];
        }

        $changePercent = round((($total - $previousTotal) / $previousTotal) * 100, 1);
        $direction = match (true) {
            $changePercent > 0 => 'up',
            $changePercent < 0 => 'down',
            default => 'flat',
        };

        return [
            'previousTotalLabel' => $formatPrevious($previousTotal),
            'changePercent' => abs($changePercent),
            'changeAmountLabel' => $changeAmountLabel,
            'direction' => $direction,
            'comparisonLabel' => $comparisonLabel,
        ];
    }

    /**
     * @param  callable(float): string  $formatAbsolute
     */
    private function signedChangeLabel(float $change, callable $formatAbsolute): string
    {
        if (abs($change) < 0.005) {
            return $formatAbsolute(0.0);
        }

        $sign = $change > 0.0 ? '+' : '-';

        return $sign.$formatAbsolute(abs($change));
    }

    private function periodLabel(string $period): string
    {
        return match ($period) {
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            default => 'Today',
        };
    }

    private function money(float $amount): string
    {
        return '$'.number_format($amount, 2);
    }
}
