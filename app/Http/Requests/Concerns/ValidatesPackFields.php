<?php

namespace App\Http\Requests\Concerns;

use App\Enums\PackOfferType;
use Illuminate\Validation\Rule;

trait ValidatesPackFields
{
    protected function preparePackFields(): void
    {
        $this->merge([
            'description' => $this->filled('description') ? $this->input('description') : null,
            'starts_at' => $this->filled('starts_at') ? $this->input('starts_at') : null,
            'ends_at' => $this->filled('ends_at') ? $this->input('ends_at') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function packFieldRules(bool $withItems = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => $this->catalogImageFileRules(),
            'gallery' => ['nullable', 'array', 'max:12'],
            'gallery.*' => $this->catalogImageFileRules(required: true),
            'offer_type' => ['required', Rule::enum(PackOfferType::class)],
            'offer_value' => [
                'required',
                'numeric',
                'min:0',
                Rule::when($this->input('offer_type') === PackOfferType::Percent->value, ['max:100']),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];

        if ($withItems) {
            $rules['items'] = ['required', 'array', 'min:2'];
            $rules['items.*.product_id'] = ['required', 'integer', 'distinct'];
            $rules['items.*.quantity'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }
}
