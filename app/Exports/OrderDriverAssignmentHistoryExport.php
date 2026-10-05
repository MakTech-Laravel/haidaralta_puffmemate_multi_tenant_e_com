<?php

namespace App\Exports;

use App\Models\OrderDriverAssignmentHistory;
use App\Support\DateTimeFormat;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderDriverAssignmentHistoryExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  Builder<OrderDriverAssignmentHistory>  $query
     */
    public function __construct(
        private Builder $query,
    ) {}

    /**
     * @return Builder<OrderDriverAssignmentHistory>
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
            'Order ID',
            'Previous driver',
            'Previous phone',
            'Assigned driver',
            'Assigned phone',
            'Reason',
            'Changed at',
            'Changed by',
        ];
    }

    /**
     * @param  OrderDriverAssignmentHistory  $row
     * @return list<mixed>
     */
    public function map(mixed $row): array
    {
        return [
            $row->fulfillment?->fulfillment_number,
            $row->fromDriver?->name,
            $row->fromDriver?->phone,
            $row->toDriver?->name,
            $row->toDriver?->phone,
            $row->reason,
            DateTimeFormat::dateTime($row->created_at),
            $row->changer?->name,
        ];
    }
}
