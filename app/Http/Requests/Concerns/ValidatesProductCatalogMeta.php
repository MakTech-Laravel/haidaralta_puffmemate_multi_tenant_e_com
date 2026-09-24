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

        if ($this->has('pack_label') && $this->input('pack_label') === '') {
            $this->merge(['pack_label' => null]);
        }

        if ($this->has('pack_unit') && $this->input('pack_unit') === '') {
            $this->merge(['pack_unit' => null]);
        }

        if ($this->has('pieces_per_pack') && ($this->input('pieces_per_pack') === '' || $this->input('pieces_per_pack') === null)) {
            $this->merge(['pieces_per_pack' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function productCatalogMetaRules(): array
    {
        return [
            'brand' => ['nullable', 'string', 'max:120'],
            'pack_label' => ['nullable', 'string', 'max:120'],
            'pieces_per_pack' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'pack_unit' => ['nullable', 'string', 'max:40'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', Rule::in(ProductTag::values())],
            'specifications' => ['nullable', 'array'],
            'specifications.*.label' => ['nullable', 'string', 'max:120'],
            'specifications.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{
     *     brand: string|null,
     *     pack_label: string|null,
     *     pieces_per_pack: int|null,
     *     pack_unit: string|null,
     *     tags: list<string>,
     *     specifications: list<array{label: string, value: string}>
     * }
     */
    protected function normalizedProductCatalogMeta(): array
    {
        /** @var list<string>|null $tags */
        $tags = $this->input('tags');

        $pieces = $this->input('pieces_per_pack');

        return [
            'brand' => $this->filled('brand') ? (string) $this->input('brand') : null,
            'pack_label' => $this->filled('pack_label') ? (string) $this->input('pack_label') : null,
            'pieces_per_pack' => $pieces === null || $pieces === '' ? null : (int) $pieces,
            'pack_unit' => $this->filled('pack_unit') ? (string) $this->input('pack_unit') : null,
            'tags' => is_array($tags)
                ? array_values(array_unique(array_map('strval', $tags)))
                : [],
            'specifications' => ProductSpecifications::normalize($this->input('specifications')),
        ];
    }
}
