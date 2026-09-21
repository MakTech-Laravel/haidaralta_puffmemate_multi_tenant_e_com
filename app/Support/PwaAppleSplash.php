<?php

namespace App\Support;

/**
 * Apple splash links written by pnpm generate:pwa-assets.
 */
final class PwaAppleSplash
{
    /**
     * @return list<array{href: string, media: string}>
     */
    public static function links(): array
    {
        $path = public_path('pwa/apple-splash.json');

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        $links = [];

        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }

            $href = $row['href'] ?? null;
            $media = $row['media'] ?? null;

            if (! is_string($href) || ! is_string($media) || $href === '' || $media === '') {
                continue;
            }

            $links[] = [
                'href' => $href,
                'media' => $media,
            ];
        }

        return $links;
    }
}
