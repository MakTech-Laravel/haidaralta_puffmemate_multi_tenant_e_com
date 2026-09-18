<?php

namespace App\Http\Controllers\Branch;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Permission;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PermissionController extends Controller
{
    public function index(): Response
    {
        $branch = $this->currentBranch();
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

        return Inertia::render('branch/permissions/index', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'permissionGroups' => $this->permissionGroups($branch),
        ]);
    }

    public function export(): StreamedResponse
    {
        $branch = $this->currentBranch();
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

        $groups = $this->permissionGroups($branch);
        $filename = 'branch-permissions-'.$branch->slug.'-'.now()->format('Y-m-d').'.csv';

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

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }

    /**
     * @return list<array{group: string, permissions: list<array{value: string, label: string, roles_count: int}>}>
     */
    private function permissionGroups(Branch $branch): array
    {
        $teamKey = config('permission.column_names.team_foreign_key');

        $roleCounts = Permission::query()
            ->where('guard_name', 'branch')
            ->withCount([
                'roles' => fn ($query) => $query
                    ->where('guard_name', 'branch')
                    ->where($teamKey, $branch->id),
            ])
            ->get()
            ->keyBy('name');

        $groups = [];

        foreach (PermissionEnum::forGuard('branch') as $permission) {
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
            ->sortBy('group', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
