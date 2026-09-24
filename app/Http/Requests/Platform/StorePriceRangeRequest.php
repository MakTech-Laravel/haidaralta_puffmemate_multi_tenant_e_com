<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePriceRangeRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('price_ranges', 'slug')],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $min = $this->input('min_amount');
            $max = $this->input('max_amount');

            if ($min === null && $max === null) {
                $validator->errors()->add('min_amount', 'Set a minimum amount, a maximum amount, or both.');
            }

            if ($min !== null && $max !== null && (float) $min > (float) $max) {
                $validator->errors()->add('max_amount', 'Maximum amount must be greater than or equal to minimum amount.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->boolean('status'),
            'min_amount' => $this->filled('min_amount') ? $this->input('min_amount') : null,
            'max_amount' => $this->filled('max_amount') ? $this->input('max_amount') : null,
            'slug' => $this->filled('slug') ? $this->string('slug')->trim()->toString() : null,
        ]);
    }
}
