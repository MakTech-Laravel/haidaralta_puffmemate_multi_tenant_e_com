<?php

namespace App\Http\Requests\Order;

use App\Enums\FulfillmentStatus;
use App\Models\Order;
use App\Models\OrderFulfillment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignFulfillmentDriverRequest extends FormRequest
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
        $fulfillment = $this->resolveFulfillment();
        $needsReason = $fulfillment !== null && (
            $fulfillment->driver_id !== null || $fulfillment->status === FulfillmentStatus::Returned
        );

        return [
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => [
                Rule::requiredIf($needsReason),
                'nullable',
                'string',
                'max:255',
            ],
            'advance' => ['nullable', 'string', Rule::in([
                FulfillmentStatus::PickedUp->value,
                FulfillmentStatus::OutForDelivery->value,
            ])],
        ];
    }

    private function resolveFulfillment(): ?OrderFulfillment
    {
        $fulfillment = $this->route('fulfillment');

        if ($fulfillment instanceof OrderFulfillment) {
            return $fulfillment;
        }

        $order = $this->route('order');

        if ($order instanceof Order) {
            return $order->fulfillment;
        }

        return null;
    }
}
