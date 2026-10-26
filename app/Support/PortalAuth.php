<?php

namespace App\Support;

use App\Enums\PermissionEnum;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

/**
 * Portal guard detection and post-login redirect paths.
 */
final class PortalAuth
{
    /**
     * @var list<string>
     */
    public const PORTAL_GUARDS = [
        'platform',
        'branch',
        'driver',
        'customer',
    ];

    /**
     * First matching platform landing path when the user lacks view-dashboard.
     * Order follows common ops priority (orders/catalog/branches before settings).
     *
     * @var list<array{0: list<string>, 1: string}>
     */
    private const PLATFORM_FALLBACK_LANDINGS = [
        [[PermissionEnum::MANAGE_ORDERS->value, PermissionEnum::COMPLETE_ORDERS_WITHOUT_CODE->value], '/platform/orders'],
        [[PermissionEnum::MANAGE_CATALOG->value], '/platform/products'],
        [[PermissionEnum::VIEW_BRANCHES->value, PermissionEnum::MANAGE_BRANCHES->value], '/platform/branches'],
        [[PermissionEnum::CREATE_BRANCHES->value], '/platform/branches/create'],
        [[PermissionEnum::MANAGE_USERS->value], '/platform/customers'],
        [[PermissionEnum::MANAGE_BRANCH_USERS->value], '/platform/branch-staff'],
        [[PermissionEnum::MANAGE_DRIVERS->value], '/platform/drivers'],
        [[PermissionEnum::MANAGE_CONVERSATIONS->value], '/platform/conversations'],
        [[PermissionEnum::MANAGE_WHOLESALE->value], '/platform/wholesale-inquiries'],
        [[PermissionEnum::MANAGE_REPORTS->value], '/platform/reports/order-reports'],
        [[PermissionEnum::MANAGE_BRANCH_INVENTORIES->value], '/platform/stock/manage'],
        [[PermissionEnum::MANAGE_SHIFTS->value], '/platform/branch-shifts'],
        [[PermissionEnum::MANAGE_ROLES->value], '/platform/permissions'],
        [[PermissionEnum::MANAGE_PAGES->value], '/platform/pages'],
        [[PermissionEnum::MANAGE_APPLICATION_SETTINGS->value], '/platform/settings/general'],
        [[PermissionEnum::MANAGE_DATABASE_BACKUPS->value], '/platform/database-backups'],
        [[PermissionEnum::VIEW_ACTIVITY_LOGS->value], '/platform/activity-logs'],
        [[PermissionEnum::VIEW_HORIZON->value, PermissionEnum::VIEW_PULSE->value], '/platform/observability'],
        [[PermissionEnum::VIEW_TRANSFERS->value], '/platform/reports/transfer-history'],
    ];

    /**
     * The name of the portal guard currently authenticated, if any.
     */
    public static function activeGuard(): ?string
    {
        foreach (self::PORTAL_GUARDS as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    /**
     * Dashboard path for the given user after login or when already authenticated.
     */
    public static function dashboardPath(User $user): string
    {
        return match ($user->user_type) {
            UserType::PLATFORM => self::platformLandingPath($user),
            UserType::BRANCH => '/branch/'.$user->branch?->slug.'/dashboard',
            UserType::DRIVER => '/driver/dashboard',
            UserType::CUSTOMER => '/dashboard',
        };
    }

    /**
     * Prefer the platform dashboard when allowed; otherwise the first permitted nav destination.
     */
    public static function platformLandingPath(User $user): string
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

        try {
            if ($user->can(PermissionEnum::VIEW_DASHBOARD->value)) {
                return '/platform/dashboard';
            }

            foreach (self::PLATFORM_FALLBACK_LANDINGS as [$permissions, $path]) {
                foreach ($permissions as $permission) {
                    if ($user->can($permission)) {
                        return $path;
                    }
                }
            }
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }

        // Authenticated platform user with no matching permissions — still land in the portal shell.
        return '/platform/dashboard';
    }
}
