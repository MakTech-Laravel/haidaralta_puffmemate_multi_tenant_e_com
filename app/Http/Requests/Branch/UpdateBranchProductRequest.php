<?php

namespace App\Http\Requests\Branch;

use App\Http\Requests\Concerns\ValidatesCatalogColorCode;
use App\Http\Requests\Concerns\ValidatesCatalogProductImages;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchProductRequest extends FormRequest
{
    use ValidatesCatalogColorCode;
    use ValidatesCatalogProductImages;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->decodeColorCodeInput();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('products', 'slug')->ignore($product->id),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'description' => ['nullable', 'string'],
            'image' => $this->catalogImageFileRules(),
            'remove_image' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'boolean'],
            ...$this->colorCodeRules(),
        ];
    }
}
