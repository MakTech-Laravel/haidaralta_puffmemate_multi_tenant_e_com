<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\BranchDataTable;
use App\DataTables\BranchStaffDataTable;
use App\Exceptions\BranchAlreadyHasSuperAdminException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreBranchRequest;
use App\Http\Requests\Platform\StoreBranchSuperAdminRequest;
use App\Http\Requests\Platform\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Services\Branch\BranchService;
use App\Support\BranchMap;
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
        return Inertia::render('platform/branches/create', [
            'areaOptions' => $this->branches->areaOptionsForForm(),
        ]);
    }

    /**
     * Store a newly created branch with its first super-admin.
     */
    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $adminData = $validated['super_admin'];
        $areaIds = $validated['area_ids'] ?? [];
        unset($validated['super_admin'], $validated['area_ids']);

        $branch = $this->branches->createWithSuperAdmin(
            [
                ...$validated,
                'status' => $request->boolean('status', true),
            ],
            $adminData,
            $request->file('logo'),
        );

        $this->branches->syncAreasFromForm($branch, $areaIds, $request->user());

        Toast::success('Branch created with super-admin.');

        return redirect()->route('platform.branches.show', $branch);
    }

    /**
     * Recovery: show the form when a branch is missing its first super-admin.
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
     * Recovery: store the first branch super-admin when none exists.
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
    public function show(Request $request, Branch $branch): Response
    {
        $branch->loadCount(['users', 'areas']);

        $areas = $branch->areas()
            ->orderBy('branch_areas.name')
            ->get([
                'branch_areas.id',
                'branch_areas.name',
                'branch_areas.is_active',
                'branch_areas.city',
                'branch_areas.state',
            ])
            ->map(fn (BranchArea $area): array => [
                'id' => $area->id,
                'name' => $area->name,
                'is_active' => $area->is_active,
                'city' => $area->city,
                'state' => $area->state,
            ])
            ->values()
            ->all();

        return Inertia::render('platform/branches/show', [
            'branch' => [
                ...$this->mapBranchDetail($branch),
                'areas_count' => (int) ($branch->areas_count ?? count($areas)),
                'areas' => $areas,
                'map' => BranchMap::links($branch->latitude, $branch->longitude),
                'needs_super_admin' => ! $this->branches->hasSuperAdmin($branch),
            ],
            'table' => BranchStaffDataTable::forBranch($request, $branch),
        ]);
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Branch $branch): Response
    {
        return Inertia::render('platform/branches/edit', [
            'branch' => [
                ...$this->mapBranchDetail($branch),
                'area_ids' => $branch->areas()->pluck('branch_areas.id')->all(),
            ],
            'areaOptions' => $this->branches->areaOptionsForForm(),
        ]);
    }

    /**
     * Update the specified branch.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validated();
        $areaIds = $validated['area_ids'] ?? [];
        unset($validated['area_ids']);

        $this->branches->update(
            $branch,
            [
                ...$validated,
                'status' => $request->boolean('status', true),
            ],
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        $this->branches->syncAreasFromForm($branch, $areaIds, $request->user());

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
            'logo' => $branch->hasMedia('logo') ? 'logo' : null,
            'logo_url' => $this->branches->logoUrl($branch),
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
            'delivery_radius_km' => $branch->delivery_radius_km,
            'opening_time' => $branch->opening_time?->format('H:i'),
            'closing_time' => $branch->closing_time?->format('H:i'),
        ];
    }
}
