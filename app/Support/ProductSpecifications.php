<?php

namespace App\Support;

class ProductSpecifications
{
    /**
     * Normalize associative maps or list rows into ordered label/value pairs.
     *
     * @return list<array{label: string, value: string}>
     */
    public static function normalize(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $pairs = [];

        if (array_is_list($raw)) {
            foreach ($raw as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $label = trim((string) ($row['label'] ?? ''));
                $value = trim((string) ($row['value'] ?? ''));

                if ($label === '' && $value === '') {
                    continue;
                }

                $pairs[] = [
                    'label' => $label,
                    'value' => $value,
                ];
            }

            return $pairs;
        }

        foreach ($raw as $label => $value) {
            $labelText = trim((string) $label);
            $valueText = is_scalar($value) || $value === null
                ? trim((string) $value)
                : '';

            if ($labelText === '' && $valueText === '') {
                continue;
            }

            $pairs[] = [
                'label' => $labelText,
                'value' => $valueText,
            ];
        }

        return $pairs;
    }

    /**
     * Build PDP rows: Product Type + Brand + stored specifications (skip duplicate Brand/Product Type labels).
     *
     * @param  list<array{label: string, value: string}>|null  $stored
     * @return list<array{label: string, value: string}>
     */
    public static function forDisplay(
        ?string $primaryCategoryName,
        ?string $brand,
        ?array $stored,
    ): array {
        $rows = [];

        if (is_string($primaryCategoryName) && $primaryCategoryName !== '') {
            $rows[] = ['label' => 'Product Type', 'value' => $primaryCategoryName];
        }

        if (is_string($brand) && $brand !== '') {
            $rows[] = ['label' => 'Brand', 'value' => $brand];
        }

        $skip = ['product type', 'brand'];

        foreach (self::normalize($stored) as $row) {
            if (in_array(strtolower($row['label']), $skip, true)) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
