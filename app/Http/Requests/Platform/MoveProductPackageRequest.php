<?php

namespace App\Http\Requests\Platform;

use App\Services\Catalog\ProductPackageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveProductPackageRequest extends FormRequest
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
            'direction' => [
                'required',
                Rule::in([
                    ProductPackageService::DIRECTION_UP,
                    ProductPackageService::DIRECTION_DOWN,
                ]),
            ],
        ];
    }
}
