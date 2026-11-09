<?php

namespace App\Http\Requests\Platform;

use App\Support\DutyWindow;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryShiftRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('delivery_shifts', 'name')],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $startTime = $this->input('start_time');

                    if (! is_string($startTime) || ! is_string($value)) {
                        return;
                    }

                    if (! DutyWindow::isSameDayWindow($startTime, $value)) {
                        $fail('End time must be after start time on the same day.');
                    }
                },
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
