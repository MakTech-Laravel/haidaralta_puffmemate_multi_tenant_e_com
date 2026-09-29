<?php

namespace App\Http\Requests\Platform;

use App\Http\Requests\Branch\UpdatePackRequest as BranchUpdatePackRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdatePackRequest extends BranchUpdatePackRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'is_highlighted' => ['sometimes', 'boolean'],
        ];
    }
}
