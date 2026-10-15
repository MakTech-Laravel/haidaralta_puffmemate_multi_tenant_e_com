<?php

namespace App\Http\Requests\Customer;

use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Mirrors the checkout delivery fields so the saved address always pre-fills a placeable order.
 */
class UpdateCustomerDeliveryAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->user_type === UserType::CUSTOMER;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:32'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Pin your delivery location on the map.',
            'longitude.required' => 'Pin your delivery location on the map.',
        ];
    }
}
