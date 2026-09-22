<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesShiftAssignmentContracts
{
    /**
     * @return array<string, mixed>
     */
    protected function shiftAssignmentWorkspaceRules(bool $branchFromRoute = false): array
    {
        return [
            'branch_id' => $branchFromRoute
                ? ['prohibited']
                : ['nullable', 'integer', Rule::exists('branches', 'id')->where('status', true)],
            'people_kind' => ['nullable', Rule::in(['staff', 'driver'])],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'month' => ['nullable', 'date_format:Y-m'],
            'assignment_page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function shiftAssignmentPeopleOptionsRules(bool $branchFromRoute = false): array
    {
        return [
            'branch_id' => $branchFromRoute
                ? ['prohibited']
                : ['required', 'integer', Rule::exists('branches', 'id')->where('status', true)],
            'people_kind' => ['required', Rule::in(['staff', 'driver'])],
            'filter' => ['sometimes', 'array'],
            'filter.search' => ['nullable', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:10,50'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function shiftAssignmentChangeRules(bool $branchFromRoute = false): array
    {
        return [
            'branch_id' => $branchFromRoute
                ? ['prohibited']
                : ['required', 'integer', Rule::exists('branches', 'id')->where('status', true)],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'shift_template_id' => ['nullable', 'integer', Rule::exists('shift_templates', 'id')],
            'assign_dates' => ['sometimes', 'array', 'max:366'],
            'assign_dates.*' => ['required', 'date_format:Y-m-d', 'distinct'],
            'replace_day_ids' => ['sometimes', 'array', 'max:366'],
            'replace_day_ids.*' => ['required', 'integer', 'distinct', Rule::exists('user_shift_days', 'id')],
            'remove_day_ids' => ['sometimes', 'array', 'max:366'],
            'remove_day_ids.*' => ['required', 'integer', 'distinct', Rule::exists('user_shift_days', 'id')],
            'month' => ['nullable', 'date_format:Y-m'],
            'assignment_page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function validateShiftAssignmentChanges(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $assignDates = $this->input('assign_dates', []);
            $replaceDayIds = $this->input('replace_day_ids', []);
            $removeDayIds = $this->input('remove_day_ids', []);

            $hasAssignments = is_array($assignDates) && $assignDates !== [];
            $hasReplacements = is_array($replaceDayIds) && $replaceDayIds !== [];
            $hasRemovals = is_array($removeDayIds) && $removeDayIds !== [];

            if (! $hasAssignments && ! $hasReplacements && ! $hasRemovals) {
                $validator->errors()->add('schedule', 'Select at least one shift change to save.');

                return;
            }

            if (($hasAssignments || $hasReplacements) && ! $this->filled('shift_template_id')) {
                $validator->errors()->add('shift_template_id', 'Select a shift for assignments and changes.');
            }

            if (
                is_array($replaceDayIds)
                && is_array($removeDayIds)
                && array_intersect($replaceDayIds, $removeDayIds) !== []
            ) {
                $validator->errors()->add(
                    'schedule',
                    'A scheduled day cannot be changed and removed in the same save.',
                );
            }
        });
    }
}
