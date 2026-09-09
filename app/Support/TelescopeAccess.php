<?php

namespace App\Support;

use App\Enums\RoleEnum;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Authorization helper for Laravel Telescope.
 *
 * Telescope itself is only registered in local/staging when TELESCOPE_ENABLED=true
 * (see bootstrap/providers.php — both Laravel\Telescope\TelescopeServiceProvider and
 * App\Providers\TelescopeServiceProvider must be registered; the package is dont-discover).
 * Local HTTP access is open; staging uses this allow-list (platform super-admins).
 */
final class TelescopeAccess
{
    public static function allows(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (! app()->environment('local', 'staging')) {
            return false;
        }

        if (! $user->isPlatform()) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

        return $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }
}
