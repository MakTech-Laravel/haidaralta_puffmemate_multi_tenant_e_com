<?php

namespace App\Exports;

use App\Models\DriverInventoryTransaction;
use App\Support\DateTimeFormat;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DriverInventoryTransactionExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  Builder<DriverInventoryTransaction>  $query
     */
    public function __construct(
        private Builder $query,
    ) {}

    /**
     * @return Builder<DriverInventoryTransaction>
     */
    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Driver',
            'Phone',
            'Product',
            'SKU',
            'Type',
            'Quantity',
            'Before',
            'After',
            'When',
            'By',
        ];
    }

    /**
     * @param  DriverInventoryTransaction  $row
     * @return list<mixed>
     */
    public function map(mixed $row): array
    {
        return [
            $row->driver?->name,
            $row->driver?->phone,
            $row->product?->name,
            $row->product?->sku,
            $row->type->label(),
            $row->quantity,
            $row->before_quantity,
            $row->after_quantity,
            DateTimeFormat::dateTime($row->created_at),
            $row->creator?->name,
        ];
    }
}
