<?php

namespace App\Http\Controllers\Branch;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreRoleRequest;
use App\Http\Requests\Branch\UpdateRoleRequest;
use App\Models\Branch;
use App\Models\Role;
use App\Support\Toast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();
        $this->useBranchTeam($branch);

        $filters = $this->resolveFilters($request);

        $roles = $this->rolesQuery($branch, $filters)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Role $role): array => $this->mapRole($role));

        return Inertia::render('branch/roles/index', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'roles' => $roles,
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $branch = $this->currentBranch();
        $this->useBranchTeam($branch);

        $filters = $this->resolveFilters($request);
        $roles = $this->rolesQuery($branch, $filters)->get();
        $filename = 'branch-roles-'.$branch->slug.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($roles): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['id', 'name', 'permissions_count', 'users_count', 'is_system']);

            foreach ($roles as $role) {
                $mapped = $this->mapRole($role);

                fputcsv($handle, [
                    $mapped['id'],
                    $mapped['name'],
                    $mapped['permissions_count'],
                    $mapped['users_count'],
                    $mapped['is_super_admin'] ? 'yes' : 'no',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create(): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/roles/create', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'permissionGroups' => $this->branchPermissionGroups(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->useBranchTeam($branch);

        $teamKey = config('permission.column_names.team_foreign_key');

        $role = Role::query()->create([
            'name' => $request->validated('name'),
            'guard_name' => 'branch',
            $teamKey => $branch->id,
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        Toast::success('Role created.');

        return redirect()->route('branch.roles.show', [$branch, $role]);
    }

    public function show(string $branch, Role $role): Response
    {
        $branch = $this->currentBranch();
        $role = $this->resolveBranchRole($role, $branch);
        $this->useBranchTeam($branch);

        $role->load(['permissions', 'users:id,name,email']);
        $isSuperAdmin = $role->name === RoleEnum::SUPER_ADMIN->value;
        $allPermissions = collect(PermissionEnum::forGuard('branch'));
        $grantedNames = ($isSuperAdmin
            ? $allPermissions->map->value
            : $role->permissions->pluck('name')
        )->flip();

        return Inertia::render('branch/roles/show', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_super_admin' => $isSuperAdmin,
                'permissions_count' => $grantedNames->count(),
                'total_permissions' => $allPermissions->count(),
                'users' => $role->users->map(fn ($user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values(),
            ],
            'permissionGroups' => $this->permissionCoverage($grantedNames),
        ]);
    }

    public function edit(string $branch, Role $role): Response
    {
        $branch = $this->currentBranch();
        $role = $this->resolveBranchRole($role, $branch);
        $this->useBranchTeam($branch);

        $role->load('permissions');

        return Inertia::render('branch/roles/edit', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_super_admin' => $role->name === RoleEnum::SUPER_ADMIN->value,
                'permissions' => $role->permissions->pluck('name')->values(),
            ],
            'permissionGroups' => $this->branchPermissionGroups(),
        ]);
    }

    public function update(UpdateRoleRequest $request, string $branch, Role $role): RedirectResponse
    {
        $branch = $this->currentBranch();
        $role = $this->resolveBranchRole($role, $branch);
        $this->useBranchTeam($branch);

        if ($role->name === RoleEnum::SUPER_ADMIN->value) {
            $role->syncPermissions([]);

            Toast::warning('Super-admin role is managed by gate bypass and cannot be renamed.');

            return redirect()->route('branch.roles.show', [$branch, $role]);
        }

        $role->update([
            'name' => $request->validated('name'),
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        Toast::success('Role updated.');

        return redirect()->route('branch.roles.show', [$branch, $role]);
    }

    public function destroy(string $branch, Role $role): RedirectResponse
    {
        $branch = $this->currentBranch();
        $role = $this->resolveBranchRole($role, $branch);
        $this->useBranchTeam($branch);

        if ($role->name === RoleEnum::SUPER_ADMIN->value) {
            Toast::error('The super-admin role cannot be deleted.');

            return redirect()->back();
        }

        if ($role->users()->exists()) {
            Toast::error('This role still has assigned users and cannot be deleted.');

            return redirect()->back();
        }

        $role->delete();

        Toast::success('Role deleted.');

        return redirect()->route('branch.roles.index', $branch);
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }

    private function useBranchTeam(Branch $branch): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    }

    private function resolveBranchRole(Role $role, Branch $branch): Role
    {
        $teamKey = config('permission.column_names.team_foreign_key');

        if ($role->guard_name !== 'branch' || (int) $role->{$teamKey} !== $branch->id) {
            abort(404);
        }

        return $role;
    }

    /**
     * @return array{search: string, kind: string, users: string, permissions: string}
     */
    private function resolveFilters(Request $request): array
    {
        $kind = strtolower(trim((string) $request->query('kind', '')));
        $users = strtolower(trim((string) $request->query('users', '')));
        $permissions = strtolower(trim((string) $request->query('permissions', '')));

        return [
            'search' => trim((string) $request->query('search', '')),
            'kind' => in_array($kind, ['system', 'custom'], true) ? $kind : '',
            'users' => in_array($users, ['with', 'without'], true) ? $users : '',
            'permissions' => in_array($permissions, ['none', 'some', 'many'], true) ? $permissions : '',
        ];
    }

    /**
     * @param  array{search: string, kind: string, users: string, permissions: string}  $filters
     * @return Builder<Role>
     */
    private function rolesQuery(Branch $branch, array $filters): Builder
    {
        $teamKey = config('permission.column_names.team_foreign_key');
        $manyThreshold = max(2, (int) ceil(count(PermissionEnum::forGuard('branch')) * 0.75));

        return Role::query()
            ->where('guard_name', 'branch')
            ->where($teamKey, $branch->id)
            ->withCount(['permissions', 'users'])
            ->when(
                $filters['search'] !== '',
                fn (Builder $query) => $query->where('name', 'like', '%'.$filters['search'].'%'),
            )
            ->when(
                $filters['kind'] === 'system',
                fn (Builder $query) => $query->where('name', RoleEnum::SUPER_ADMIN->value),
            )
            ->when(
                $filters['kind'] === 'custom',
                fn (Builder $query) => $query->where('name', '!=', RoleEnum::SUPER_ADMIN->value),
            )
            ->when(
                $filters['users'] === 'with',
                fn (Builder $query) => $query->has('users'),
            )
            ->when(
                $filters['users'] === 'without',
                fn (Builder $query) => $query->doesntHave('users'),
            )
            ->when(
                $filters['permissions'] === 'none',
                fn (Builder $query) => $query
                    ->where('name', '!=', RoleEnum::SUPER_ADMIN->value)
                    ->doesntHave('permissions'),
            )
            ->when(
                $filters['permissions'] === 'some',
                fn (Builder $query) => $query
                    ->where('name', '!=', RoleEnum::SUPER_ADMIN->value)
                    ->has('permissions')
                    ->has('permissions', '<', $manyThreshold),
            )
            ->when(
                $filters['permissions'] === 'many',
                fn (Builder $query) => $query->where(function (Builder $builder) use ($manyThreshold): void {
                    $builder->where('name', RoleEnum::SUPER_ADMIN->value)
                        ->orHas('permissions', '>=', $manyThreshold);
                }),
            )
            ->orderBy('name');
    }

    /**
     * @return array{id: int, name: string, permissions_count: int, users_count: int, is_super_admin: bool}
     */
    private function mapRole(Role $role): array
    {
        $isSuperAdmin = $role->name === RoleEnum::SUPER_ADMIN->value;

        return [
            'id' => $role->id,
            'name' => $role->name,
            'permissions_count' => $isSuperAdmin
                ? count(PermissionEnum::forGuard('branch'))
                : (int) $role->permissions_count,
            'users_count' => (int) $role->users_count,
            'is_super_admin' => $isSuperAdmin,
        ];
    }

    /**
     * @param  Collection<string, int>  $grantedNames
     * @return list<array{group: string, total: int, granted_count: int, permissions: list<array{value: string, label: string, granted: bool}>}>
     */
    private function permissionCoverage($grantedNames): array
    {
        $groups = [];

        foreach (PermissionEnum::forGuard('branch') as $permission) {
            $groups[$permission->group()][] = [
                'value' => $permission->value,
                'label' => $permission->label(),
                'granted' => $grantedNames->has($permission->value),
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

    /**
     * @return list<array{group: string, permissions: list<array{value: string, label: string}>}>
     */
    private function branchPermissionGroups(): array
    {
        $groups = [];

        foreach (PermissionEnum::forGuard('branch') as $permission) {
            $groups[$permission->group()][] = [
                'value' => $permission->value,
                'label' => $permission->label(),
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
