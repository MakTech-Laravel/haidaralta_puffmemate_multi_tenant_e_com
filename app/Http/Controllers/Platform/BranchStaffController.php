<?php

namespace App\Http\Controllers\Platform;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlatformBranchStaffRequest;
use App\Http\Requests\Platform\UpdatePlatformBranchStaffRequest;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Support\SuperAdmin;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class BranchStaffController extends Controller
{
    /**
     * Display branch staff across all branches.
     */
    public function index(Request $request): Response
    {
        $branchId = $request->integer('branch_id') ?: null;

        $staff = User::query()
            ->where('user_type', UserType::BRANCH)
            ->with('branch:id,name,slug')
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->paginate(15)
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

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return Inertia::render('platform/branch-staff/index', [
            'staff' => $staff,
            'branches' => Branch::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'filters' => [
                'branch_id' => $branchId,
            ],
        ]);
    }

    /**
     * Show the form for creating branch staff from the platform.
     */
    public function create(): Response
    {
        return Inertia::render('platform/branch-staff/create', [
            'branches' => $this->branchOptions(),
            'rolesByBranch' => $this->rolesByBranch(),
        ]);
    }

    /**
     * Store a newly created branch staff member.
     */
    public function store(StorePlatformBranchStaffRequest $request): RedirectResponse
    {
        $branch = $request->branch();
        $this->useBranchTeam($branch);

        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'user_type' => UserType::BRANCH,
            'branch_id' => $branch->id,
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($request->validated('roles', []));

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        Toast::success('Branch staff member created.');

        return redirect()->route('platform.branch-staff.show', $user);
    }

    /**
     * Display a single branch staff member.
     */
    public function show(User $user): Response
    {
        $user = $this->resolveBranchStaff($user);
        $user->load('branch:id,name,slug');

        $roles = [];
        $isSuperAdmin = false;
        $effectivePermissions = [];

        if ($user->branch_id !== null) {
            $this->useBranchTeam($user->branch);
            $roles = $user->getRoleNames()->values()->all();
            $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);
            $effectivePermissions = $isSuperAdmin
                ? collect(PermissionEnum::forGuard('branch'))->map->value->values()->all()
                : $user->getAllPermissions()->pluck('name')->values()->all();
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return Inertia::render('platform/branch-staff/show', [
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'branch' => $user->branch?->only(['id', 'name', 'slug']),
                'roles' => $roles,
                'is_super_admin' => $isSuperAdmin,
                'effective_permissions' => $effectivePermissions,
            ],
        ]);
    }

    /**
     * Show the form for editing branch staff.
     */
    public function edit(User $user): Response
    {
        $user = $this->resolveBranchStaff($user);
        $user->load('branch:id,name,slug');

        abort_unless($user->branch_id !== null && $user->branch !== null, 404);

        $branch = $user->branch;
        $this->useBranchTeam($branch);

        $payload = [
            'branches' => [
                [
                    'value' => (string) $branch->id,
                    'label' => $branch->name,
                ],
            ],
            'rolesByBranch' => [
                (string) $branch->id => $this->roleOptionsForBranch($branch),
            ],
            'lockedSuperAdmin' => SuperAdmin::isLastInTeam($user, $branch->id),
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'branch_id' => $branch->id,
                'roles' => $user->getRoleNames()->values()->all(),
            ],
        ];

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return Inertia::render('platform/branch-staff/edit', $payload);
    }

    /**
     * Update the specified branch staff member.
     */
    public function update(UpdatePlatformBranchStaffRequest $request, User $user): RedirectResponse
    {
        $user = $this->resolveBranchStaff($user);
        abort_unless($user->branch_id !== null && $user->branch !== null, 404);

        $branch = $user->branch;
        $this->useBranchTeam($branch);

        $roles = $request->validated('roles', []);
        $isActive = $request->boolean('is_active');

        if ($redirect = $this->guardLastSuperAdmin($user, $branch, $roles, $isActive, destroying: false)) {
            return $redirect;
        }

        $payload = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'is_active' => $isActive,
            'user_type' => UserType::BRANCH,
            'branch_id' => $branch->id,
        ];

        if (filled($request->validated('password'))) {
            $payload['password'] = $request->validated('password');
        }

        $user->update($payload);
        $user->unsetRelation('roles');
        $user->syncRoles($roles);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        Toast::success('Branch staff member updated.');

        return redirect()->route('platform.branch-staff.show', $user);
    }

    /**
     * Remove the specified branch staff member.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user = $this->resolveBranchStaff($user);
        abort_unless($user->branch_id !== null && $user->branch !== null, 404);

        $branch = $user->branch;
        $this->useBranchTeam($branch);

        if ($redirect = $this->guardLastSuperAdmin(
            $user,
            $branch,
            $user->getRoleNames()->all(),
            false,
            destroying: true,
        )) {
            return $redirect;
        }

        $user->delete();

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        Toast::success('Branch staff member deleted.');

        return redirect()->route('platform.branch-staff.index');
    }

    private function resolveBranchStaff(User $user): User
    {
        if ($user->user_type !== UserType::BRANCH) {
            abort(404);
        }

        return $user;
    }

    private function useBranchTeam(?Branch $branch): void
    {
        if ($branch === null) {
            return;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function branchOptions(): array
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
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function rolesByBranch(): array
    {
        $teamKey = config('permission.column_names.team_foreign_key');
        $map = [];

        foreach (Branch::query()->orderBy('name')->get(['id', 'name']) as $branch) {
            $map[(string) $branch->id] = $this->roleOptionsForBranch($branch, $teamKey);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return $map;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptionsForBranch(Branch $branch, ?string $teamKey = null): array
    {
        $teamKey ??= config('permission.column_names.team_foreign_key');
        $this->useBranchTeam($branch);

        return Role::query()
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

    /**
     * @param  list<string>  $roles
     */
    private function guardLastSuperAdmin(
        User $user,
        Branch $branch,
        array $roles,
        bool $isActive,
        bool $destroying,
    ): ?RedirectResponse {
        $this->useBranchTeam($branch);

        if (! $user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return null;
        }

        $losingSuperAdmin = $destroying
            || ! $isActive
            || ! in_array(RoleEnum::SUPER_ADMIN->value, $roles, true);

        if (! $losingSuperAdmin) {
            return null;
        }

        if (SuperAdmin::isLastInTeam($user, $branch->id)) {
            Toast::error('Cannot remove or deactivate the last super-admin for this branch.');

            return redirect()->back();
        }

        return null;
    }
}
