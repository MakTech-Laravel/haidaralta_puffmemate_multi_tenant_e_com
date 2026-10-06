<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;

class OrderReportsExport implements FromArray
{
    use Exportable;

    /**
     * @param  array{
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
        $rows = [
            ['Order Reports'],
            ['Period', $this->dashboard['period']],
            [],
            ['Summary'],
            ['Metric', 'Value'],
            ['Total orders', $summary['orders']],
            ['Delivered', $summary['delivered']],
            ['Waiting to deliver', $summary['waiting']],
            ['Could not deliver', $summary['failed']],
            ['Money from sales', $summary['sales']],
            ['Cash collected on delivery', $summary['cod_collected']],
            [],
            ['What sells most'],
            ['Product', 'Units sold'],
        ];

        foreach ($this->dashboard['products'] as $product) {
            $rows[] = [$product['name'], $product['sold']];
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

        return $rows;
    }
}
