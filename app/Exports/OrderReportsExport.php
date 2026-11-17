<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;

class OrderReportsExport implements FromArray
{
    use Exportable;

    /**
     * @var array<string, string>
     */
    private const METRICS = [
        'orders' => 'Total orders',
        'payments_collected' => 'Total payment collected',
        'cancelled' => 'Cancelled orders',
        'refunded' => 'Refunded orders',
        'refunded_amount' => 'Refunded amount',
        'delivered' => 'Delivered',
        'waiting' => 'Waiting to deliver',
        'failed' => 'Could not deliver',
        'sales' => 'Money from sales',
        'cod_collected' => 'Cash collected on delivery',
    ];

    /**
     * @param  array{
     *     month: string,
     *     summary: array<string, int|float>,
     *     products: list<array{name: string, sold: int}>,
     *     branches: list<array{id: int, branch: string, name: string, sales: float}>,
     *     drivers: list<array{id: int, driver: string, name: string, deliveries: int}>,
     *     compare: array{month: string, summary: array<string, int|float>, products: list<array{name: string, sold: int}>, branches: list<array{id: int, branch: string, name: string, sales: float}>, drivers: list<array{id: int, driver: string, name: string, deliveries: int}>}|null
     * }  $dashboard
     */
    public function __construct(
        private array $dashboard,
    ) {}

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        $summary = $this->dashboard['summary'];
        $compare = $this->dashboard['compare'];

        $rows = [
            ['Order Reports'],
            ['Month', $this->dashboard['month']],
        ];

        if ($compare !== null) {
            $rows[] = ['Compare month', $compare['month']];
        }

        $rows[] = [];
        $rows[] = ['Summary'];
        $rows[] = $compare === null
            ? ['Metric', 'Value']
            : ['Metric', $this->dashboard['month'], $compare['month'], 'Change', 'Change %'];

        foreach (self::METRICS as $key => $label) {
            $value = $summary[$key] ?? 0;

            if ($compare === null) {
                $rows[] = [$label, $value];

                continue;
            }

            $previous = $compare['summary'][$key] ?? 0;
            $change = $value - $previous;
            $percent = $previous == 0 ? null : round($change / $previous * 100, 1);

            $rows[] = [$label, $value, $previous, $change, $percent === null ? 'n/a' : $percent.'%'];
        }

        $rows[] = [];
        $rows[] = ['What sells most'];
        $rows[] = ['Product', 'Units sold'];

        foreach ($this->dashboard['products'] as $product) {
            $rows[] = [$product['name'], $product['sold']];
        }

        if ($compare !== null) {
            $rows[] = [];
            $rows[] = ['What sells most ('.$compare['month'].')'];
            $rows[] = ['Product', 'Units sold'];

            foreach ($compare['products'] as $product) {
                $rows[] = [$product['name'], $product['sold']];
            }
        }

        $rows[] = [];
        $rows[] = ['Which branches sell most'];
        $rows[] = ['Branch', 'Sales'];

        foreach ($this->dashboard['branches'] as $branch) {
            $rows[] = [$branch['name'], $branch['sales']];
        }

        $rows[] = [];
        $rows[] = ['Which drivers deliver most'];
        $rows[] = ['Driver', 'Deliveries'];

        foreach ($this->dashboard['drivers'] as $driver) {
            $rows[] = [$driver['name'], $driver['deliveries']];
        }

        if ($compare !== null) {
            $rows[] = [];
            $rows[] = ['Which branches sell most ('.$compare['month'].')'];
            $rows[] = ['Branch', 'Sales'];

            foreach ($compare['branches'] as $branch) {
                $rows[] = [$branch['name'], $branch['sales']];
            }

            $rows[] = [];
            $rows[] = ['Which drivers deliver most ('.$compare['month'].')'];
            $rows[] = ['Driver', 'Deliveries'];

            foreach ($compare['drivers'] as $driver) {
                $rows[] = [$driver['name'], $driver['deliveries']];
            }
        }

        return $rows;
    }
}
