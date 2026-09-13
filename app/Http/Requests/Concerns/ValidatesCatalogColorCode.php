<?php

namespace App\Http\Requests\Concerns;

use App\Enums\CatalogBackgroundMode;
use Illuminate\Validation\Rule;

trait ValidatesCatalogColorCode
{
    /**
     * @return array<string, mixed>
     */
    protected function colorCodeRules(): array
    {
        return [
            'color_code' => ['nullable', 'array'],
            'color_code.mode' => [
                'required_with:color_code',
                Rule::enum(CatalogBackgroundMode::class),
            ],
            'color_code.color' => [
                'required_if:color_code.mode,'.CatalogBackgroundMode::Solid->value,
                'nullable',
                'hex_color',
            ],
            'color_code.from' => [
                'required_if:color_code.mode,'.CatalogBackgroundMode::Gradient->value,
                'nullable',
                'hex_color',
            ],
            'color_code.to' => [
                'required_if:color_code.mode,'.CatalogBackgroundMode::Gradient->value,
                'nullable',
                'hex_color',
            ],
            'color_code.angle' => [
                'nullable',
                'integer',
                'min:0',
                'max:360',
            ],
        ];
    }

    protected function decodeColorCodeInput(): void
    {
        if (! $this->exists('color_code')) {
            return;
        }

        $raw = $this->input('color_code');

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $this->merge([
                'color_code' => is_array($decoded) ? $decoded : null,
            ]);
        }
    }
}
