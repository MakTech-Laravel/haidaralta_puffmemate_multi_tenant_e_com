<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class PublicDiskMedia
{
    public const USER_AVATARS = 'users/avatars';

    public const DRIVER_VEHICLES = 'drivers/vehicles';

    public static function store(?UploadedFile $file, string $directory): ?string
    {
        if ($file === null) {
            return null;
        }

        return $file->store($directory, 'public');
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '' || ! str_contains($path, '/')) {
            return;
        }

        if (str_starts_with($path, '/images/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public static function replace(
        ?string $current,
        ?UploadedFile $next,
        string $directory,
        bool $remove = false,
    ): ?string {
        if ($remove) {
            self::delete($current);

            return null;
        }

        if ($next !== null) {
            self::delete($current);

            return self::store($next, $directory);
        }

        return $current;
    }
}
