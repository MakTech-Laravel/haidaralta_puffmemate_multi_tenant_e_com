<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public const PLATFORM_GUARD = 'platform';

    public const BRANCH_GUARD = 'branch';

    /**
     * @var list<string>
     */
    public const PLATFORM_PERMISSIONS = [
        'view-dashboard',
        'manage-users',
        'manage-roles',
        'manage-branches',
    ];

    /**
     * @var list<string>
     */
    public const BRANCH_PERMISSIONS = [
        'view-branch-dashboard',
        'manage-branch-products',
        'manage-branch-orders',
        'manage-branch-staff',
    ];

    /**
     * @var list<string>
     */
    private const ROLES = [
        'super-admin',
        'admin',
        'manager',
        'staff',
    ];

    /**
     * Seed permissions and platform-scoped roles.
     */
    public function run(): void
    {
        $this->resetTeamContext();

        $platformPermissions = $this->createPermissions(self::PLATFORM_PERMISSIONS, self::PLATFORM_GUARD);
        $this->createPermissions(self::BRANCH_PERMISSIONS, self::BRANCH_GUARD);

        $this->createGuardRoles(self::PLATFORM_GUARD, $platformPermissions, 'manage-roles');

        $this->resetTeamContext();
    }

    /**
     * Seed branch-scoped roles for a single branch team.
     */
    public function seedBranchRolesForBranch(Branch $branch): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

        $branchPermissions = collect(self::BRANCH_PERMISSIONS)
            ->map(fn (string $name) => Permission::findByName($name, self::BRANCH_GUARD))
            ->all();

        $this->createGuardRoles(self::BRANCH_GUARD, $branchPermissions, 'manage-branch-staff');

        $this->resetTeamContext();
    }

    /**
     * @param  list<string>  $permissionNames
     * @return list<Permission>
     */
    private function createPermissions(array $permissionNames, string $guardName): array
    {
        $permissions = [];

        foreach ($permissionNames as $name) {
            $permissions[] = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guardName,
            ]);
        }

        return $permissions;
    }

    /**
     * @param  list<Permission>  $permissions
     */
    private function createGuardRoles(
        string $guardName,
        array $permissions,
        string $managementPermission,
    ): void {
        $teamForeignKey = config('permission.column_names.team_foreign_key');

        foreach (self::ROLES as $roleName) {
            $roleAttributes = [
                'name' => $roleName,
                'guard_name' => $guardName,
            ];

            if (config('permission.teams')) {
                $roleAttributes[$teamForeignKey] = app(PermissionRegistrar::class)->getPermissionsTeamId();
            }

            $role = Role::firstOrCreate($roleAttributes);

            $rolePermissions = match ($roleName) {
                'super-admin' => [],
                'admin' => $permissions,
                'manager' => collect($permissions)
                    ->reject(fn (Permission $permission) => $permission->name === $managementPermission)
                    ->values()
                    ->all(),
                'staff' => collect($permissions)
                    ->filter(fn (Permission $permission) => in_array($permission->name, [
                        'view-dashboard',
                        'view-branch-dashboard',
                    ], true))
                    ->values()
                    ->all(),
                default => [],
            };

            $role->syncPermissions($rolePermissions);
        }
    }

    private function resetTeamContext(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
