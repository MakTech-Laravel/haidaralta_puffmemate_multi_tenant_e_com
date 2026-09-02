<?php

namespace App\Support;

use App\Enums\RoleEnum;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Authorization helper for Laravel Telescope.
 *
 * Telescope itself is only registered in local/staging (see bootstrap/providers.php).
 * This helper encodes the allow-list used by TelescopeServiceProvider.
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
