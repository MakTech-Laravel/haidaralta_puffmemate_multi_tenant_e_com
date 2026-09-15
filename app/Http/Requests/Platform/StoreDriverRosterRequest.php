<?php

namespace App\Http\Requests\Platform;

use App\Concerns\ValidatesDriverRosterPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverRosterRequest extends FormRequest
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
        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            ...$this->rosterPeriodRules(),
        ];
    }
}
