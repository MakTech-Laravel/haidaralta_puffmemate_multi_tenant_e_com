<?php

namespace App\Services\Branch;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\Media\MediaUploadService;
use App\Support\SuperAdmin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class BranchStaffService
{
    public function __construct(private MediaUploadService $media) {}

    /**
     * @param  array{name: string, phone: string, email?: string|null, password: string, roles?: list<string>, is_active?: bool}  $data
     */
    public function create(Branch $branch, array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($branch, $data, $avatar): User {
            $this->useBranchTeam($branch);

            $user = User::query()->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'user_type' => UserType::BRANCH,
                'branch_id' => $branch->id,
                'is_active' => $data['is_active'] ?? true,
                'phone_verified_at' => now(),
            ]);

            $this->media->syncSingle($user, 'avatar', $avatar);
            $user->syncRoles($data['roles'] ?? []);

            $this->clearTeam();

            return $user;
        });
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null, password?: string|null, roles: list<string>, is_active: bool, remove_avatar?: bool}  $data
     */
    public function update(User $user, Branch $branch, array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($user, $branch, $data, $avatar): User {
            $this->useBranchTeam($branch);

            $payload = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'is_active' => $data['is_active'],
                'user_type' => UserType::BRANCH,
                'branch_id' => $branch->id,
            ];

            if (filled($data['password'] ?? null)) {
                $payload['password'] = $data['password'];
            }

            $user->update($payload);
            $this->media->syncSingle(
                $user,
                'avatar',
                $avatar,
                (bool) ($data['remove_avatar'] ?? false),
            );
            $user->unsetRelation('roles');
            $user->syncRoles($data['roles']);

            $this->clearTeam();

            return $user->refresh();
        });
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

        return SuperAdmin::isLastInActiveBranch($user, $branch);
    }

    public function isLastSuperAdminInBranch(User $user, Branch $branch): bool
    {
        return SuperAdmin::isLastInActiveBranch($user, $branch);
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

    /**
     * @return array<string, mixed>
     */
    public function mapStaffMember(User $user, Branch $branch, bool $includeAccess = false): array
    {
        $this->useBranchTeam($branch);

        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'avatar' => $user->hasMedia('avatar') ? 'avatar' : null,
            'avatar_url' => $user->avatarUrl(),
            'is_active' => $user->is_active,
            'branch_id' => $branch->id,
            'branch' => $branch->only(['id', 'name', 'slug']),
            'roles' => $user->getRoleNames()->values()->all(),
            'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];

        if (! $includeAccess) {
            $this->clearTeam();

            return $payload;
        }

        $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);
        $effectivePermissions = $isSuperAdmin
            ? collect(PermissionEnum::forGuard('branch'))->map->value->values()->all()
            : $user->getAllPermissions()->pluck('name')->values()->all();

        $payload['is_super_admin'] = $isSuperAdmin;
        $payload['effective_permissions'] = $effectivePermissions;
        $payload['permission_groups'] = $this->permissionCoverage(
            collect($effectivePermissions),
        );

        $this->clearTeam();

        return $payload;
    }

    public function paginateForBranch(
        Branch $branch,
        int $perPage = 15,
        bool $includeBranch = true,
        ?string $search = null,
    ): LengthAwarePaginator {
        $this->useBranchTeam($branch);

        $search = trim((string) $search);

        $staff = (
            $search !== ''
                ? User::search($search)
                    ->where('user_type', UserType::BRANCH->value)
                    ->where('branch_id', $branch->id)
                    ->orderBy('name')
                    ->paginate($perPage)
                : User::query()
                    ->where('user_type', UserType::BRANCH)
                    ->where('branch_id', $branch->id)
                    ->orderBy('name')
                    ->paginate($perPage)
        )
            ->withQueryString()
            ->through(function (User $user) use ($branch, $includeBranch): array {
                $row = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'avatar_url' => $user->avatarUrl(),
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

    public function paginateAcrossBranches(
        ?int $branchId,
        int $perPage = 15,
        ?string $search = null,
    ): LengthAwarePaginator {
        $search = trim((string) $search);

        $staff = (
            $search !== ''
                ? User::search($search)
                    ->where('user_type', UserType::BRANCH->value)
                    ->when(
                        $branchId !== null,
                        fn ($builder) => $builder->where('branch_id', $branchId),
                    )
                    ->orderBy('name')
                    ->paginate($perPage)
                : User::query()
                    ->where('user_type', UserType::BRANCH)
                    ->with('branch:id,name,slug')
                    ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
                    ->orderBy('name')
                    ->paginate($perPage)
        );

        if ($search !== '') {
            $staff->getCollection()->load('branch:id,name,slug');
        }

        $staff = $staff
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
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'avatar_url' => $user->avatarUrl(),
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
     * @param  Collection<int, string>  $grantedNames
     * @return list<array{group: string, total: int, granted_count: int, permissions: list<array{value: string, label: string, granted: bool}>}>
     */
    private function permissionCoverage(Collection $grantedNames): array
    {
        $groups = [];

        foreach (PermissionEnum::forGuard('branch') as $permission) {
            $groups[$permission->group()][] = [
                'value' => $permission->value,
                'label' => $permission->label(),
                'granted' => $grantedNames->contains($permission->value),
            ];
        }

        return collect($groups)
            ->map(fn (array $permissions, string $group): array => [
                'group' => $group,
                'total' => count($permissions),
                'granted_count' => collect($permissions)->where('granted', true)->count(),
                'permissions' => $permissions,
            ])
            ->values()
            ->all();
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
