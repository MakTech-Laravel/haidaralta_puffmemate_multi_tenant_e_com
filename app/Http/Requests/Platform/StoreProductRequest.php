<?php

namespace App\Http\Requests\Platform;

use App\Http\Requests\Concerns\ValidatesCatalogColorCode;
use App\Http\Requests\Concerns\ValidatesCatalogProductImages;
use App\Http\Requests\Concerns\ValidatesProductCatalogMeta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    use ValidatesCatalogColorCode;
    use ValidatesCatalogProductImages;
    use ValidatesProductCatalogMeta;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->decodeColorCodeInput();
        $this->prepareProductCatalogMeta();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')],
            'description' => ['nullable', 'string'],
            'image' => $this->catalogImageFileRules(),
            'gallery' => ['nullable', 'array', 'max:12'],
            'gallery.*' => $this->catalogImageFileRules(required: true),
            'status' => ['sometimes', 'boolean'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'regular_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'gt:0'],
            'min_wholesale_qty' => ['nullable', 'integer', 'min:1', 'max:100000'],
            ...$this->colorCodeRules(),
            ...$this->productCatalogMetaRules(),
        ];
    }
}
