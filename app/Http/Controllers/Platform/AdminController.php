<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\PlatformAdminDataTable;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlatformAdminRequest;
use App\Http\Requests\Platform\UpdatePlatformAdminRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Platform\PlatformAdminService;
use App\Support\MediaUrl;
use App\Support\PlatformTeamResolver;
use App\Support\SuperAdmin;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class AdminController extends Controller
{
    public function __construct(private PlatformAdminService $admins) {}

    /**
     * Display platform administrator accounts.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('platform/admins/index', [
            'table' => PlatformAdminDataTable::make($request),
        ]);
    }

    /**
     * Show the form for creating a platform administrator.
     */
    public function create(): Response
    {
        $this->usePlatformTeam();

        return Inertia::render('platform/admins/create', [
            'roles' => $this->platformRoleOptions(),
        ]);
    }

    /**
     * Store a newly created platform administrator.
     */
    public function store(StorePlatformAdminRequest $request): RedirectResponse
    {
        $this->usePlatformTeam();

        $user = $this->admins->create(
            [
                'name' => $request->validated('name'),
                'phone' => $request->validated('phone'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'roles' => $request->validated('roles', []),
                'is_active' => $request->boolean('is_active', true),
            ],
            $request->file('avatar'),
        );

        Toast::success('Admin created.');

        return redirect()->route('platform.admins.show', $user);
    }

    /**
     * Display the specified platform administrator.
     */
    public function show(User $user): Response
    {
        $user = $this->resolvePlatformAdmin($user);
        $this->usePlatformTeam();

        return Inertia::render('platform/admins/show', [
            'admin' => $this->mapAdmin($user, includeAccess: true),
        ]);
    }

    /**
     * Show the form for editing a platform administrator.
     */
    public function edit(User $user): Response
    {
        $user = $this->resolvePlatformAdmin($user);
        $this->usePlatformTeam();

        return Inertia::render('platform/admins/edit', [
            'roles' => $this->platformRoleOptions(),
            'lockedSuperAdmin' => SuperAdmin::isLastInTeam($user, PlatformTeamResolver::PLATFORM_TEAM_ID),
            'admin' => $this->mapAdmin($user, includeAccess: false),
        ]);
    }

    /**
     * Update the specified platform administrator.
     */
    public function update(UpdatePlatformAdminRequest $request, User $user): RedirectResponse
    {
        $user = $this->resolvePlatformAdmin($user);
        $this->usePlatformTeam();

        $roles = $request->validated('roles', []);
        $isActive = $request->boolean('is_active');

        if ($redirect = $this->guardLastSuperAdmin($user, $roles, $isActive, destroying: false)) {
            return $redirect;
        }

        $this->admins->update(
            $user,
            [
                'name' => $request->validated('name'),
                'phone' => $request->validated('phone'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'roles' => $roles,
                'is_active' => $isActive,
                'remove_avatar' => $request->boolean('remove_avatar'),
            ],
            $request->file('avatar'),
        );

        Toast::success('Admin updated.');

        return redirect()->route('platform.admins.show', $user);
    }

    /**
     * Remove the specified platform administrator.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user = $this->resolvePlatformAdmin($user);
        $this->usePlatformTeam();

        if ($redirect = $this->guardLastSuperAdmin(
            $user,
            $user->getRoleNames()->all(),
            false,
            destroying: true,
        )) {
            return $redirect;
        }

        $this->admins->delete($user);

        Toast::success('Admin deleted.');

        return redirect()->route('platform.admins.index');
    }

    private function usePlatformTeam(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    }

    private function resolvePlatformAdmin(User $user): User
    {
        if ($user->user_type !== UserType::PLATFORM) {
            abort(404);
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAdmin(User $user, bool $includeAccess): array
    {
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'avatar_url' => MediaUrl::resolve($user->avatar, fallback: null),
            'is_active' => $user->is_active,
            'roles' => $user->getRoleNames()->values()->all(),
            'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];

        if (! $includeAccess) {
            return $payload;
        }

        $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);

        $payload['is_super_admin'] = $isSuperAdmin;
        $payload['effective_permissions'] = $isSuperAdmin
            ? collect(PermissionEnum::forGuard('platform'))->map->value->values()->all()
            : $user->getAllPermissions()->pluck('name')->values()->all();
        $payload['permission_groups'] = $this->permissionCoverage(
            collect($payload['effective_permissions']),
        );

        return $payload;
    }

    /**
     * @param  Collection<int, string>  $grantedNames
     * @return list<array{group: string, total: int, granted_count: int, permissions: list<array{value: string, label: string, granted: bool}>}>
     */
    private function permissionCoverage(Collection $grantedNames): array
    {
        $groups = [];

        foreach (PermissionEnum::forGuard('platform') as $permission) {
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

    /**
     * @return list<array{value: string, label: string}>
     */
    private function platformRoleOptions(): array
    {
        $this->usePlatformTeam();
        $teamKey = config('permission.column_names.team_foreign_key');

        return Role::query()
            ->where('guard_name', 'platform')
            ->where($teamKey, PlatformTeamResolver::PLATFORM_TEAM_ID)
            ->orderBy('name')
            ->get(['name'])
            ->map(fn (Role $role): array => [
                'value' => $role->name,
                'label' => str($role->name)->replace('-', ' ')->title()->toString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $roles
     */
    private function guardLastSuperAdmin(
        User $user,
        array $roles,
        bool $isActive,
        bool $destroying,
    ): ?RedirectResponse {
        $this->usePlatformTeam();

        if (! $user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return null;
        }

        $losingSuperAdmin = $destroying
            || ! $isActive
            || ! in_array(RoleEnum::SUPER_ADMIN->value, $roles, true);

        if (! $losingSuperAdmin) {
            return null;
        }

        if (SuperAdmin::isLastInTeam($user, PlatformTeamResolver::PLATFORM_TEAM_ID)) {
            Toast::error('Cannot remove or deactivate the last platform super-admin.');

            return redirect()->back();
        }

        return null;
    }
}
