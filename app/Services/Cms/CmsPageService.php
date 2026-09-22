<?php

namespace App\Services\Cms;

use App\Models\CmsPage;
use App\Support\Cms\CmsPageDefaults;
use App\Support\MediaUrl;
use App\Support\PublicDiskMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CmsPageService
{
    public const CACHE_PREFIX = 'cms_page.';

    public const MEDIA_DIRECTORY = 'cms';

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
        $directory = self::MEDIA_DIRECTORY.'/'.$slug;

        $payload = $this->applyImageUpload(
            $payload,
            ['hero', 'image'],
            $heroImage,
            $directory.'/hero',
            (bool) ($data['remove_hero_image'] ?? false),
        );

        $payload = $this->applyImageUpload(
            $payload,
            ['who_we_are', 'mediaImage'],
            $mediaImage,
            $directory.'/media',
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
            'payload' => $this->enrichPayloadForForm($page->payload ?? []),
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
        $payload = $this->resolvePublicImageUrls($payload);

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
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $path
     * @return array<string, mixed>
     */
    private function applyImageUpload(
        array $payload,
        array $path,
        ?UploadedFile $file,
        string $directory,
        bool $remove,
    ): array {
        $current = data_get($payload, implode('.', $path));
        $currentPath = is_string($current) ? $current : null;

        $next = PublicDiskMedia::replace($currentPath, $file, $directory, $remove);

        data_set($payload, implode('.', $path), $next);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function enrichPayloadForForm(array $payload): array
    {
        if (isset($payload['updatedAt']) && is_string($payload['updatedAt'])) {
            $payload['updatedAt'] = $this->normalizeUpdatedAt($payload['updatedAt']) ?? $payload['updatedAt'];
        }

        if (isset($payload['hero']) && is_array($payload['hero'])) {
            $image = $payload['hero']['image'] ?? null;
            $payload['hero']['image_url'] = is_string($image)
                ? MediaUrl::resolve($image, fallback: null)
                : null;
        }

        if (isset($payload['who_we_are']) && is_array($payload['who_we_are'])) {
            $image = $payload['who_we_are']['mediaImage'] ?? null;
            $payload['who_we_are']['mediaImage_url'] = is_string($image)
                ? MediaUrl::resolve($image, fallback: null)
                : null;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function resolvePublicImageUrls(array $payload): array
    {
        if (isset($payload['hero']['image']) && is_string($payload['hero']['image'])) {
            $payload['hero']['image'] = MediaUrl::resolve($payload['hero']['image']) ?? $payload['hero']['image'];
        }

        if (isset($payload['who_we_are']['mediaImage']) && is_string($payload['who_we_are']['mediaImage'])) {
            $payload['who_we_are']['mediaImage'] = MediaUrl::resolve($payload['who_we_are']['mediaImage'])
                ?? $payload['who_we_are']['mediaImage'];
        }

        return $payload;
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
