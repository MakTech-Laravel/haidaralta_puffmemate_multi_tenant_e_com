<?php

namespace App\Exports;

use App\Enums\UserType;
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
        private bool $includeBranch = false,
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
        $headings = ['Order ID'];

        if ($this->includeBranch) {
            $headings[] = 'Branch';
        }

        return [
            ...$headings,
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
        $values = [
            $row->fulfillment?->fulfillment_number,
        ];

        if ($this->includeBranch) {
            $values[] = $row->fulfillment?->branch?->name;
        }

        return [
            ...$values,
            $row->fromDriver?->name,
            $row->fromDriver?->phone,
            $row->toDriver?->name,
            $row->toDriver?->phone,
            $row->reason,
            DateTimeFormat::dateTime($row->created_at),
            $this->changedByLabel($row),
        ];
    }

    private function changedByLabel(OrderDriverAssignmentHistory $row): ?string
    {
        $changer = $row->changer;

        if ($changer === null) {
            return null;
        }

        if ($changer->user_type === UserType::PLATFORM) {
            return 'System';
        }

        return $changer->name;
    }
}
