<?php

namespace App\Http\Requests\Platform;

use App\Services\Driver\DriverService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncDriverBranchesRequest extends FormRequest
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
            'branch_ids' => [
                'required',
                'array',
                Rule::when((bool) config('drivers.single_active_branch'), ['max:1']),
            ],
            'branch_ids.*' => ['integer', 'distinct', Rule::exists('branches', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch_ids.max' => DriverService::ONE_ACTIVE_BRANCH_MESSAGE,
        ];
    }
}
