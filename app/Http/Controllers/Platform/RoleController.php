<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\PlatformRoleDataTable;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Exports\RoleExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportSpreadsheetRequest;
use App\Http\Requests\Platform\StoreRoleRequest;
use App\Http\Requests\Platform\UpdateRoleRequest;
use App\Models\Role;
use App\Support\PlatformTeamResolver;
use App\Support\Toast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RoleController extends Controller
{
    /**
     * Display a paginated list of platform roles.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('platform/roles/index', [
            'table' => PlatformRoleDataTable::make($request),
        ]);
    }

    /**
     * Export filtered platform roles as CSV or Excel.
     */
    public function export(ExportSpreadsheetRequest $request): BinaryFileResponse
    {
        $this->usePlatformTeam();

        $filters = $this->resolveFilters($request);
        $rows = $this->rolesQuery($filters)
            ->get()
            ->map(fn (Role $role): array => $this->mapRole($role))
            ->values();

        return Excel::download(
            new RoleExport($rows),
            $request->filename('platform-roles'),
            $request->exportFormat()->writerType(),
        );
    }

    /**
     * Show the form for creating a new platform role.
     */
    public function create(): Response
    {
        $this->usePlatformTeam();

        return Inertia::render('platform/roles/create', [
            'permissionGroups' => $this->platformPermissionGroups(),
            'existingNames' => $this->existingRoleNames(),
        ]);
    }

    /**
     * Store a newly created platform role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->usePlatformTeam();

        $teamKey = config('permission.column_names.team_foreign_key');

        $role = Role::query()->create([
            'name' => $request->validated('name'),
            'guard_name' => 'platform',
            $teamKey => PlatformTeamResolver::PLATFORM_TEAM_ID,
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        Toast::success('Role created.');

        return redirect()->route('platform.roles.show', $role);
    }

    /**
     * Display the specified platform role.
     */
    public function show(Role $role): Response
    {
        $role = $this->resolvePlatformRole($role);
        $this->usePlatformTeam();

        $role->load(['permissions', 'users:id,name,email']);
        $isSuperAdmin = $role->name === RoleEnum::SUPER_ADMIN->value;
        $allPermissions = collect(PermissionEnum::forGuard('platform'));
        $grantedNames = ($isSuperAdmin
            ? $allPermissions->map->value
            : $role->permissions->pluck('name')
        )->flip();

        return Inertia::render('platform/roles/show', [
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

    /**
     * Show the form for editing the specified platform role.
     */
    public function edit(Role $role): Response
    {
        $role = $this->resolvePlatformRole($role);
        $this->usePlatformTeam();

        $role->load('permissions');

        return Inertia::render('platform/roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_super_admin' => $role->name === RoleEnum::SUPER_ADMIN->value,
                'permissions' => $role->permissions->pluck('name')->values(),
            ],
            'permissionGroups' => $this->platformPermissionGroups(),
            'existingNames' => $this->existingRoleNames($role->id),
        ]);
    }

    /**
     * Update the specified platform role.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role = $this->resolvePlatformRole($role);
        $this->usePlatformTeam();

        if ($role->name === RoleEnum::SUPER_ADMIN->value) {
            $role->syncPermissions([]);

            Toast::warning('Super-admin role is managed by gate bypass and cannot be renamed.');

            return redirect()->route('platform.roles.show', $role);
        }

        $role->update([
            'name' => $request->validated('name'),
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        Toast::success('Role updated.');

        return redirect()->route('platform.roles.show', $role);
    }

    /**
     * Remove the specified platform role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $role = $this->resolvePlatformRole($role);
        $this->usePlatformTeam();

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

        return redirect()->route('platform.roles.index');
    }

    /**
     * @return array{search: string, kind: string, users: string, permissions: string}
     */
    private function resolveFilters(Request $request): array
    {
        $filter = $request->input('filter', []);
        $filter = is_array($filter) ? $filter : [];

        $kind = strtolower(trim((string) ($filter['kind'] ?? $request->query('kind', ''))));
        $users = strtolower(trim((string) ($filter['users'] ?? $request->query('users', ''))));
        $permissions = strtolower(trim((string) ($filter['permissions'] ?? $request->query('permissions', ''))));

        return [
            'search' => trim((string) ($filter['search'] ?? $request->query('search', ''))),
            'kind' => in_array($kind, ['system', 'custom'], true) ? $kind : '',
            'users' => in_array($users, ['with', 'without'], true) ? $users : '',
            'permissions' => in_array($permissions, ['none', 'some', 'many'], true) ? $permissions : '',
        ];
    }

    /**
     * @param  array{search: string, kind: string, users: string, permissions: string}  $filters
     * @return Builder<Role>
     */
    private function rolesQuery(array $filters): Builder
    {
        $manyThreshold = max(2, (int) ceil(count(PermissionEnum::forGuard('platform')) * 0.75));

        return $this->platformRolesQuery()
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
                ? count(PermissionEnum::forGuard('platform'))
                : (int) $role->permissions_count,
            'users_count' => (int) $role->users_count,
            'is_super_admin' => $isSuperAdmin,
        ];
    }

    /**
     * @return list<string>
     */
    private function existingRoleNames(?int $exceptId = null): array
    {
        return $this->platformRolesQuery()
            ->when(
                $exceptId !== null,
                fn (Builder $query) => $query->whereKeyNot($exceptId),
            )
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }

    private function usePlatformTeam(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    }

    /**
     * @return Builder<Role>
     */
    private function platformRolesQuery()
    {
        $teamKey = config('permission.column_names.team_foreign_key');

        return Role::query()
            ->where('guard_name', 'platform')
            ->where($teamKey, PlatformTeamResolver::PLATFORM_TEAM_ID);
    }

    private function resolvePlatformRole(Role $role): Role
    {
        $teamKey = config('permission.column_names.team_foreign_key');

        if ($role->guard_name !== 'platform' || (int) $role->{$teamKey} !== PlatformTeamResolver::PLATFORM_TEAM_ID) {
            abort(404);
        }

        return $role;
    }

    /**
     * @param  Collection<string, int>  $grantedNames
     * @return list<array{group: string, total: int, granted_count: int, permissions: list<array{value: string, label: string, granted: bool}>}>
     */
    private function permissionCoverage($grantedNames): array
    {
        $groups = [];

        foreach (PermissionEnum::forGuard('platform') as $permission) {
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
    private function platformPermissionGroups(): array
    {
        $groups = [];

        foreach (PermissionEnum::forGuard('platform') as $permission) {
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
