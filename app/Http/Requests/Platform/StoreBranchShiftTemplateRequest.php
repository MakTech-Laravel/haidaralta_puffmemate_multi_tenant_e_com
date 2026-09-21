<?php

namespace App\Http\Requests\Platform;

use App\Concerns\ValidatesWorkforceInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchShiftTemplateRequest extends FormRequest
{
    use ValidatesWorkforceInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->forceBranchOwner();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->shiftTemplateRules(),
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
        ];
    }
}
