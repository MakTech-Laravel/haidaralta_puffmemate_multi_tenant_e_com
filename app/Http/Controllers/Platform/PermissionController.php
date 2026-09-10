<?php

namespace App\Http\Controllers\Platform;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Support\PlatformTeamResolver;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        return Inertia::render('platform/permissions/index', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Export platform permissions as CSV.
     */
    public function export(): StreamedResponse
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

        $groups = $this->permissionGroups();
        $filename = 'platform-permissions-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($groups): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['group', 'permission', 'key', 'roles_count']);

            foreach ($groups as $group) {
                foreach ($group['permissions'] as $permission) {
                    fputcsv($handle, [
                        $group['group'],
                        $permission['label'],
                        $permission['value'],
                        $permission['roles_count'],
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return list<array{group: string, permissions: list<array{value: string, label: string, roles_count: int}>}>
     */
    private function permissionGroups(): array
    {
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

        return collect($groups)
            ->map(fn (array $permissions, string $group): array => [
                'group' => $group,
                'permissions' => $permissions,
            ])
            ->values()
            ->all();
    }
}
