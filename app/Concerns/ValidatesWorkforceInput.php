<?php

namespace App\Concerns;

use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftOwnerType;
use App\Models\DriverRoster;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait ValidatesWorkforceInput
{
    /**
     * @return array<string, mixed>
     */
    protected function shiftTemplateRules(bool $nameRequired = true): array
    {
        return [
            'name' => [
                $nameRequired ? 'required' : 'sometimes',
                'string',
                'max:120',
                $this->uniqueOwnerName('shift_templates', $this->routeModelId('shift_template')),
            ],
            'start_time' => [$nameRequired ? 'required' : 'sometimes', 'date_format:H:i'],
            'end_time' => [$nameRequired ? 'required' : 'sometimes', 'date_format:H:i'],
            'owner_type' => ['sometimes', Rule::enum(ShiftOwnerType::class)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rosterPeriodRules(bool $nameRequired = true): array
    {
        return [
            'name' => [
                $nameRequired ? 'required' : 'sometimes',
                'string',
                'max:120',
                $this->uniqueOwnerName('shift_roster_periods', $this->routeModelId('shift_roster_period')),
            ],
            'start_date' => [$nameRequired ? 'required' : 'sometimes', 'date'],
            'end_date' => [$nameRequired ? 'required' : 'sometimes', 'date', 'after_or_equal:start_date'],
            'owner_type' => ['sometimes', Rule::enum(ShiftOwnerType::class)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function shiftAssignmentRules(bool $creating = true): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'user_id' => [$creating ? 'required' : 'prohibited', 'integer', Rule::exists('users', 'id')],
            'branch_id' => [$creating ? 'required' : 'prohibited', 'integer', Rule::exists('branches', 'id')],
            'shift_template_id' => [$required, 'integer', Rule::exists('shift_templates', 'id')],
            'shift_roster_period_id' => [$required, 'integer', Rule::exists('shift_roster_periods', 'id')],
            'start_date' => [$required, 'date'],
            'end_date' => [$required, 'date', 'after_or_equal:start_date'],
            'days_of_week' => [$creating ? 'required' : 'sometimes', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'distinct', 'between:1,7'],
            'status' => ['sometimes', Rule::enum(ShiftAssignmentStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
            'effective_date' => ['nullable', 'date'],
        ];
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

    /**
     * @return list<int>
     */
    protected function normalizedDays(): array
    {
        return DriverRoster::normalizeDays($this->input('days_of_week'));
    }

    private function uniqueOwnerName(string $table, ?int $ignoreId = null): Unique
    {
        $branchId = $this->filled('branch_id') ? (int) $this->input('branch_id') : null;

        $rule = Rule::unique($table, 'name');

        if ($ignoreId !== null) {
            $rule->ignore($ignoreId);
        }

        return $branchId === null
            ? $rule->whereNull('branch_id')
            : $rule->where('branch_id', $branchId);
    }

    private function routeModelId(string $parameter): ?int
    {
        $model = $this->route($parameter);

        if (is_object($model) && isset($model->id)) {
            return (int) $model->id;
        }

        return is_numeric($model) ? (int) $model : null;
    }
}
