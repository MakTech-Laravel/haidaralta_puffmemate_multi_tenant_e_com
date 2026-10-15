<?php

namespace App\Http\Requests\Platform;

use App\Models\PaymentGatewaySetting;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

class StoreWholesaleOrderRequest extends FormRequest
{
    public const MAX_ITEMS = 100;

    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('phone');

        if (is_string($raw) && $raw !== '') {
            try {
                $this->merge(['phone' => Phone::toE164($raw)]);
            } catch (Throwable) {
                // Leave raw value for the phone validation rule.
            }
        }

        foreach (['discount', 'delivery_fee', 'email', 'company', 'time_slot', 'notes', 'delivery_date'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $methods = collect(app(PaymentGatewaySettingsService::class)->enabledMethods())
            ->pluck('id')
            ->filter(fn (mixed $id): bool => is_string($id) && in_array($id, PaymentGatewaySetting::KEYS, true))
            ->values()
            ->all();

        return [
            'wholesale_inquiry_id' => ['nullable', 'integer', Rule::exists('wholesale_inquiries', 'id')],
            'customer_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', ...Phone::validationRule(false)],
            'email' => ['nullable', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:2000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('status', true)],
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('status', true)],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'payment_method' => ['required', 'string', Rule::in($methods)],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'time_slot' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch_id.required' => 'Choose a branch to fulfil this order.',
            'branch_id.exists' => 'Choose an active branch.',
            'items.required' => 'Add at least one product.',
            'items.min' => 'Add at least one product.',
            'items.*.product_id.exists' => 'This product is no longer available.',
            'payment_method.in' => 'Choose an available payment method.',
        ];
    }
}
