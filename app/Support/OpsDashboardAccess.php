<?php

namespace App\Support;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

/**
 * Authorization for Laravel Horizon and Pulse dashboards.
 *
 * Platform portal only. Platform super-admins bypass Spatie permissions;
 * other platform roles need view-horizon / view-pulse. Branch super-admins
 * are never allowed (isPlatform() gate).
 */
final class OpsDashboardAccess
{
    public static function allowsHorizon(?User $user): bool
    {
        return self::allows($user, PermissionEnum::VIEW_HORIZON);
    }

    public static function allowsPulse(?User $user): bool
    {
        return self::allows($user, PermissionEnum::VIEW_PULSE);
    }

    public static function allowsObservabilityHub(?User $user): bool
    {
        return self::allowsHorizon($user) || self::allowsPulse($user);
    }

    public static function resolvePortalUser(?Request $request = null): ?User
    {
        if ($request !== null) {
            $user = $request->user();

            if ($user instanceof User) {
                return $user;
            }
        }

        foreach (PortalAuth::PORTAL_GUARDS as $guard) {
            $candidate = Auth::guard($guard)->user();

            if ($candidate instanceof User) {
                return $candidate;
            }
        }

        return null;
    }

    private static function allows(?User $user, PermissionEnum $permission): bool
    {
        if ($user === null || ! $user->isPlatform()) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        return $user->can($permission->value);
    }
}
