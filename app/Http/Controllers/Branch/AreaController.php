<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchAreaDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchAreaRequest;
use App\Http\Requests\Branch\UpdateBranchAreaRequest;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Services\Branch\BranchAreaService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AreaController extends Controller
{
    public function __construct(private BranchAreaService $areas) {}

    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/areas/index', [
            'branch' => $branch->toPortalContext(),
            'table' => BranchAreaDataTable::forBranch($request, $branch),
        ]);
    }

    public function create(): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/areas/create', [
            'branch' => $branch->toPortalContext(),
        ]);
    }

    public function store(StoreBranchAreaRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $area = $this->areas->create($branch, $request->validated());

        Toast::success('Area created.');

        return redirect()->route('branch.areas.show', [$branch, $area]);
    }

    public function show(string $branchSlug, BranchArea $area): Response
    {
        $branch = $this->currentBranch();
        $this->ensureBelongsToBranch($branch, $area);

        return Inertia::render('branch/areas/show', [
            'branch' => $branch->toPortalContext(),
            'area' => $this->areas->detail($area),
        ]);
    }

    public function edit(string $branchSlug, BranchArea $area): Response
    {
        $branch = $this->currentBranch();
        $this->ensureBelongsToBranch($branch, $area);

        return Inertia::render('branch/areas/edit', [
            'branch' => $branch->toPortalContext(),
            'area' => $this->areas->detail($area),
        ]);
    }

    public function update(
        UpdateBranchAreaRequest $request,
        string $branchSlug,
        BranchArea $area,
    ): RedirectResponse {
        $branch = $this->currentBranch();
        $this->ensureBelongsToBranch($branch, $area);

        $this->areas->update($area, $request->validated());

        Toast::success('Area updated.');

        return redirect()->route('branch.areas.show', [$branch, $area]);
    }

    public function destroy(string $branchSlug, BranchArea $area): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->ensureBelongsToBranch($branch, $area);

        $this->areas->delete($area);

        Toast::success('Area deleted.');

        return redirect()->route('branch.areas.index', $branch);
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }

    private function ensureBelongsToBranch(Branch $branch, BranchArea $area): void
    {
        abort_unless($area->branches()->whereKey($branch->id)->exists(), 404);
    }
}
