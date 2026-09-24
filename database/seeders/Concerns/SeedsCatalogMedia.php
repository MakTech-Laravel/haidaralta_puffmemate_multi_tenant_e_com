<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

trait SeedsCatalogMedia
{
    protected function resolveImagePath(
        ?string $publicRelative,
        ?string $remoteUrl,
        string $catalogDirectory,
        string $basename,
    ): ?string {
        if (is_string($publicRelative) && $publicRelative !== '') {
            $fromPublic = $this->publicWebPath($publicRelative);

            if ($fromPublic !== null) {
                return $fromPublic;
            }
        }

        if (is_string($remoteUrl) && $remoteUrl !== '') {
            return $this->storeRemoteImage($remoteUrl, $catalogDirectory, $basename);
        }

        return null;
    }

    protected function publicWebPath(string $relativePublicPath): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relativePublicPath), '/');
        $absolute = public_path($relative);

        if (! is_file($absolute)) {
            return null;
        }

        return '/'.$relative;
    }

    protected function storeRemoteImage(string $url, string $catalogDirectory, string $basename): ?string
    {
        $directory = 'images/catalog/'.trim($catalogDirectory, '/');
        $absoluteDirectory = public_path($directory);

        if (! is_dir($absoluteDirectory) && ! mkdir($absoluteDirectory, 0755, true) && ! is_dir($absoluteDirectory)) {
            return null;
        }

        $slug = Str::slug($basename);
        $existing = $this->existingCatalogFile($absoluteDirectory, $slug);

        if ($existing !== null) {
            return '/'.$directory.'/'.$existing;
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'PuffMeMateCatalogSeeder/1.0'])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            $extension = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };

            $filename = $slug.'.'.$extension;
            $absolute = $absoluteDirectory.DIRECTORY_SEPARATOR.$filename;

            file_put_contents($absolute, $response->body());

            return '/'.$directory.'/'.$filename;
        } catch (Throwable) {
            return null;
        }
    }

    protected function existingCatalogFile(string $absoluteDirectory, string $slug): ?string
    {
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $extension) {
            $filename = $slug.'.'.$extension;

            if (is_file($absoluteDirectory.DIRECTORY_SEPARATOR.$filename)) {
                return $filename;
            }
        }

        return null;
    }

    protected function clearObsoleteSeedStorage(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $disk = Storage::disk('public');

        foreach (['categories', 'products'] as $directory) {
            try {
                if ($disk->exists($directory)) {
                    $disk->deleteDirectory($directory);
                }
            } catch (Throwable) {
                // Windows can race on transient public-disk files during tests.
            }
        }
    }
}
