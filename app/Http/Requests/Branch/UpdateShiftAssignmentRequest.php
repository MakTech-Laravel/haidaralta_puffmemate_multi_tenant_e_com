<?php

namespace App\Http\Requests\Branch;

use App\Concerns\ValidatesWorkforceInput;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftAssignmentRequest extends FormRequest
{
    use ValidatesWorkforceInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDaysOfWeekInput();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->shiftAssignmentRules(creating: false);
    }
}
