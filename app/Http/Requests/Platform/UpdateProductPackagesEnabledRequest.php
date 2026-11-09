<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductPackagesEnabledRequest extends FormRequest
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
            'packages_enabled' => ['required', 'boolean'],
        ];
    }
}
