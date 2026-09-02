<?php

namespace App\Http\Controllers\Platform;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Support\PlatformTeamResolver;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    /**
     * Display the read-only platform permission catalog.
     *
     * Gated by manage-roles (same as role management). There is no dedicated
     * view-permissions permission in PermissionEnum today.
     */
    public function index(): Response
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

        $teamKey = config('permission.column_names.team_foreign_key');

        $roleCounts = Permission::query()
            ->where('guard_name', 'platform')
            ->withCount([
                'roles' => fn ($query) => $query
                    ->where('guard_name', 'platform')
                    ->where($teamKey, PlatformTeamResolver::PLATFORM_TEAM_ID),
            ])
            ->get()
            ->keyBy('name');

        $groups = [];

        foreach (PermissionEnum::forGuard('platform') as $permission) {
            $groups[$permission->group()][] = [
                'value' => $permission->value,
                'label' => $permission->label(),
                'roles_count' => (int) ($roleCounts[$permission->value]->roles_count ?? 0),
            ];
        }

        return Inertia::render('platform/permissions/index', [
            'permissionGroups' => collect($groups)
                ->map(fn (array $permissions, string $group): array => [
                    'group' => $group,
                    'permissions' => $permissions,
                ])
                ->values()
                ->all(),
        ]);
    }
}
