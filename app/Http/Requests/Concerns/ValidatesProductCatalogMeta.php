<?php

namespace App\Http\Requests\Concerns;

use App\Enums\ProductTag;
use App\Support\ProductSpecifications;
use Illuminate\Validation\Rule;

trait ValidatesProductCatalogMeta
{
    protected function prepareProductCatalogMeta(): void
    {
        if ($this->has('specifications') && is_string($this->input('specifications'))) {
            $decoded = json_decode($this->input('specifications'), true);

            $this->merge([
                'specifications' => is_array($decoded) ? $decoded : [],
            ]);
        }

        if ($this->has('tags') && is_string($this->input('tags'))) {
            $decoded = json_decode($this->input('tags'), true);

            if (is_array($decoded)) {
                $this->merge(['tags' => $decoded]);
            }
        }

        if ($this->has('brand') && $this->input('brand') === '') {
            $this->merge(['brand' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function productCatalogMetaRules(): array
    {
        return [
            'brand' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', Rule::in(ProductTag::values())],
            'specifications' => ['nullable', 'array'],
            'specifications.*.label' => ['nullable', 'string', 'max:120'],
            'specifications.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{brand: string|null, tags: list<string>, specifications: list<array{label: string, value: string}>}
     */
    protected function normalizedProductCatalogMeta(): array
    {
        /** @var list<string>|null $tags */
        $tags = $this->input('tags');

        return [
            'brand' => $this->filled('brand') ? (string) $this->input('brand') : null,
            'tags' => is_array($tags)
                ? array_values(array_unique(array_map('strval', $tags)))
                : [],
            'specifications' => ProductSpecifications::normalize($this->input('specifications')),
        ];
    }
}
