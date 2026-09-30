<?php

namespace App\Http\Requests\Storefront;

use App\Services\Storefront\CartService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'min:0', 'max:'.CartService::MAX_LINE_QUANTITY],
        ];
    }
}
