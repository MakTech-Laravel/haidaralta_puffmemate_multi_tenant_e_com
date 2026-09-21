<?php

namespace App\Exports;

use App\DataTables\BranchProductPriceHistoryDataTable;
use App\Models\BranchProductPriceHistory;
use App\Support\DateTimeFormat;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PriceHistoryExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  Builder<BranchProductPriceHistory>  $query
     */
    public function __construct(
        private Builder $query,
        private bool $includeBranch = true,
    ) {}

    /**
     * @return Builder<BranchProductPriceHistory>
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
        $headings = ['Product', 'SKU', 'Variant'];

        if ($this->includeBranch) {
            $headings[] = 'Branch';
        }

        return [
            ...$headings,
            'Source',
            'Changes',
            'When',
            'By',
        ];
    }

    /**
     * @param  BranchProductPriceHistory  $row
     * @return list<mixed>
     */
    public function map(mixed $row): array
    {
        $values = [
            $row->product?->name,
            $row->product?->sku,
            $row->variant?->name,
        ];

        if ($this->includeBranch) {
            $values[] = $row->branch?->name;
        }

        return [
            ...$values,
            $row->source->label(),
            BranchProductPriceHistoryDataTable::flattenChanges($row->changes ?? []),
            DateTimeFormat::dateTime($row->created_at),
            $row->creator?->name,
        ];
    }
}
