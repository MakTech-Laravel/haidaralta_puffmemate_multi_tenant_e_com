<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchShippingRuleRequest extends FormRequest
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
            'base_fee' => ['required', 'numeric', 'min:0'],
            'free_delivery_distance_km' => ['nullable', 'numeric', 'min:0'],
            'cost_per_km' => ['required', 'numeric', 'min:0'],
            'minimum_delivery_charge' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->boolean('status'),
            'free_delivery_distance_km' => $this->input('free_delivery_distance_km') === '' ? null : $this->input('free_delivery_distance_km'),
        ]);
    }
}
