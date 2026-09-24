<?php

namespace App\Services\Catalog;

use App\Models\PriceRange;
use App\Models\ShopPriceFilterSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PriceRangeService
{
    /**
     * @return Collection<int, PriceRange>
     */
    public function all(): Collection
    {
        return PriceRange::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Active presets for the storefront shop sidebar.
     *
     * @return list<array{slug: string, label: string, min: float|null, max: float|null}>
     */
    public function activeForStorefront(): array
    {
        return PriceRange::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (PriceRange $range): array => [
                'slug' => $range->slug,
                'label' => $range->label,
                'min' => $range->min_amount === null ? null : (float) $range->min_amount,
                'max' => $range->max_amount === null ? null : (float) $range->max_amount,
            ])
            ->values()
            ->all();
    }

    /**
     * Storefront dual-range slider floor and ceiling (AUD integers).
     *
     * @return array{min: int, max: int}
     */
    public function sliderBounds(): array
    {
        $settings = ShopPriceFilterSetting::current();
        $min = max(0, (int) floor((float) $settings->slider_min));
        $max = (int) ceil((float) $settings->slider_max);

        if ($max <= $min) {
            $max = $min + 1;
        }

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    public function sliderMin(): int
    {
        return $this->sliderBounds()['min'];
    }

    public function sliderMax(): int
    {
        return $this->sliderBounds()['max'];
    }

    /**
     * @return array{slider_min: string, slider_max: string}
     */
    public function boundsPayload(): array
    {
        $settings = ShopPriceFilterSetting::current();

        return [
            'slider_min' => (string) $settings->slider_min,
            'slider_max' => (string) $settings->slider_max,
        ];
    }

    /**
     * @param  array{slider_min: float|int|string, slider_max: float|int|string}  $data
     */
    public function updateSliderBounds(array $data): ShopPriceFilterSetting
    {
        $settings = ShopPriceFilterSetting::current();
        $settings->fill([
            'slider_min' => $data['slider_min'],
            'slider_max' => $data['slider_max'],
        ])->save();

        return $settings->refresh();
    }

    /**
     * @return list<array{
     *     id: int,
     *     label: string,
     *     slug: string,
     *     min_amount: string|null,
     *     max_amount: string|null,
     *     sort_order: int,
     *     status: bool,
     *     updated_at: string|null
     * }>
     */
    public function indexPayload(): array
    {
        return $this->all()
            ->map(fn (PriceRange $range): array => $this->rowPayload($range))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     id: int,
     *     label: string,
     *     slug: string,
     *     min_amount: string|null,
     *     max_amount: string|null,
     *     sort_order: int,
     *     status: bool,
     *     updated_at: string|null
     * }
     */
    public function formPayload(PriceRange $range): array
    {
        return $this->rowPayload($range);
    }

    /**
     * @param  array{
     *     label: string,
     *     slug?: string|null,
     *     min_amount?: float|string|null,
     *     max_amount?: float|string|null,
     *     status?: bool
     * }  $data
     */
    public function create(array $data): PriceRange
    {
        return DB::transaction(function () use ($data): PriceRange {
            $nextSort = ((int) PriceRange::query()->max('sort_order')) + 1;

            return PriceRange::query()->create([
                'label' => $data['label'],
                'slug' => $this->uniqueSlug($data['slug'] ?? null, $data['label']),
                'min_amount' => $data['min_amount'] ?? null,
                'max_amount' => $data['max_amount'] ?? null,
                'sort_order' => $nextSort,
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    /**
     * @param  array{
     *     label: string,
     *     slug?: string|null,
     *     min_amount?: float|string|null,
     *     max_amount?: float|string|null,
     *     status?: bool
     * }  $data
     */
    public function update(PriceRange $range, array $data): PriceRange
    {
        $range->fill([
            'label' => $data['label'],
            'slug' => $this->uniqueSlug($data['slug'] ?? null, $data['label'], $range->id),
            'min_amount' => $data['min_amount'] ?? null,
            'max_amount' => $data['max_amount'] ?? null,
            'status' => $data['status'] ?? $range->status,
        ]);
        $range->save();

        return $range->refresh();
    }

    public function delete(PriceRange $range): void
    {
        $range->delete();
    }

    public function setStatus(PriceRange $range, bool $status): PriceRange
    {
        $range->forceFill(['status' => $status])->save();

        return $range->refresh();
    }

    public function move(PriceRange $range, string $direction): void
    {
        DB::transaction(function () use ($range, $direction): void {
            $ordered = PriceRange::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $index = $ordered->search(fn (PriceRange $item): bool => $item->id === $range->id);

            if ($index === false) {
                return;
            }

            $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

            if ($swapWith < 0 || $swapWith >= $ordered->count()) {
                return;
            }

            /** @var PriceRange $current */
            $current = $ordered[$index];
            /** @var PriceRange $neighbor */
            $neighbor = $ordered[$swapWith];

            $currentSort = $current->sort_order;
            $current->forceFill(['sort_order' => $neighbor->sort_order])->save();
            $neighbor->forceFill(['sort_order' => $currentSort])->save();
        });
    }

    /**
     * @return array{
     *     id: int,
     *     label: string,
     *     slug: string,
     *     min_amount: string|null,
     *     max_amount: string|null,
     *     sort_order: int,
     *     status: bool,
     *     updated_at: string|null
     * }
     */
    private function rowPayload(PriceRange $range): array
    {
        return [
            'id' => $range->id,
            'label' => $range->label,
            'slug' => $range->slug,
            'min_amount' => $range->min_amount === null ? null : (string) $range->min_amount,
            'max_amount' => $range->max_amount === null ? null : (string) $range->max_amount,
            'sort_order' => $range->sort_order,
            'status' => $range->status,
            'updated_at' => $range->updated_at?->toIso8601String(),
        ];
    }

    private function uniqueSlug(?string $slug, string $label, ?int $ignoreId = null): string
    {
        $base = Str::slug(is_string($slug) && $slug !== '' ? $slug : $label);
        $base = $base !== '' ? $base : 'price-range';
        $candidate = $base;
        $suffix = 2;

        while (
            PriceRange::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
