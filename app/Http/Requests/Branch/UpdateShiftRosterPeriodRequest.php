<?php

namespace App\Http\Requests\Branch;

use App\Concerns\ValidatesWorkforceInput;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRosterPeriodRequest extends FormRequest
{
    use ValidatesWorkforceInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->rosterPeriodRules(nameRequired: false);
    }
}
