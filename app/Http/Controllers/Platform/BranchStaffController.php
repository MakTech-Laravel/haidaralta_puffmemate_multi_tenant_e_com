<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\BranchStaffDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlatformBranchStaffRequest;
use App\Http\Requests\Platform\UpdatePlatformBranchStaffRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\Branch\BranchStaffService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchStaffController extends Controller
{
    public function __construct(private BranchStaffService $branchStaffService) {}

    /**
     * Display branch staff across all branches.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('platform/branch-staff/index', [
            'table' => BranchStaffDataTable::acrossBranches($request),
        ]);
    }

    /**
     * Show the form for creating branch staff from the platform.
     */
    public function create(): Response
    {
        return Inertia::render('platform/branch-staff/create', [
            'branches' => $this->branchStaffService->branchOptions(),
            'rolesByBranch' => $this->branchStaffService->rolesByBranch(),
        ]);
    }

    /**
     * Store a newly created branch staff member.
     */
    public function store(StorePlatformBranchStaffRequest $request): RedirectResponse
    {
        $user = $this->branchStaffService->create($request->branch(), [
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'roles' => $request->validated('roles', []),
            'is_active' => $request->boolean('is_active', true),
        ]);

        Toast::success('Branch staff member created.');

        return redirect()->route('platform.branch-staff.show', $user);
    }

    /**
     * Display a single branch staff member.
     */
    public function show(User $user): Response
    {
        $user = $this->branchStaffService->resolveBranchStaff($user);
        $user->load('branch:id,name,slug');

        $roles = [];
        $isSuperAdmin = false;
        $effectivePermissions = [];

        if ($user->branch_id !== null && $user->branch !== null) {
            $detail = $this->branchStaffService->mapStaffMemberDetail($user, $user->branch);
            $roles = $detail['roles'];
            $isSuperAdmin = $detail['is_super_admin'];
            $effectivePermissions = $detail['effective_permissions'];
        }

        return Inertia::render('platform/branch-staff/show', [
            'staffMember' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
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
        $user = $this->branchStaffService->resolveBranchStaff($user);
        $user->load('branch:id,name,slug');

        abort_unless($user->branch_id !== null && $user->branch !== null, 404);

        $branch = $user->branch;

        return Inertia::render('platform/branch-staff/edit', [
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
     * Update the specified branch staff member.
     */
    public function update(UpdatePlatformBranchStaffRequest $request, User $user): RedirectResponse
    {
        $user = $this->branchStaffService->resolveBranchStaff($user);
        abort_unless($user->branch_id !== null && $user->branch !== null, 404);

        $branch = $user->branch;
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

        return redirect()->route('platform.branch-staff.show', $user);
    }

    /**
     * Remove the specified branch staff member.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user = $this->branchStaffService->resolveBranchStaff($user);
        abort_unless($user->branch_id !== null && $user->branch !== null, 404);

        $branch = $user->branch;

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

        return redirect()->route('platform.branch-staff.index');
    }
}
