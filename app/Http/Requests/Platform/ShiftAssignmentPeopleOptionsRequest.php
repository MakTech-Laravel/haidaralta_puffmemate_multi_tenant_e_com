<?php

namespace App\Http\Requests\Platform;

use App\Http\Requests\Concerns\ValidatesShiftAssignmentContracts;
use Illuminate\Foundation\Http\FormRequest;

class ShiftAssignmentPeopleOptionsRequest extends FormRequest
{
    use ValidatesShiftAssignmentContracts;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->shiftAssignmentPeopleOptionsRules();
    }
}
