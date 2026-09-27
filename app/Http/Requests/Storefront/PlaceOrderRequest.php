<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'mobile' => ['required', 'string', 'max:32'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'save_as_default' => ['sometimes', 'boolean'],
            'delivery_method' => ['required', 'string'],
            'delivery_date' => ['nullable', 'date'],
            'time_slot_id' => ['nullable', 'string'],
            'payment_method' => ['required', 'string'],
            'coupon' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'terms' => ['accepted'],
        ];
    }
}
