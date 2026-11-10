<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless((bool) config('catalog.show_product_packages'), 404);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required' => 'Enter a package label.',
            'quantity.required' => 'Enter a package quantity.',
            'quantity.min' => 'Package quantity must be at least 1.',
            'price.required' => 'Enter a package price.',
        ];
    }
}
