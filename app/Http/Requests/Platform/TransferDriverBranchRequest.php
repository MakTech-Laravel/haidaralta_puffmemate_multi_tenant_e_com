<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferDriverBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to_branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:500'],
            'roles' => ['prohibited'],
        ];
    }
}
