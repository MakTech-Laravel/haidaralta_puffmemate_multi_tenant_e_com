<?php

namespace App\Http\Controllers\Platform;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlatformStaffRequest;
use App\Http\Requests\Platform\UpdatePlatformStaffRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use App\Support\SuperAdmin;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class StaffController extends Controller
{
    /**
     * Display platform staff accounts.
     */
    public function index(Request $request): Response
    {
        $this->usePlatformTeam();

        $search = $request->string('search')->trim()->toString();

        $staff = (
            $search !== ''
                ? User::search($search)
                    ->where('user_type', UserType::PLATFORM->value)
                    ->orderBy('name')
                    ->paginate(15)
                : User::query()
                    ->where('user_type', UserType::PLATFORM)
                    ->orderBy('name')
                    ->paginate(15)
        )
            ->withQueryString()
            ->through(function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'roles' => $user->getRoleNames()->values()->all(),
                ];
            });

        return Inertia::render('platform/staff/index', [
            'staff' => $staff,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating platform staff.
     */
    public function create(): Response
    {
        $this->usePlatformTeam();

        return Inertia::render('platform/staff/create', [
            'roles' => $this->platformRoleOptions(),
        ]);
    }

    /**
     * Store a newly created platform staff member.
     */
    public function store(StorePlatformStaffRequest $request): RedirectResponse
    {
        $this->usePlatformTeam();

        $user = User::query()->create([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'user_type' => UserType::PLATFORM,
            'branch_id' => null,
            'is_active' => $request->boolean('is_active', true),
            'phone_verified_at' => now(),
        ]);

        $user->syncRoles($request->validated('roles', []));

        Toast::success('Staff member created.');

        return redirect()->route('platform.staff.show', $user);
    }

    /**
     * Display the specified platform staff member.
     */
    public function show(User $user): Response
    {
        $user = $this->resolvePlatformStaff($user);
        $this->usePlatformTeam();

        $permissionNames = $user->getAllPermissions()->pluck('name');
        $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);

        return Inertia::render('platform/staff/show', [
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $user->getRoleNames()->values()->all(),
                'is_super_admin' => $isSuperAdmin,
                'effective_permissions' => $isSuperAdmin
                    ? collect(PermissionEnum::forGuard('platform'))->map->value->values()->all()
                    : $permissionNames->values()->all(),
            ],
        ]);
    }

    /**
     * Show the form for editing platform staff.
     */
    public function edit(User $user): Response
    {
        $user = $this->resolvePlatformStaff($user);
        $this->usePlatformTeam();

        return Inertia::render('platform/staff/edit', [
            'roles' => $this->platformRoleOptions(),
            'lockedSuperAdmin' => SuperAdmin::isLastInTeam($user, PlatformTeamResolver::PLATFORM_TEAM_ID),
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $user->getRoleNames()->values()->all(),
            ],
        ]);
    }

    /**
     * Update the specified platform staff member.
     */
    public function update(UpdatePlatformStaffRequest $request, User $user): RedirectResponse
    {
        $user = $this->resolvePlatformStaff($user);
        $this->usePlatformTeam();

        $roles = $request->validated('roles', []);
        $isActive = $request->boolean('is_active');

        if ($redirect = $this->guardLastSuperAdmin($user, $roles, $isActive, destroying: false)) {
            return $redirect;
        }

        $payload = [
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'is_active' => $isActive,
            'user_type' => UserType::PLATFORM,
            'branch_id' => null,
        ];

        if (filled($request->validated('password'))) {
            $payload['password'] = $request->validated('password');
        }

        $user->update($payload);
        $user->syncRoles($roles);

        Toast::success('Staff member updated.');

        return redirect()->route('platform.staff.show', $user);
    }

    /**
     * Remove the specified platform staff member.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user = $this->resolvePlatformStaff($user);
        $this->usePlatformTeam();

        if ($redirect = $this->guardLastSuperAdmin(
            $user,
            $user->getRoleNames()->all(),
            false,
            destroying: true,
        )) {
            return $redirect;
        }

        $user->delete();

        Toast::success('Staff member deleted.');

        return redirect()->route('platform.staff.index');
    }

    private function usePlatformTeam(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    }

    private function resolvePlatformStaff(User $user): User
    {
        if ($user->user_type !== UserType::PLATFORM) {
            abort(404);
        }

        return $user;
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
