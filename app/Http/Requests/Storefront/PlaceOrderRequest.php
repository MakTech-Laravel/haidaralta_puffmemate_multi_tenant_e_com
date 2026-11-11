<?php

namespace App\Http\Requests\Storefront;

use App\Models\DeliveryMethod;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Support\Delivery\DeliveryShiftAvailability;
use App\Support\Phone;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('customer');

        if ($user === null) {
            return true;
        }

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

        $methodKey = $this->input('delivery_method');
        $isLate = $methodKey === DeliveryShiftAvailability::METHOD_LATE;

        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => [
                'required',
                'string',
                'max:32',
                ...Phone::validationRule(allowInternational: false),
            ],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'save_as_default' => ['sometimes', 'boolean'],
            'delivery_method' => ['required', 'string'],
            'delivery_date' => [$isLate ? 'required' : 'nullable', 'date'],
            'time_slot_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string', Rule::in($enabledPaymentKeys)],
            'coupon' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'accept_late_delivery' => ['sometimes', 'boolean'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mobile.phone' => 'Enter a valid Australian mobile number.',
            'payment_method.in' => 'Choose an available payment method.',
            'time_slot_id.required' => 'Choose a delivery shift.',
            'delivery_date.required' => 'Choose a delivery date.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $key = $this->input('delivery_method');

            if (! is_string($key) || $key === '') {
                return;
            }

            $method = DeliveryMethod::query()
                ->where('key', $key)
                ->where('status', true)
                ->first();

            if ($method === null) {
                $validator->errors()->add('delivery_method', 'Choose a delivery method.');

                return;
            }

            try {
                app(DeliveryShiftAvailability::class)->assertSelectable(
                    $method->key,
                    $this->input('delivery_date'),
                    $this->input('time_slot_id'),
                );
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        });
    }
}
