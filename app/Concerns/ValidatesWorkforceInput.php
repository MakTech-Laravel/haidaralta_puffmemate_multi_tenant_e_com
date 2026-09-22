<?php

namespace App\Concerns;

use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftDayKind;
use App\Enums\ShiftOwnerType;
use App\Models\DriverRoster;
use App\Support\DutyWindow;
use App\Support\ShiftDutyCalendar;
use Closure;
use Illuminate\Support\Carbon;
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
            'end_time' => [
                $nameRequired ? 'required' : 'sometimes',
                'date_format:H:i',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $startTime = $this->input('start_time');

                    if (! is_string($startTime) || ! is_string($value)) {
                        return;
                    }

                    if (! DutyWindow::isSameDayWindow($startTime, $value)) {
                        $fail('End time must be after start time on the same day.');
                    }
                },
            ],
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
            'start_date' => [$required, 'date'],
            'end_date' => [
                $required,
                'date',
                'after_or_equal:start_date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $startDate = $this->input('start_date');

                    if (! is_string($startDate) || ! is_string($value)) {
                        return;
                    }

                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($value)->startOfDay();

                    if ((int) $start->diffInDays($end) + 1 > ShiftDutyCalendar::MAX_DAYS) {
                        $fail('A duty period cannot be longer than 366 days.');
                    }
                },
            ],
            'days_of_week' => [$creating ? 'required' : 'sometimes', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'distinct', 'between:1,7'],
            'days' => ['sometimes', 'array'],
            'days.*.date' => ['required', 'date'],
            'days.*.kind' => ['required', Rule::enum(ShiftDayKind::class)],
            'days.*.shift_template_id' => ['nullable', 'integer', Rule::exists('shift_templates', 'id')],
            'status' => ['sometimes', Rule::enum(ShiftAssignmentStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
            'effective_date' => ['nullable', 'date'],
        ];
    }

    protected function forcePlatformOwner(): void
    {
        $this->merge([
            'owner_type' => ShiftOwnerType::Platform->value,
            'branch_id' => null,
        ]);
    }

    protected function forceBranchOwner(): void
    {
        $this->merge([
            'owner_type' => ShiftOwnerType::Branch->value,
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
