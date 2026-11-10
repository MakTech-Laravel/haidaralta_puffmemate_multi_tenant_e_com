<?php

namespace App\Http\Requests;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Opens (or resumes) a shared platform↔branch ops chat.
 */
class StorePlatformBranchChatRequest extends FormRequest
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
        if ($this->route('branch') instanceof Branch) {
            return [];
        }

        return [
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id'),
            ],
        ];
    }

    public function branch(): Branch
    {
        $routeBranch = $this->route('branch');

        if ($routeBranch instanceof Branch) {
            return $routeBranch;
        }

        return Branch::query()->findOrFail($this->integer('branch_id'));
    }
}
