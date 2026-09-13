<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchStaffRequest;
use App\Http\Requests\Branch\UpdateBranchStaffRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\Branch\BranchStaffService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(private BranchStaffService $branchStaffService) {}

    /**
     * Display staff for the current branch only.
     */
    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('branch/staff/index', [
            'staff' => $this->branchStaffService->paginateForBranch(
                $branch,
                includeBranch: false,
                search: $search !== '' ? $search : null,
            ),
            'branch' => $branch->only(['id', 'name', 'slug']),
            'filters' => [
                'search' => $search,
            ],
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
            'roles' => $this->branchStaffService->roleOptionsForBranch($branch),
        ]);
    }

    /**
     * Store a newly created branch staff member.
     */
    public function store(StoreBranchStaffRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        $user = $this->branchStaffService->create($branch, [
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'roles' => $request->validated('roles', []),
            'is_active' => $request->boolean('is_active', true),
        ]);

        Toast::success('Staff member created.');

        return redirect()->route('branch.staff.show', [$branch, $user]);
    }

    /**
     * Display the specified branch staff member.
     */
    public function show(string $branch, User $user): Response
    {
        $branch = $this->currentBranch();
        $user = $this->branchStaffService->resolveForBranch($user, $branch);
        $detail = $this->branchStaffService->mapStaffMemberDetail($user, $branch);

        return Inertia::render('branch/staff/show', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $detail['roles'],
                'is_super_admin' => $detail['is_super_admin'],
                'effective_permissions' => $detail['effective_permissions'],
            ],
        ]);
    }

    /**
     * Show the form for editing branch staff.
     */
    public function edit(string $branch, User $user): Response
    {
        $branch = $this->currentBranch();
        $user = $this->branchStaffService->resolveForBranch($user, $branch);

        return Inertia::render('branch/staff/edit', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'roles' => $this->branchStaffService->roleOptionsForBranch($branch),
            'lockedSuperAdmin' => $this->branchStaffService->isLastSuperAdminInBranch($user, $branch),
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $this->branchStaffService->roleNames($user, $branch),
            ],
        ]);
    }

    /**
     * Update the specified branch staff member.
     */
    public function update(UpdateBranchStaffRequest $request, string $branch, User $user): RedirectResponse
    {
        $branch = $this->currentBranch();
        $user = $this->branchStaffService->resolveForBranch($user, $branch);
        $roles = $request->validated('roles', []);
        $isActive = $request->boolean('is_active');

        if ($this->branchStaffService->wouldViolateLastSuperAdmin($user, $branch, $roles, $isActive, destroying: false)) {
            Toast::error('Cannot remove or deactivate the last super-admin for this branch.');

            return redirect()->back();
        }

        $this->branchStaffService->update($user, $branch, [
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'roles' => $roles,
            'is_active' => $isActive,
        ]);

        Toast::success('Staff member updated.');

        return redirect()->route('branch.staff.show', [$branch, $user]);
    }

    /**
     * Remove the specified branch staff member.
     */
    public function destroy(string $branch, User $user): RedirectResponse
    {
        $branch = $this->currentBranch();
        $user = $this->branchStaffService->resolveForBranch($user, $branch);

        if ($this->branchStaffService->wouldViolateLastSuperAdmin(
            $user,
            $branch,
            $this->branchStaffService->roleNames($user, $branch),
            false,
            destroying: true,
        )) {
            Toast::error('Cannot remove or deactivate the last super-admin for this branch.');

            return redirect()->back();
        }

        $this->branchStaffService->delete($user, $branch);

        Toast::success('Staff member deleted.');

        return redirect()->route('branch.staff.index', $branch);
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
