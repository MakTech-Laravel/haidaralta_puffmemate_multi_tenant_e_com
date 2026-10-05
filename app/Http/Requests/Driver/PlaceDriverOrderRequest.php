<?php

namespace App\Http\Requests\Driver;

use App\Models\PaymentGatewaySetting;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

class PlaceDriverOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('driver') !== null;
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
            'customer_id' => ['nullable', 'integer', 'exists:users,id'],
            'phone' => ['required_without:customer_id', 'nullable', 'string', ...Phone::validationRule()],
            'name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'string', Rule::in($methods)],
            'idempotency_key' => ['required', 'uuid'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'string', 'max:32'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
