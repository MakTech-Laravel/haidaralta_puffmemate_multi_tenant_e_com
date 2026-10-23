<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreBranchAreaRequest;
use App\Http\Requests\Platform\UpdateBranchAreaRequest;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Services\Branch\BranchAreaService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchAreaController extends Controller
{
    public function __construct(private BranchAreaService $areas) {}

    public function index(Request $request, Branch $branch): RedirectResponse
    {
        return redirect()->route('platform.branch-areas.index', array_replace_recursive(
            $request->query(),
            ['filter' => ['branch_id' => $branch->id]],
        ));
    }

    public function create(Branch $branch): Response
    {
        return Inertia::render('platform/branches/areas/create', [
            'branch' => $branch->only(['id', 'name', 'slug']),
        ]);
    }

    public function store(StoreBranchAreaRequest $request, Branch $branch): RedirectResponse
    {
        $area = $this->areas->create($branch, $request->validated());

        Toast::success('Area created.');

        return redirect()->route('platform.branch-areas.show', $area);
    }

    public function show(Branch $branch, BranchArea $area): RedirectResponse
    {
        $this->ensureBelongsToBranch($branch, $area);

        return redirect()->route('platform.branch-areas.show', $area);
    }

    public function edit(Branch $branch, BranchArea $area): RedirectResponse
    {
        $this->ensureBelongsToBranch($branch, $area);

        return redirect()->route('platform.branch-areas.edit', $area);
    }

    public function update(
        UpdateBranchAreaRequest $request,
        Branch $branch,
        BranchArea $area,
    ): RedirectResponse {
        $this->ensureBelongsToBranch($branch, $area);

        $this->areas->update($area, $request->validated());

        Toast::success('Area updated.');

        return redirect()->route('platform.branch-areas.show', $area);
    }

    public function destroy(Branch $branch, BranchArea $area): RedirectResponse
    {
        $this->ensureBelongsToBranch($branch, $area);

        $this->areas->delete($area);

        Toast::success('Area deleted.');

        return redirect()->route('platform.branch-areas.index', [
            'filter' => ['branch_id' => $branch->id],
        ]);
    }

    private function ensureBelongsToBranch(Branch $branch, BranchArea $area): void
    {
        abort_unless($area->branches()->whereKey($branch->id)->exists(), 404);
    }
}
