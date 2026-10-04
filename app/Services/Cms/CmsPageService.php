<?php

namespace App\Services\Cms;

use App\Models\CmsPage;
use App\Services\Media\MediaUploadService;
use App\Support\Cms\CmsPageDefaults;
use App\Support\MediaUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CmsPageService
{
    public const CACHE_PREFIX = 'cms_page.';

    public function __construct(
        private readonly MediaUploadService $media,
    ) {}

    /**
     * @return Collection<int, CmsPage>
     */
    public function all(): Collection
    {
        $this->ensureDefaults();

        $order = array_flip(CmsPageDefaults::slugs());

        return CmsPage::query()
            ->get()
            ->sortBy(fn (CmsPage $page): int => $order[$page->slug] ?? 999)
            ->values();
    }

    public function findBySlug(string $slug): ?CmsPage
    {
        if (! in_array($slug, CmsPageDefaults::slugs(), true)) {
            return null;
        }

        $this->ensureDefaults();

        /** @var array<string, mixed>|null $attributes */
        $attributes = Cache::rememberForever(self::CACHE_PREFIX.$slug, function () use ($slug): ?array {
            $page = CmsPage::query()->where('slug', $slug)->first();

            return $page?->getAttributes();
        });

        if ($attributes === null) {
            return null;
        }

        return (new CmsPage)->newFromBuilder($attributes);
    }

    public function findPublished(string $slug): ?CmsPage
    {
        $page = $this->findBySlug($slug);

        if ($page === null || ! $page->is_published) {
            return null;
        }

        return $page;
    }

    /**
     * @param  array{
     *     title: string,
     *     meta_title?: string|null,
     *     meta_description?: string|null,
     *     is_published: bool,
     *     payload: array<string, mixed>,
     *     remove_hero_image?: bool,
     *     remove_media_image?: bool,
     * }  $data
     */
    public function update(
        string $slug,
        array $data,
        ?int $updatedBy = null,
        ?UploadedFile $heroImage = null,
        ?UploadedFile $mediaImage = null,
    ): CmsPage {
        $page = $this->findBySlug($slug);

        if ($page === null) {
            abort(404);
        }

        $payload = $this->normalizePayload($slug, $data['payload'] ?? []);

        $payload = $this->applyImageUpload(
            $page,
            $payload,
            ['hero', 'image'],
            CmsPage::HERO_COLLECTION,
            $heroImage,
            (bool) ($data['remove_hero_image'] ?? false),
        );

        $payload = $this->applyImageUpload(
            $page,
            $payload,
            ['who_we_are', 'mediaImage'],
            CmsPage::MEDIA_COLLECTION,
            $mediaImage,
            (bool) ($data['remove_media_image'] ?? false),
        );

        $page->title = $data['title'];
        $page->meta_title = $data['meta_title'] ?? null;
        $page->meta_description = $data['meta_description'] ?? null;
        $page->is_published = (bool) $data['is_published'];
        $page->payload = $payload;
        $page->updated_by = $updatedBy;
        $page->save();

        Cache::forget(self::CACHE_PREFIX.$slug);
        Cache::forever(self::CACHE_PREFIX.$slug, $page->fresh()->getAttributes());

        return $page->fresh();
    }

    /**
     * @return array{
     *     id: int,
     *     slug: string,
     *     title: string,
     *     meta_title: string|null,
     *     meta_description: string|null,
     *     is_published: bool,
     *     payload: array<string, mixed>,
     *     updated_at: string|null
     * }
     */
    public function formPayload(CmsPage $page): array
    {
        return [
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->title,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'is_published' => $page->is_published,
            'payload' => $this->enrichPayloadForForm($page, $page->payload ?? []),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{
     *     slug: string,
     *     title: string,
     *     meta_title: string|null,
     *     meta_description: string|null,
     *     payload: array<string, mixed>
     * }
     */
    public function publicPayload(CmsPage $page): array
    {
        $payload = $page->payload ?? [];
        $payload = $this->resolvePublicImageUrls($page, $payload);

        return [
            'slug' => $page->slug,
            'title' => $page->title,
            'meta_title' => $page->meta_title ?: $page->title,
            'meta_description' => $page->meta_description,
            'payload' => $payload,
        ];
    }

    public function ensureDefaults(): void
    {
        if (! $this->tableReady()) {
            return;
        }

        foreach (CmsPageDefaults::all() as $slug => $definition) {
            CmsPage::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $definition['title'],
                    'meta_title' => $definition['meta_title'],
                    'meta_description' => $definition['meta_description'],
                    'is_published' => $definition['is_published'],
                    'payload' => $definition['payload'],
                ],
            );
        }
    }

    public function refresh(string $slug): void
    {
        Cache::forget(self::CACHE_PREFIX.$slug);
        $this->findBySlug($slug);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(string $slug, array $payload): array
    {
        unset($payload['hero']['image_url'], $payload['who_we_are']['mediaImage_url']);

        if (in_array($slug, ['privacy', 'terms'], true) && isset($payload['updatedAt'])) {
            $payload['updatedAt'] = $this->normalizeUpdatedAt(
                is_string($payload['updatedAt']) ? $payload['updatedAt'] : null,
            ) ?? $payload['updatedAt'];
        }

        if ($slug === 'about') {
            $payload = $this->renumberList($payload, ['how_it_works', 'steps']);
            $payload = $this->renumberList($payload, ['multi_branch', 'factors']);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $path
     * @return array<string, mixed>
     */
    private function renumberList(array $payload, array $path): array
    {
        $cursor = &$payload;
        foreach ($path as $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                return $payload;
            }
            $cursor = &$cursor[$segment];
        }

        if (! is_array($cursor)) {
            return $payload;
        }

        foreach ($cursor as $index => &$item) {
            if (is_array($item)) {
                $item['number'] = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            }
        }
        unset($item);

        return $payload;
    }

    /**
     * Sync uploads to the media collection. The payload only ever keeps the page's
     * existing static seed path (never client-submitted paths or uploaded file paths).
     *
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $path
     * @return array<string, mixed>
     */
    private function applyImageUpload(
        CmsPage $page,
        array $payload,
        array $path,
        string $collection,
        ?UploadedFile $file,
        bool $remove,
    ): array {
        $key = implode('.', $path);

        $this->media->syncSingle($page, $collection, $file, $remove);

        $stored = data_get($page->payload ?? [], $key);
        $replaced = $remove || $file !== null;
        $staticPath = ! $replaced && is_string($stored) ? $stored : null;

        if ($replaced || $staticPath !== null || Arr::has($payload, $key)) {
            data_set($payload, $key, $staticPath);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function enrichPayloadForForm(CmsPage $page, array $payload): array
    {
        if (isset($payload['updatedAt']) && is_string($payload['updatedAt'])) {
            $payload['updatedAt'] = $this->normalizeUpdatedAt($payload['updatedAt']) ?? $payload['updatedAt'];
        }

        if (isset($payload['hero']) && is_array($payload['hero'])) {
            $payload['hero']['image_url'] = $this->imageUrlFor(
                $page,
                CmsPage::HERO_COLLECTION,
                $payload['hero']['image'] ?? null,
                null,
            );
        }

        if (isset($payload['who_we_are']) && is_array($payload['who_we_are'])) {
            $payload['who_we_are']['mediaImage_url'] = $this->imageUrlFor(
                $page,
                CmsPage::MEDIA_COLLECTION,
                $payload['who_we_are']['mediaImage'] ?? null,
                null,
            );
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function resolvePublicImageUrls(CmsPage $page, array $payload): array
    {
        if (isset($payload['hero']) && is_array($payload['hero'])
            && ($page->hasMedia(CmsPage::HERO_COLLECTION) || is_string($payload['hero']['image'] ?? null))) {
            $payload['hero']['image'] = $this->imageUrlFor(
                $page,
                CmsPage::HERO_COLLECTION,
                $payload['hero']['image'] ?? null,
                MediaUrl::DEFAULT_FALLBACK,
            );
        }

        if (isset($payload['who_we_are']) && is_array($payload['who_we_are'])
            && ($page->hasMedia(CmsPage::MEDIA_COLLECTION) || is_string($payload['who_we_are']['mediaImage'] ?? null))) {
            $payload['who_we_are']['mediaImage'] = $this->imageUrlFor(
                $page,
                CmsPage::MEDIA_COLLECTION,
                $payload['who_we_are']['mediaImage'] ?? null,
                MediaUrl::DEFAULT_FALLBACK,
            );
        }

        return $payload;
    }

    /**
     * Uploaded media wins; otherwise resolve the static seed path stored in the payload.
     */
    private function imageUrlFor(CmsPage $page, string $collection, mixed $staticPath, ?string $fallback): ?string
    {
        $static = is_string($staticPath) ? MediaUrl::resolve($staticPath, $fallback) : $fallback;

        return MediaUrl::fromCollection($page, $collection, $static);
    }

    public function normalizeUpdatedAt(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $value, $matches) === 1) {
            return $matches[1];
        }

        $cleaned = preg_replace('/^Last Updated:\s*/i', '', $value) ?? $value;
        $timestamp = strtotime($cleaned);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function tableReady(): bool
    {
        try {
            return Schema::hasTable('cms_pages');
        } catch (\Throwable) {
            return false;
        }
    }
}
