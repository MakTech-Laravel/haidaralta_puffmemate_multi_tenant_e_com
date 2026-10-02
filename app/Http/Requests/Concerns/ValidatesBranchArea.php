<?php

namespace App\Http\Requests\Concerns;

use App\Models\BranchArea;
use Closure;

trait ValidatesBranchArea
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function branchAreaRules(?int $branchId = null, ?int $ignoreAreaId = null): array
    {
        return [
            'name' => $this->branchAreaNameRules($branchId, $ignoreAreaId),
            'address' => ['required', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return list<mixed>
     */
    protected function branchAreaNameRules(?int $branchId, ?int $ignoreAreaId = null): array
    {
        return [
            'required',
            'string',
            'max:255',
            function (string $attribute, mixed $value, Closure $fail) use ($branchId, $ignoreAreaId): void {
                if ($branchId === null || $branchId < 1) {
                    return;
                }

                $name = mb_strtolower(trim((string) $value));

                if ($name === '') {
                    return;
                }

                $exists = BranchArea::query()
                    ->where('branch_id', $branchId)
                    ->when(
                        $ignoreAreaId !== null && $ignoreAreaId > 0,
                        fn ($query) => $query->whereKeyNot($ignoreAreaId),
                    )
                    ->whereRaw('LOWER(name) = ?', [$name])
                    ->exists();

                if ($exists) {
                    $fail('An area with this name already exists for this branch.');
                }
            },
        ];
    }

    protected function prepareBranchArea(): void
    {
        if ($this->has('name') && is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }

        if ($this->has('postal_code') && blank($this->input('postal_code'))) {
            $this->merge(['postal_code' => null]);
        }

        if ($this->has('notes') && blank($this->input('notes'))) {
            $this->merge(['notes' => null]);
        }
    }
}
