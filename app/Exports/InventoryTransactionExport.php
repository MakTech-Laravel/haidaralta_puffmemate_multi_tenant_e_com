<?php

namespace App\Exports;

use App\Models\InventoryTransaction;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryTransactionExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  Builder<InventoryTransaction>  $query
     */
    public function __construct(
        private Builder $query,
        private bool $includeBranch = true,
    ) {}

    /**
     * @return Builder<InventoryTransaction>
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
        $headings = ['Product', 'SKU'];

        if ($this->includeBranch) {
            $headings[] = 'Branch';
        }

        return [
            ...$headings,
            'Type',
            'Reason',
            'Quantity',
            'Buying',
            'Before',
            'After',
            'When',
            'By',
        ];
    }

    /**
     * @param  InventoryTransaction  $row
     * @return list<mixed>
     */
    public function map(mixed $row): array
    {
        $values = [
            $row->product?->name,
            $row->product?->sku,
        ];

        if ($this->includeBranch) {
            $values[] = $row->branch?->name;
        }

        return [
            ...$values,
            $row->type->label(),
            $row->reasonLabel(),
            $row->quantity,
            $row->unit_cost === null
                ? null
                : number_format((float) $row->unit_cost, 2, '.', ''),
            $row->before_quantity,
            $row->after_quantity,
            $row->created_at?->format('Y-m-d H:i:s'),
            $row->creator?->name,
        ];
    }
}
