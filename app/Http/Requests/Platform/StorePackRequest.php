<?php

namespace App\Http\Requests\Platform;

use App\Http\Requests\Branch\StorePackRequest as BranchStorePackRequest;
use Illuminate\Validation\Rule;

class StorePackRequest extends BranchStorePackRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('status', true)],
            'is_highlighted' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch_id' => 'branch',
        ];
    }
}
