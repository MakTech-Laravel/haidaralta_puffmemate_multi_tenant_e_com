<?php

namespace App\Http\Controllers\Branch;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchStaffRequest;
use App\Http\Requests\Branch\UpdateBranchStaffRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
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

        $user->syncRoles([$request->validated('role')]);

        return redirect()
            ->route('branch.staff.index', $branch)
            ->with('success', 'Staff member created.');
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
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'role' => $user->getRoleNames()->first(),
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

        $newRole = $request->validated('role');
        $isActive = $request->boolean('is_active');

        $this->guardLastSuperAdmin($user, $branch, $newRole, $isActive, destroying: false);

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
        $user->syncRoles([$newRole]);

        return redirect()
            ->route('branch.staff.index', $branch)
            ->with('success', 'Staff member updated.');
    }

    /**
     * Remove the specified branch staff member.
     */
    public function destroy(string $branch, User $user): RedirectResponse
    {
        $branch = $this->currentBranch();
        $user = $this->resolveBranchStaff($user, $branch);
        $this->useBranchTeam($branch);

        $this->guardLastSuperAdmin(
            $user,
            $branch,
            $user->getRoleNames()->first() ?? '',
            false,
            destroying: true,
        );

        $user->delete();

        return redirect()
            ->route('branch.staff.index', $branch)
            ->with('success', 'Staff member deleted.');
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
        return collect(RoleEnum::cases())
            ->map(fn (RoleEnum $role): array => [
                'value' => $role->value,
                'label' => str($role->value)->replace('-', ' ')->title()->toString(),
            ])
            ->values()
            ->all();
    }

    private function guardLastSuperAdmin(
        User $user,
        Branch $branch,
        string $newRole,
        bool $isActive,
        bool $destroying,
    ): void {
        $this->useBranchTeam($branch);

        if (! $user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return;
        }

        $losingSuperAdmin = $destroying
            || ! $isActive
            || $newRole !== RoleEnum::SUPER_ADMIN->value;

        if (! $losingSuperAdmin) {
            return;
        }

        $otherSuperAdmins = User::query()
            ->where('user_type', UserType::BRANCH)
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->get()
            ->filter(fn (User $candidate): bool => $candidate->hasRole(RoleEnum::SUPER_ADMIN->value))
            ->count();

        if ($otherSuperAdmins === 0) {
            throw ValidationException::withMessages([
                'role' => 'Cannot remove or deactivate the last super-admin for this branch.',
            ]);
        }
    }
}
