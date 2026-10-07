<?php

namespace App\Services\Cms;

use App\Models\HomeBanner;
use App\Services\Media\MediaUploadService;
use App\Support\MediaUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HomeBannerService
{
    public function __construct(
        private readonly MediaUploadService $media,
    ) {}

    /**
     * @return Collection<int, HomeBanner>
     */
    public function all(): Collection
    {
        return HomeBanner::query()
            ->with('media')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Active banners for the storefront hero, ordered for display.
     *
     * @return list<array{
     *     id: string,
     *     eyebrow: string,
     *     title: string,
     *     description: string,
     *     image: string,
     *     primaryCta: array{label: string, href: string},
     *     secondaryCta: array{label: string, href: string}
     * }>
     */
    public function publishedForStorefront(): array
    {
        return HomeBanner::query()
            ->with('media')
            ->where('is_active', true)
            ->whereHas('media', fn ($query) => $query->where('collection_name', HomeBanner::IMAGE_COLLECTION))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (HomeBanner $banner): array => $this->toStorefrontSlide($banner))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     id: int,
     *     sort_order: int,
     *     is_active: bool,
     *     eyebrow: string,
     *     title: string,
     *     description: string,
     *     image_url: string|null,
     *     primary_cta_label: string,
     *     primary_cta_href: string,
     *     secondary_cta_label: string,
     *     secondary_cta_href: string,
     *     updated_at: string|null
     * }
     */
    public function formPayload(HomeBanner $banner): array
    {
        return [
            'id' => $banner->id,
            'sort_order' => $banner->sort_order,
            'is_active' => $banner->is_active,
            'eyebrow' => $banner->eyebrow,
            'title' => $banner->title,
            'description' => $banner->description,
            'image_url' => $banner->imageUrl(),
            'primary_cta_label' => $banner->primary_cta_label,
            'primary_cta_href' => $banner->primary_cta_href,
            'secondary_cta_label' => $banner->secondary_cta_label,
            'secondary_cta_href' => $banner->secondary_cta_href,
            'updated_at' => $banner->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{
     *     id: int,
     *     sort_order: int,
     *     is_active: bool,
     *     title: string,
     *     eyebrow: string,
     *     image_url: string|null,
     *     updated_at: string|null
     * }>
     */
    public function indexPayload(): array
    {
        return $this->all()
            ->map(fn (HomeBanner $banner): array => [
                'id' => $banner->id,
                'sort_order' => $banner->sort_order,
                'is_active' => $banner->is_active,
                'title' => $banner->title,
                'eyebrow' => $banner->eyebrow,
                'image_url' => $banner->imageUrl(),
                'updated_at' => $banner->updated_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{
     *     is_active: bool,
     *     eyebrow: string,
     *     title: string,
     *     description: string,
     *     primary_cta_label: string,
     *     primary_cta_href: string,
     *     secondary_cta_label: string,
     *     secondary_cta_href: string
     * }  $data
     */
    public function create(array $data, ?UploadedFile $image): HomeBanner
    {
        $maxOrder = (int) HomeBanner::query()->max('sort_order');

        $banner = new HomeBanner([
            ...$data,
            'sort_order' => $maxOrder + 1,
        ]);

        $banner->save();

        if ($image !== null) {
            $this->media->syncSingle($banner, HomeBanner::IMAGE_COLLECTION, $image);
        }

        return $banner;
    }

    /**
     * @param  array{
     *     is_active: bool,
     *     eyebrow: string,
     *     title: string,
     *     description: string,
     *     primary_cta_label: string,
     *     primary_cta_href: string,
     *     secondary_cta_label: string,
     *     secondary_cta_href: string
     * }  $data
     */
    public function update(
        HomeBanner $banner,
        array $data,
        ?UploadedFile $image,
        bool $removeImage = false,
    ): HomeBanner {
        $banner->fill($data);
        $banner->save();

        $this->media->syncSingle($banner, HomeBanner::IMAGE_COLLECTION, $image, $removeImage);

        return $banner;
    }

    public function delete(HomeBanner $banner): void
    {
        $banner->delete();
    }

    public function move(HomeBanner $banner, string $direction): void
    {
        $direction = $direction === 'up' ? 'up' : 'down';

        DB::transaction(function () use ($banner, $direction): void {
            $neighbors = HomeBanner::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $index = $neighbors->search(fn (HomeBanner $row): bool => $row->id === $banner->id);

            if ($index === false) {
                return;
            }

            $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

            if ($swapWith < 0 || $swapWith >= $neighbors->count()) {
                return;
            }

            /** @var HomeBanner $current */
            $current = $neighbors[$index];
            /** @var HomeBanner $other */
            $other = $neighbors[$swapWith];

            $currentOrder = $current->sort_order;
            $current->sort_order = $other->sort_order;
            $other->sort_order = $currentOrder;

            $current->save();
            $other->save();
        });
    }

    public function setActive(HomeBanner $banner, bool $active): HomeBanner
    {
        $banner->is_active = $active;
        $banner->save();

        return $banner;
    }

    /**
     * Seed the two default homepage slides if the table is empty.
     * Uses public demo images so the homepage stays populated after first migrate/seed.
     */
    public function ensureDefaults(): void
    {
        if (HomeBanner::query()->exists()) {
            return;
        }

        $defaults = [
            [
                'sort_order' => 1,
                'is_active' => true,
                'eyebrow' => 'PUFFMEMATE AUSTRALIA',
                'title' => 'Your Life. Your Choice.',
                'description' => 'A cleaner, premium shopping experience for cigarettes, vapes, tobacco, nicotine products and accessories.',
                'image_file' => 'hero-1.jpg',
                'primary_cta_label' => 'Shop',
                'primary_cta_href' => '/shop',
                'secondary_cta_label' => 'Explore Deals',
                'secondary_cta_href' => '#home-deals',
            ],
            [
                'sort_order' => 2,
                'is_active' => true,
                'eyebrow' => 'PUFFMEMATE AUSTRALIA',
                'title' => 'Premium picks. Local stock.',
                'description' => 'Browse featured packs and new arrivals from nearby branches — ready when you are.',
                'image_file' => 'hero2.jpg',
                'primary_cta_label' => 'Shop',
                'primary_cta_href' => '/shop',
                'secondary_cta_label' => 'Explore Deals',
                'secondary_cta_href' => '#home-deals',
            ],
        ];

        foreach ($defaults as $row) {
            $imageFile = $row['image_file'];
            unset($row['image_file']);

            $banner = HomeBanner::query()->create($row);

            $this->media->attachFromPath(
                $banner,
                HomeBanner::IMAGE_COLLECTION,
                public_path('images/storefront/'.$imageFile),
            );
        }
    }

    /**
     * @return array{
     *     id: string,
     *     eyebrow: string,
     *     title: string,
     *     description: string,
     *     image: string,
     *     primaryCta: array{label: string, href: string},
     *     secondaryCta: array{label: string, href: string}
     * }
     */
    private function toStorefrontSlide(HomeBanner $banner): array
    {
        return [
            'id' => (string) $banner->id,
            'eyebrow' => $banner->eyebrow,
            'title' => $banner->title,
            'description' => $banner->description,
            'image' => $banner->imageUrl(MediaUrl::DEFAULT_FALLBACK) ?? MediaUrl::DEFAULT_FALLBACK,
            'primaryCta' => [
                'label' => $banner->primary_cta_label,
                'href' => $banner->primary_cta_href,
            ],
            'secondaryCta' => [
                'label' => $banner->secondary_cta_label,
                'href' => $banner->secondary_cta_href,
            ],
        ];
    }
}
