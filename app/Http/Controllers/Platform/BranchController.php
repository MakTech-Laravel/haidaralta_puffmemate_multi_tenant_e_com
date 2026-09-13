<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\BranchDataTable;
use App\Exceptions\BranchAlreadyHasSuperAdminException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreBranchRequest;
use App\Http\Requests\Platform\StoreBranchSuperAdminRequest;
use App\Http\Requests\Platform\UpdateBranchRequest;
use App\Models\Branch;
use App\Services\Branch\BranchService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function __construct(private BranchService $branches) {}

    /**
     * Display a listing of branches.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('platform/branches/index', [
            'table' => BranchDataTable::make($request),
        ]);
    }

    /**
     * Show the form for creating a branch.
     */
    public function create(): Response
    {
        return Inertia::render('platform/branches/create');
    }

    /**
     * Store a newly created branch, then continue to super-admin setup.
     */
    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $branch = $this->branches->create(
            [
                ...$validated,
                'status' => $request->boolean('status', true),
            ],
            $request->file('logo'),
        );

        Toast::success('Branch created. Add a super-admin to finish setup.');

        return redirect()->route('platform.branches.super-admin.create', $branch);
    }

    /**
     * Show the form for creating the branch super-admin.
     */
    public function createSuperAdmin(Branch $branch): Response|RedirectResponse
    {
        if ($this->branches->hasSuperAdmin($branch)) {
            Toast::warning('This branch already has a super-admin.');

            return redirect()->route('platform.branches.show', $branch);
        }

        return Inertia::render('platform/branches/create-super-admin', [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'code' => $branch->code,
            ],
        ]);
    }

    /**
     * Store the branch super-admin.
     */
    public function storeSuperAdmin(StoreBranchSuperAdminRequest $request, Branch $branch): RedirectResponse
    {
        try {
            $this->branches->createSuperAdmin($branch, $request->validated());
        } catch (BranchAlreadyHasSuperAdminException $exception) {
            Toast::warning($exception->getMessage());

            return redirect()->route('platform.branches.show', $branch);
        }

        Toast::success('Branch super-admin created.');

        return redirect()->route('platform.branches.show', $branch);
    }

    /**
     * Display the specified branch.
     */
    public function show(Branch $branch): Response
    {
        $branch->loadCount('users');

        $superAdmins = $this->branches->listSuperAdmins($branch);

        return Inertia::render('platform/branches/show', [
            'branch' => [
                ...$this->mapBranchDetail($branch),
                'super_admins' => $superAdmins,
                'needs_super_admin' => $superAdmins === [],
            ],
        ]);
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Branch $branch): Response
    {
        return Inertia::render('platform/branches/edit', [
            'branch' => $this->mapBranchDetail($branch),
        ]);
    }

    /**
     * Update the specified branch.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validated();

        $this->branches->update(
            $branch,
            [
                ...$validated,
                'status' => $request->boolean('status', true),
            ],
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        Toast::success('Branch updated.');

        return redirect()->route('platform.branches.show', $branch);
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->users()->exists()) {
            Toast::error('Cannot delete a branch that still has staff accounts.');

            return redirect()->back();
        }

        $this->branches->delete($branch);

        Toast::success('Branch deleted.');

        return redirect()->route('platform.branches.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBranchRow(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'slug' => $branch->slug,
            'code' => $branch->code,
            'logo' => $branch->logo,
            'logo_url' => $this->branches->logoUrl($branch->logo),
            'status' => $branch->status,
            'users_count' => (int) ($branch->users_count ?? $branch->users()->count()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBranchDetail(Branch $branch): array
    {
        return [
            ...$this->mapBranchRow($branch),
            'phone' => $branch->phone,
            'email' => $branch->email,
            'address' => $branch->address,
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'opening_time' => $branch->opening_time?->format('H:i'),
            'closing_time' => $branch->closing_time?->format('H:i'),
        ];
    }
}
