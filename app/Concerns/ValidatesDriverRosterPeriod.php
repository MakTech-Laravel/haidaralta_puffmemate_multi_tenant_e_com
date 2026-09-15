<?php

namespace App\Concerns;

use App\Enums\DriverRosterStatus;
use App\Models\Branch;
use Illuminate\Validation\Rule;

trait ValidatesDriverRosterPeriod
{
    /**
     * @return array<string, mixed>
     */
    protected function rosterPeriodRules(string $prefix = '', bool $statusRequired = false): array
    {
        $startDate = $prefix === '' ? 'start_date' : "{$prefix}start_date";
        $endDate = $prefix === '' ? 'end_date' : "{$prefix}end_date";
        $startTime = $prefix === '' ? 'start_time' : "{$prefix}start_time";
        $endTime = $prefix === '' ? 'end_time' : "{$prefix}end_time";
        $days = $prefix === '' ? 'days_of_week' : "{$prefix}days_of_week";
        $status = $prefix === '' ? 'status' : "{$prefix}status";

        return [
            $startDate => ['required', 'date'],
            $endDate => ['required', 'date', "after_or_equal:{$startDate}"],
            $startTime => ['required', 'date_format:H:i'],
            $endTime => ['required', 'date_format:H:i', "after:{$startTime}"],
            $days => ['required', 'array', 'min:1'],
            "{$days}.*" => ['integer', 'distinct', 'between:1,7'],
            $status => [
                $statusRequired ? 'required' : 'nullable',
                Rule::enum(DriverRosterStatus::class),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function nestedRosterRules(bool $includeBranchId = true): array
    {
        $rules = [
            'rosters' => ['sometimes', 'array'],
            ...$this->rosterPeriodRules('rosters.*.'),
        ];

        if ($includeBranchId) {
            $rules['rosters.*.branch_id'] = ['required', 'integer', Rule::exists('branches', 'id')];
        }

        return $rules;
    }

    protected function stampNestedRosterBranchId(): void
    {
        $branch = $this->route('branch');

        if (! $branch instanceof Branch || ! is_array($this->input('rosters'))) {
            return;
        }

        $this->merge([
            'rosters' => collect($this->input('rosters'))
                ->map(function (mixed $row) use ($branch): mixed {
                    if (! is_array($row)) {
                        return $row;
                    }

                    $row['branch_id'] = filled($row['branch_id'] ?? null)
                        ? $row['branch_id']
                        : $branch->id;

                    return $row;
                })
                ->all(),
        ]);
    }

    protected function normalizeDaysOfWeekInput(): void
    {
        if (! $this->exists('days_of_week') || is_array($this->input('days_of_week'))) {
            return;
        }

        $this->merge([
            'days_of_week' => [(int) $this->input('days_of_week')],
        ]);
    }
}
