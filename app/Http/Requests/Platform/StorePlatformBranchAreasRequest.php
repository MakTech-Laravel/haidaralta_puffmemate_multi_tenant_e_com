<?php

namespace App\Http\Requests\Platform;

use App\Http\Requests\Concerns\ValidatesBranchArea;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlatformBranchAreasRequest extends FormRequest
{
    use ValidatesBranchArea;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareBranchArea();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $branchId = $this->filled('branch_id') ? $this->integer('branch_id') : null;

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            ...$this->branchAreaRules($branchId),
        ];
    }

    public function branch(): Branch
    {
        return Branch::query()->findOrFail($this->integer('branch_id'));
    }
}
