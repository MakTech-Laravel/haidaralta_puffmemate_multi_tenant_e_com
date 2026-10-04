<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

final class MediaUrl
{
    public const DEFAULT_FALLBACK = '/images/storefront/logo.png';

    /**
     * Resolve the first media URL for a HasMedia model collection.
     */
    public static function fromCollection(
        HasMedia $model,
        string $collection,
        ?string $fallback = self::DEFAULT_FALLBACK,
        string $conversion = '',
    ): ?string {
        $url = $model->getFirstMediaUrl($collection, $conversion);

        if ($url !== '') {
            return $url;
        }

        return $fallback;
    }

    /**
     * Resolve a media value to a browser-usable URL.
     *
     * Order: empty → fallback; http(s) → as-is; existing public path → normalized;
     * existing public-disk path → Storage URL; else → fallback.
     */
    public static function resolve(?string $value, ?string $fallback = self::DEFAULT_FALLBACK): ?string
    {
        if ($value === null || trim($value) === '') {
            return $fallback;
        }

        $value = trim($value);

        if (self::isAbsoluteUrl($value)) {
            return $value;
        }

        $publicPath = self::normalizePublicWebPath($value);

        if ($publicPath !== null && self::publicFileExists($publicPath)) {
            return $publicPath;
        }

        $storagePath = self::normalizeStoragePath($value);

        if ($storagePath !== null && Storage::disk('public')->exists($storagePath)) {
            return Storage::disk('public')->url($storagePath);
        }

        return $fallback;
    }

    /**
     * @param  iterable<int|string, string|null>  $values
     * @return list<string|null>
     */
    public static function resolveMany(iterable $values, ?string $fallback = self::DEFAULT_FALLBACK): array
    {
        $resolved = [];

        foreach ($values as $value) {
            $resolved[] = self::resolve(
                is_string($value) ? $value : null,
                $fallback,
            );
        }

        return $resolved;
    }

    private static function isAbsoluteUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    /**
     * Normalize values like `/images/foo.png` or `images/foo.png` to a leading-slash web path.
     */
    private static function normalizePublicWebPath(string $value): ?string
    {
        $path = str_replace('\\', '/', $value);

        if (str_starts_with($path, '/storage/')) {
            return null;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return '/'.$path;
        }

        return null;
    }

    private static function publicFileExists(string $webPath): bool
    {
        $relative = ltrim($webPath, '/');

        if ($relative === '' || str_contains($relative, '..')) {
            return false;
        }

        return self::isDisplayableFile(public_path($relative));
    }

    /**
     * True when the file exists and starts with a real image header.
     * Rejects empty files and HTTP-fake stubs such as "fake-image-bytes".
     */
    public static function isDisplayableFile(string $absolutePath): bool
    {
        if (! is_file($absolutePath)) {
            return false;
        }

        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 16) ?: '';
        fclose($handle);

        $trimmed = ltrim($header);

        return str_starts_with($header, "\xFF\xD8\xFF")
            || str_starts_with($header, "\x89PNG")
            || str_starts_with($header, 'GIF8')
            || str_starts_with($header, 'RIFF')
            || str_starts_with($trimmed, '<svg')
            || str_starts_with($trimmed, '<?xml');
    }

    /**
     * Treat remaining relative paths (e.g. products/abc.jpg) as public-disk storage keys.
     */
    private static function normalizeStoragePath(string $value): ?string
    {
        $path = ltrim(str_replace('\\', '/', $value), '/');

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path !== '' ? $path : null;
    }
}
