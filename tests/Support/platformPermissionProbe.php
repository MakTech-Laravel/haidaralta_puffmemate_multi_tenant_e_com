<?php

use App\Enums\PermissionEnum;
use App\Models\Branch;
use App\Models\User;
use App\Support\PlatformPermissionProbe;
use Database\Seeders\BranchSeeder;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Build a probe user for the given platform permission (same helper as the seeder).
 *
 * @param  list<PermissionEnum>|null  $onlyPermissions
 */
function platformPermissionProbeUser(PermissionEnum $permission, ?array $onlyPermissions = null): User
{
    return PlatformPermissionProbe::ensureUser($permission, $onlyPermissions);
}

function platformPermissionControlRoute(PermissionEnum $permission): string
{
    return $permission === PermissionEnum::MANAGE_ROLES
        ? 'platform.customers.index'
        : 'platform.permissions.index';
}

/**
 * Whether any registered route middleware string contains the given permission gate.
 */
function platformRouteUsesPermission(string $permission): bool
{
    foreach (RouteFacade::getRoutes() as $route) {
        if (! $route instanceof Route) {
            continue;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if (! str_contains($middleware, 'permission:')) {
                continue;
            }

            // Matches `permission:foo`, `permission:foo|bar`, and `permission:bar|foo`.
            if (preg_match('/(?:^|[:,|])'.preg_quote($permission, '/').'(?:$|[,|])/', $middleware) === 1) {
                return true;
            }
        }
    }

    return false;
}

function platformProbeBranch(): Branch
{
    return Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
}
