<?php

namespace App\Http\Requests\Order;

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
        $fulfillment = $this->route('fulfillment');
        $isReassign = $fulfillment instanceof OrderFulfillment && $fulfillment->driver_id !== null;

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
}
