<?php

namespace App\Http\Controllers\Branch;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchStaffRequest;
use App\Http\Requests\Branch\UpdateBranchStaffRequest;
use App\Models\Branch;
use App\Models\User;
use App\Support\SuperAdmin;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StaffController extends Controller
{
    /**
     * Display staff for the current branch only.
     */
    public function index(): Response
    {
        $branch = $this->currentBranch();
        $this->useBranchTeam($branch);

        $staff = User::query()
            ->where('user_type', UserType::BRANCH)
            ->where('branch_id', $branch->id)
            ->orderBy('name')
            ->paginate(15)
            ->through(function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'roles' => $user->getRoleNames()->values()->all(),
                ];
            });

        return Inertia::render('branch/staff/index', [
            'staff' => $staff,
            'branch' => $branch->only(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Show the form for creating branch staff.
     */
    public function create(): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/staff/create', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'roles' => $this->branchRoleOptions(),
        ]);
    }

    /**
     * Store a newly created branch staff member.
     */
    public function store(StoreBranchStaffRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
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

        Toast::success('Staff member created.');

        return redirect()->route('branch.staff.show', [$branch, $user]);
    }

    /**
     * Display the specified branch staff member.
     */
    public function show(string $branch, User $user): Response
    {
        $branch = $this->currentBranch();
        $user = $this->resolveBranchStaff($user, $branch);
        $this->useBranchTeam($branch);

        $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);
        $permissionNames = $user->getAllPermissions()->pluck('name');

        return Inertia::render('branch/staff/show', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $user->getRoleNames()->values()->all(),
                'is_super_admin' => $isSuperAdmin,
                'effective_permissions' => $isSuperAdmin
                    ? collect(PermissionEnum::forGuard('branch'))->map->value->values()->all()
                    : $permissionNames->values()->all(),
            ],
        ]);
    }

    /**
     * Show the form for editing branch staff.
     */
    public function edit(string $branch, User $user): Response
    {
        $branch = $this->currentBranch();
        $user = $this->resolveBranchStaff($user, $branch);
        $this->useBranchTeam($branch);

        return Inertia::render('branch/staff/edit', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'roles' => $this->branchRoleOptions(),
            'lockedSuperAdmin' => SuperAdmin::isLastInTeam($user, $branch->id),
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $user->getRoleNames()->values()->all(),
            ],
        ]);
    }

    /**
     * Update the specified branch staff member.
     */
    public function update(UpdateBranchStaffRequest $request, string $branch, User $user): RedirectResponse
    {
        $branch = $this->currentBranch();
        $user = $this->resolveBranchStaff($user, $branch);
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
        $user->syncRoles($roles);

        Toast::success('Staff member updated.');

        return redirect()->route('branch.staff.show', [$branch, $user]);
    }

    /**
     * Remove the specified branch staff member.
     */
    public function destroy(string $branch, User $user): RedirectResponse
    {
        $branch = $this->currentBranch();
        $user = $this->resolveBranchStaff($user, $branch);
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

        Toast::success('Staff member deleted.');

        return redirect()->route('branch.staff.index', $branch);
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

    private function resolveBranchStaff(User $user, Branch $branch): User
    {
        if ($user->user_type !== UserType::BRANCH || $user->branch_id !== $branch->id) {
            abort(404);
        }

        return $user;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function branchRoleOptions(): array
    {
        $branch = $this->currentBranch();
        $this->useBranchTeam($branch);
        $teamKey = config('permission.column_names.team_foreign_key');

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
