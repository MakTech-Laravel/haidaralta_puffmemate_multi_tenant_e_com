<?php

namespace App\Http\Requests\Platform;

use App\Http\Requests\Concerns\ValidatesCatalogColorCode;
use App\Http\Requests\Concerns\ValidatesCatalogProductImages;
use App\Http\Requests\Concerns\ValidatesProductCatalogMeta;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $this->prepareProductPackagesInput();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        $rules = [
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
            'gallery' => ['nullable', 'array', 'max:12'],
            'gallery.*' => $this->catalogImageFileRules(required: true),
            'remove_gallery_ids' => ['nullable', 'array'],
            'remove_gallery_ids.*' => [
                'integer',
                Rule::exists('media', 'id')
                    ->where('model_type', $product->getMorphClass())
                    ->where('model_id', $product->id)
                    ->where('collection_name', 'gallery'),
            ],
            'status' => ['sometimes', 'boolean'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'regular_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'gt:0'],
            'min_wholesale_qty' => ['nullable', 'integer', 'min:1', 'max:100000'],
            ...$this->colorCodeRules(),
            ...$this->productCatalogMetaRules(),
        ];

        if ((bool) config('catalog.show_product_packages')) {
            $rules['packages_enabled'] = ['sometimes', 'boolean'];
            $rules['packages'] = ['nullable', 'array'];
            $rules['packages.*.id'] = [
                'nullable',
                'integer',
                Rule::exists('product_packages', 'id')
                    ->where('product_id', $product->id)
                    ->whereNull('deleted_at'),
            ];
            $rules['packages.*.label'] = ['required', 'string', 'max:255'];
            $rules['packages.*.quantity'] = ['required', 'integer', 'min:1', 'max:99999'];
            $rules['packages.*.price'] = ['required', 'numeric', 'min:0'];
            $rules['packages.*.status'] = ['sometimes', 'boolean'];
            $rules['packages.*.sort_order'] = ['sometimes', 'integer', 'min:0', 'max:99999'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'packages.*.label.required' => 'Enter a package label, or remove this row.',
            'packages.*.quantity.required' => 'Enter a package quantity, or remove this row.',
            'packages.*.quantity.min' => 'Package quantity must be at least 1, or remove this row.',
            'packages.*.price.required' => 'Enter a package price, or remove this row.',
            'packages.*.id.exists' => 'One of the packages no longer belongs to this product.',
        ];
    }

    private function prepareProductPackagesInput(): void
    {
        if (! (bool) config('catalog.show_product_packages')) {
            return;
        }

        if ($this->has('packages') && is_string($this->input('packages'))) {
            $decoded = json_decode($this->input('packages'), true);

            $this->merge([
                'packages' => is_array($decoded) ? $decoded : [],
            ]);
        }
    }
}
