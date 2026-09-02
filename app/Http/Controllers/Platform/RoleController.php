<?php

namespace App\Http\Controllers\Platform;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreRoleRequest;
use App\Http\Requests\Platform\UpdateRoleRequest;
use App\Support\PlatformTeamResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /**
     * Display a paginated list of platform roles.
     */
    public function index(Request $request): Response
    {
        $this->usePlatformTeam();

        $search = $request->string('search')->trim()->toString();

        $roles = $this->platformRolesQuery()
            ->withCount(['permissions', 'users'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'is_super_admin' => $role->name === RoleEnum::SUPER_ADMIN->value,
            ]);

        return Inertia::render('platform/roles/index', [
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new platform role.
     */
    public function create(): Response
    {
        return Inertia::render('platform/roles/create', [
            'permissionGroups' => $this->platformPermissionGroups(),
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

        return redirect()
            ->route('platform.roles.show', $role)
            ->with('success', 'Role created.');
    }

    /**
     * Display the specified platform role.
     */
    public function show(Role $role): Response
    {
        $role = $this->resolvePlatformRole($role);
        $this->usePlatformTeam();

        $role->load(['permissions', 'users:id,name,email']);

        return Inertia::render('platform/roles/show', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_super_admin' => $role->name === RoleEnum::SUPER_ADMIN->value,
                'permissions' => $role->permissions->pluck('name')->values(),
                'users' => $role->users->map(fn ($user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values(),
            ],
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

            return redirect()
                ->route('platform.roles.show', $role)
                ->with('success', 'Super-admin role is managed by gate bypass and cannot be renamed.');
        }

        $role->update([
            'name' => $request->validated('name'),
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        return redirect()
            ->route('platform.roles.show', $role)
            ->with('success', 'Role updated.');
    }

    /**
     * Remove the specified platform role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $role = $this->resolvePlatformRole($role);
        $this->usePlatformTeam();

        if ($role->name === RoleEnum::SUPER_ADMIN->value) {
            throw ValidationException::withMessages([
                'role' => 'The super-admin role cannot be deleted.',
            ]);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'This role still has assigned users and cannot be deleted.',
            ]);
        }

        $role->delete();

        return redirect()
            ->route('platform.roles.index')
            ->with('success', 'Role deleted.');
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
