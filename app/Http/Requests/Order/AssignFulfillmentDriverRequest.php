<?php

namespace App\Http\Requests\Order;

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
        $isReassign = $fulfillment !== null && $fulfillment->driver_id !== null;

        return [
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => [
                Rule::requiredIf($isReassign),
                'nullable',
                'string',
                'max:255',
            ],
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
