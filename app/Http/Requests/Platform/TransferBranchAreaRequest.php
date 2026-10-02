<?php

namespace App\Http\Requests\Platform;

use App\Models\BranchArea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferBranchAreaRequest extends FormRequest
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
        /** @var BranchArea $area */
        $area = $this->route('area');

        return [
            'to_branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('status', true)),
                Rule::notIn([(int) $area->branch_id]),
            ],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to_branch_id.not_in' => 'Choose a different branch than the current owner.',
        ];
    }
}
