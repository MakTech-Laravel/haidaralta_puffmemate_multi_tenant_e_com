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
use App\Support\WorkforcePayload;
use Illuminate\Http\JsonResponse;
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
        return Inertia::render('platform/branch-staff/create');
    }

    /**
     * Paginated branch options for the staff create combobox.
     */
    public function branchOptions(Request $request): JsonResponse
    {
        return response()->json(WorkforcePayload::paginatedBranchOptions($request));
    }

    /**
     * Role options for a selected branch on the staff form.
     */
    public function roleOptions(Request $request): JsonResponse
    {
        $branch = Branch::query()->findOrFail($request->integer('branch_id'));

        return response()->json([
            'data' => $this->branchStaffService->roleOptionsForBranch($branch),
        ]);
    }

    /**
     * Store a newly created branch staff member.
     */
    public function store(StorePlatformBranchStaffRequest $request): RedirectResponse
    {
        $user = $this->branchStaffService->create(
            $request->branch(),
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

        abort_unless($user->branch_id !== null && $user->branch !== null, 404);

        $staffMember = $this->branchStaffService->mapStaffMember($user, $user->branch, includeAccess: true);
        $staffMember['transfers'] = $user->transferHistories()
            ->with(['fromBranch:id,name,slug', 'toBranch:id,name,slug', 'transferredBy:id,name'])
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($history) => WorkforcePayload::transfer($history))
            ->values()
            ->all();

        return Inertia::render('platform/branch-staff/show', [
            'staffMember' => $staffMember,
            'branches' => Branch::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Branch $branch): array => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'slug' => $branch->slug,
                ])
                ->values()
                ->all(),
            'rolesByBranch' => $this->branchStaffService->rolesByBranch(),
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
            'roles' => $this->branchStaffService->roleOptionsForBranch($branch),
            'lockedSuperAdmin' => $this->branchStaffService->isLastSuperAdminInBranch($user, $branch),
            'staffMember' => $this->branchStaffService->mapStaffMember($user, $branch),
            'selectedBranch' => [
                'value' => (string) $branch->id,
                'label' => $branch->name,
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

        $this->branchStaffService->update(
            $user,
            $branch,
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
