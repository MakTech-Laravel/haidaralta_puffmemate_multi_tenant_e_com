<?php

namespace App\Http\Requests\Concerns;

trait ValidatesCatalogProductImages
{
    /**
     * Secure catalog raster + SVG uploads (jpg/jpeg/png/webp/svg), max 2MB.
     * SVG uses Laravel's image:allow_svg; keep serving via <img src>, never inline HTML.
     *
     * @return list<string>
     */
    protected function catalogImageFileRules(bool $required = false): array
    {
        $rules = [
            'image:allow_svg',
            'mimes:jpg,jpeg,png,webp,svg',
            'max:2048',
        ];

        return $required
            ? $rules
            : ['nullable', ...$rules];
    }
}
