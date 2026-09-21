<?php

namespace App\Http\Requests\Branch;

use App\Concerns\ValidatesWorkforceInput;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;

class StoreShiftAssignmentRequest extends FormRequest
{
    use ValidatesWorkforceInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDaysOfWeekInput();

        $branch = $this->route('branch');

        if ($branch instanceof Branch) {
            $this->merge(['branch_id' => $branch->id]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->shiftAssignmentRules();
    }
}
