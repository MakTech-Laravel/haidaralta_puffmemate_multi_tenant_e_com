<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\BranchStaffDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreScopedPlatformBranchStaffRequest;
use App\Http\Requests\Platform\UpdatePlatformBranchStaffRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\Branch\BranchStaffService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchScopedStaffController extends Controller
{
    public function __construct(private BranchStaffService $branchStaffService) {}

    /**
     * Display staff for a single branch.
     */
    public function index(Request $request, Branch $branch): Response
    {
        return Inertia::render('platform/branches/staff/index', [
            'branch' => $branch->only(['id', 'name', 'slug', 'code']),
            'table' => BranchStaffDataTable::forBranch($request, $branch),
        ]);
    }

    /**
     * Show the form for creating staff in this branch.
     */
    public function create(Branch $branch): Response
    {
        return Inertia::render('platform/branches/staff/create', [
            'branch' => $branch->only(['id', 'name', 'slug', 'code']),
            'branches' => [
                [
                    'value' => (string) $branch->id,
                    'label' => $branch->name,
                ],
            ],
            'rolesByBranch' => [
                (string) $branch->id => $this->branchStaffService->roleOptionsForBranch($branch),
            ],
        ]);
    }

    /**
     * Store a newly created staff member for this branch.
     */
    public function store(StoreScopedPlatformBranchStaffRequest $request, Branch $branch): RedirectResponse
    {
        $user = $this->branchStaffService->create($branch, [
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'roles' => $request->validated('roles', []),
            'is_active' => $request->boolean('is_active', true),
        ]);

        Toast::success('Branch staff member created.');

        return redirect()->route('platform.branches.staff.show', [$branch, $user]);
    }

    /**
     * Display the specified staff member for this branch.
     */
    public function show(Branch $branch, User $user): Response
    {
        $user = $this->branchStaffService->resolveForBranch($user, $branch);
        $detail = $this->branchStaffService->mapStaffMemberDetail($user, $branch);

        return Inertia::render('platform/branches/staff/show', [
            'branch' => $branch->only(['id', 'name', 'slug', 'code']),
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
     * Show the form for editing staff in this branch.
     */
    public function edit(Branch $branch, User $user): Response
    {
        $user = $this->branchStaffService->resolveForBranch($user, $branch);

        return Inertia::render('platform/branches/staff/edit', [
            'branch' => $branch->only(['id', 'name', 'slug', 'code']),
            'branches' => [
                [
                    'value' => (string) $branch->id,
                    'label' => $branch->name,
                ],
            ],
            'rolesByBranch' => [
                (string) $branch->id => $this->branchStaffService->roleOptionsForBranch($branch),
            ],
            'lockedSuperAdmin' => $this->branchStaffService->isLastSuperAdminInBranch($user, $branch),
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'branch_id' => $branch->id,
                'roles' => $this->branchStaffService->roleNames($user, $branch),
            ],
        ]);
    }

    /**
     * Update the specified staff member for this branch.
     */
    public function update(UpdatePlatformBranchStaffRequest $request, Branch $branch, User $user): RedirectResponse
    {
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

        Toast::success('Branch staff member updated.');

        return redirect()->route('platform.branches.staff.show', [$branch, $user]);
    }

    /**
     * Remove the specified staff member from this branch.
     */
    public function destroy(Branch $branch, User $user): RedirectResponse
    {
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

        Toast::success('Branch staff member deleted.');

        return redirect()->route('platform.branches.staff.index', $branch);
    }
}
