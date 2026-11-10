<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    use ReportsSeedProgress;

    public const PLATFORM_GUARD = 'platform';

    public const BRANCH_GUARD = 'branch';

    /**
     * Seed platform permissions/roles, then re-sync default roles for every existing branch.
     * New branches still get roles via BranchSeeder / NationwideBranchSeeder.
     */
    public function run(): void
    {
        $this->resetTeamContext();

        $platformCases = PermissionEnum::forGuard(self::PLATFORM_GUARD);
        $branchCases = PermissionEnum::forGuard(self::BRANCH_GUARD);
        $branches = Branch::query()->orderBy('id')->get();

        $this->startSeedProgress(
            'Permissions',
            count($platformCases) + count($branchCases) + count(RoleEnum::cases()) + ($branches->count() * count(RoleEnum::cases())),
        );

        $platformPermissions = $this->createPermissions($platformCases);
        $this->createPermissions($branchCases);

        $this->createGuardRoles(
            self::PLATFORM_GUARD,
            $platformPermissions,
            [
                PermissionEnum::MANAGE_ROLES->value,
                PermissionEnum::MANAGE_BRANCH_USERS->value,
                PermissionEnum::MANAGE_DATABASE_BACKUPS->value,
                PermissionEnum::MANAGE_APPLICATION_SETTINGS->value,
                PermissionEnum::MANAGE_PAGES->value,
                PermissionEnum::VIEW_HORIZON->value,
                PermissionEnum::VIEW_PULSE->value,
            ],
        );

        $this->resetTeamContext();

        foreach ($branches as $branch) {
            $this->seedBranchRolesForBranch($branch, reportProgress: true);
        }

        $this->finishSeedProgress(sprintf(
            '%d permissions, %d platform roles, and default roles for %d branches ready.',
            count($platformCases) + count($branchCases),
            count(RoleEnum::cases()),
            $branches->count(),
        ));
    }

    /**
     * Seed branch-scoped roles for a single branch team.
     */
    public function seedBranchRolesForBranch(Branch $branch, bool $reportProgress = false): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

        $branchPermissions = collect(PermissionEnum::forGuard(self::BRANCH_GUARD))
            ->map(fn (PermissionEnum $permission) => Permission::findByName($permission->value, self::BRANCH_GUARD))
            ->all();

        $this->createGuardRoles(
            self::BRANCH_GUARD,
            $branchPermissions,
            [
                PermissionEnum::MANAGE_BRANCH_STAFF->value,
                PermissionEnum::MANAGE_BRANCH_ROLES->value,
            ],
            reportProgress: $reportProgress,
        );

        $this->resetTeamContext();
    }

    /**
     * @param  array<int, PermissionEnum>  $permissionCases
     * @return list<Permission>
     */
    private function createPermissions(array $permissionCases): array
    {
        $permissions = [];

        foreach ($permissionCases as $permissionCase) {
            $permissions[] = Permission::firstOrCreate([
                'name' => $permissionCase->value,
                'guard_name' => $permissionCase->guard(),
            ]);

            $this->advanceSeedProgress();
        }

        return $permissions;
    }

    /**
     * @param  list<Permission>  $permissions
     * @param  list<string>  $managementPermissions
     */
    private function createGuardRoles(
        string $guardName,
        array $permissions,
        array $managementPermissions,
        bool $reportProgress = true,
    ): void {
        $teamForeignKey = config('permission.column_names.team_foreign_key');

        foreach (RoleEnum::cases() as $roleCase) {
            $roleName = $roleCase->value;

            $roleAttributes = [
                'name' => $roleName,
                'guard_name' => $guardName,
            ];

            if (config('permission.teams')) {
                $roleAttributes[$teamForeignKey] = app(PermissionRegistrar::class)->getPermissionsTeamId();
            }

            $role = Role::firstOrCreate($roleAttributes);

            $rolePermissions = match ($roleCase) {
                RoleEnum::SUPER_ADMIN => [],
                RoleEnum::ADMIN => $permissions,
                RoleEnum::MANAGER => collect($permissions)
                    ->reject(fn (Permission $permission) => in_array($permission->name, $managementPermissions, true))
                    ->values()
                    ->all(),
                RoleEnum::STAFF => collect($permissions)
                    ->filter(fn (Permission $permission) => in_array($permission->name, [
                        PermissionEnum::VIEW_DASHBOARD->value,
                        PermissionEnum::VIEW_BRANCH_DASHBOARD->value,
                        PermissionEnum::VIEW_ACTIVITY_LOGS->value,
                        PermissionEnum::VIEW_BRANCH_ACTIVITY_LOGS->value,
                    ], true))
                    ->values()
                    ->all(),
            };

            $role->syncPermissions($rolePermissions);

            if ($reportProgress) {
                $this->advanceSeedProgress();
            }
        }
    }

    private function resetTeamContext(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
