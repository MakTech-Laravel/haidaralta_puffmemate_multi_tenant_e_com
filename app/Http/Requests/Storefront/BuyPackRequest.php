<?php

namespace App\Http\Requests\Storefront;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class BuyPackRequest extends FormRequest
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
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
