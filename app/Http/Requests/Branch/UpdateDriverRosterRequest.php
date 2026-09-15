<?php

namespace App\Http\Requests\Branch;

use App\Concerns\ValidatesDriverRosterPeriod;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRosterRequest extends FormRequest
{
    use ValidatesDriverRosterPeriod;

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
        return $this->rosterPeriodRules(statusRequired: true);
    }
}
