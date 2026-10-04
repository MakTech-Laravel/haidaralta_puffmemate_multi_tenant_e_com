<?php

namespace App\Http\Requests\Storefront;

use App\Models\DeliveryMethod;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('customer');

        return $user instanceof User && $user->isCustomer() && $user->is_active;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $enabledPaymentKeys = collect(app(PaymentGatewaySettingsService::class)->enabledMethods())
            ->pluck('id')
            ->all();

        return [
            'name' => ['required', 'string', 'max:255'],
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
            'payment_method' => ['required', 'string', Rule::in($enabledPaymentKeys)],
            'coupon' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.in' => 'Choose an available payment method.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $key = $this->input('delivery_method');

            if (! is_string($key) || $key === '') {
                return;
            }

            $method = DeliveryMethod::query()
                ->where('key', $key)
                ->where('status', true)
                ->first();

            if ($method === null || ! $method->requires_slot) {
                return;
            }

            if (blank($this->input('time_slot_id'))) {
                $validator->errors()->add('time_slot_id', 'Choose a time slot.');
            }
        });
    }
}
