<?php

namespace App\Services\Branch;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Support\SuperAdmin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\PermissionRegistrar;

class BranchStaffService
{
    /**
     * @param  array{name: string, email: string, password: string, roles?: list<string>, is_active?: bool}  $data
     */
    public function create(Branch $branch, array $data): User
    {
        $this->useBranchTeam($branch);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'user_type' => UserType::BRANCH,
            'branch_id' => $branch->id,
            'is_active' => $data['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($data['roles'] ?? []);

        $this->clearTeam();

        return $user;
    }

    /**
     * @param  array{name: string, email: string, password?: string|null, roles: list<string>, is_active: bool}  $data
     */
    public function update(User $user, Branch $branch, array $data): User
    {
        $this->useBranchTeam($branch);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
            'user_type' => UserType::BRANCH,
            'branch_id' => $branch->id,
        ];

        if (filled($data['password'] ?? null)) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user->unsetRelation('roles');
        $user->syncRoles($data['roles']);

        $this->clearTeam();

        return $user;
    }

    public function delete(User $user, Branch $branch): void
    {
        $this->useBranchTeam($branch);

        $user->delete();

        $this->clearTeam();
    }

    public function resolveForBranch(User $user, Branch $branch): User
    {
        if ($user->user_type !== UserType::BRANCH || $user->branch_id !== $branch->id) {
            abort(404);
        }

        return $user;
    }

    /**
     * Resolve a branch-type staff user (any branch). Used by the global platform staff UI.
     */
    public function resolveBranchStaff(User $user): User
    {
        if ($user->user_type !== UserType::BRANCH) {
            abort(404);
        }

        return $user;
    }

    /**
     * @param  list<string>  $roles
     */
    public function wouldViolateLastSuperAdmin(
        User $user,
        Branch $branch,
        array $roles,
        bool $isActive,
        bool $destroying,
    ): bool {
        $this->useBranchTeam($branch);

        if (! $user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return false;
        }

        $losingSuperAdmin = $destroying
            || ! $isActive
            || ! in_array(RoleEnum::SUPER_ADMIN->value, $roles, true);

        if (! $losingSuperAdmin) {
            return false;
        }

        return SuperAdmin::isLastInTeam($user, $branch->id);
    }

    public function isLastSuperAdminInBranch(User $user, Branch $branch): bool
    {
        return SuperAdmin::isLastInTeam($user, $branch->id);
    }

    /**
     * @return list<string>
     */
    public function roleNames(User $user, Branch $branch): array
    {
        $this->useBranchTeam($branch);

        $roles = $user->getRoleNames()->values()->all();

        $this->clearTeam();

        return $roles;
    }

    public function paginateForBranch(Branch $branch, int $perPage = 15, bool $includeBranch = true): LengthAwarePaginator
    {
        $this->useBranchTeam($branch);

        $staff = User::query()
            ->where('user_type', UserType::BRANCH)
            ->where('branch_id', $branch->id)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (User $user) use ($branch, $includeBranch): array {
                $row = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'roles' => $user->getRoleNames()->values()->all(),
                ];

                if ($includeBranch) {
                    $row['branch'] = $branch->only(['id', 'name', 'slug']);
                }

                return $row;
            });

        $this->clearTeam();

        return $staff;
    }

    public function paginateAcrossBranches(?int $branchId, int $perPage = 15): LengthAwarePaginator
    {
        $staff = User::query()
            ->where('user_type', UserType::BRANCH)
            ->with('branch:id,name,slug')
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (User $user): array {
                $roles = [];

                if ($user->branch_id !== null) {
                    app(PermissionRegistrar::class)->setPermissionsTeamId($user->branch_id);
                    $roles = $user->getRoleNames()->values()->all();
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'branch' => $user->branch?->only(['id', 'name', 'slug']),
                    'roles' => $roles,
                ];
            });

        $this->clearTeam();

        return $staff;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function roleOptionsForBranch(Branch $branch): array
    {
        $teamKey = config('permission.column_names.team_foreign_key');
        $this->useBranchTeam($branch);

        $roles = Role::query()
            ->where('guard_name', 'branch')
            ->where($teamKey, $branch->id)
            ->orderBy('name')
            ->get(['name'])
            ->map(fn (Role $role): array => [
                'value' => $role->name,
                'label' => str($role->name)->replace('-', ' ')->title()->toString(),
            ])
            ->values()
            ->all();

        $this->clearTeam();

        return $roles;
    }

    /**
     * @return array<string, list<array{value: string, label: string}>>
     */
    public function rolesByBranch(): array
    {
        $teamKey = config('permission.column_names.team_foreign_key');
        $map = [];

        foreach (Branch::query()->orderBy('name')->get(['id', 'name']) as $branch) {
            $this->useBranchTeam($branch);

            $map[(string) $branch->id] = Role::query()
                ->where('guard_name', 'branch')
                ->where($teamKey, $branch->id)
                ->orderBy('name')
                ->get(['name'])
                ->map(fn (Role $role): array => [
                    'value' => $role->name,
                    'label' => str($role->name)->replace('-', ' ')->title()->toString(),
                ])
                ->values()
                ->all();
        }

        $this->clearTeam();

        return $map;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function branchOptions(): array
    {
        return Branch::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Branch $branch): array => [
                'value' => (string) $branch->id,
                'label' => $branch->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{roles: list<string>, is_super_admin: bool, effective_permissions: list<string>}
     */
    public function mapStaffMemberDetail(User $user, Branch $branch): array
    {
        $this->useBranchTeam($branch);

        $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);
        $roles = $user->getRoleNames()->values()->all();
        $effectivePermissions = $isSuperAdmin
            ? collect(PermissionEnum::forGuard('branch'))->map->value->values()->all()
            : $user->getAllPermissions()->pluck('name')->values()->all();

        $this->clearTeam();

        return [
            'roles' => $roles,
            'is_super_admin' => $isSuperAdmin,
            'effective_permissions' => $effectivePermissions,
        ];
    }

    private function useBranchTeam(Branch $branch): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    }

    private function clearTeam(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
