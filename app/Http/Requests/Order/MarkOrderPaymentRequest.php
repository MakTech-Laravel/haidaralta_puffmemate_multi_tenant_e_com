<?php

namespace App\Http\Requests\Order;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Platform-only override of a master order's payment_status. Branch staff
 * marking their own fulfillment paid use a body-less action route instead.
 */
class MarkOrderPaymentRequest extends FormRequest
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
            'payment_status' => ['required', new Enum(PaymentStatus::class)],
        ];
    }
}
