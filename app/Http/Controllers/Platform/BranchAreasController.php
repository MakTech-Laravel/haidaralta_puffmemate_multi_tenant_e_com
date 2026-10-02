<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\BranchAreaDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlatformBranchAreasRequest;
use App\Http\Requests\Platform\TransferBranchAreaRequest;
use App\Http\Requests\Platform\UpdateBranchAreaRequest;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\User;
use App\Services\Branch\BranchAreaService;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchAreasController extends Controller
{
    public function __construct(private BranchAreaService $areas) {}

    /**
     * Display delivery areas across all branches.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('platform/branch-areas/index', [
            'table' => BranchAreaDataTable::acrossBranches($request),
            'canManage' => $request->user('platform')?->can('edit-branches') ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/branch-areas/create');
    }

    public function store(StorePlatformBranchAreasRequest $request): RedirectResponse
    {
        $branch = $request->branch();
        $payload = $request->safe()->except(['branch_id']);
        $area = $this->areas->create($branch, $payload);

        Toast::success('Area created.');

        return redirect()->route('platform.branch-areas.show', $area);
    }

    public function show(BranchArea $area): Response
    {
        $area->load('branch:id,name,slug');

        return Inertia::render('platform/branch-areas/show', [
            'branch' => $area->branch?->only(['id', 'name', 'slug']),
            'area' => $this->areas->detail($area),
            'canManage' => request()->user('platform')?->can('edit-branches') ?? false,
        ]);
    }

    public function edit(BranchArea $area): Response
    {
        $area->load('branch:id,name,slug');

        return Inertia::render('platform/branch-areas/edit', [
            'branch' => $area->branch?->only(['id', 'name', 'slug']),
            'area' => $this->areas->detail($area),
        ]);
    }

    public function update(UpdateBranchAreaRequest $request, BranchArea $area): RedirectResponse
    {
        $this->areas->update($area, $request->validated());

        Toast::success('Area updated.');

        return redirect()->route('platform.branch-areas.show', $area);
    }

    public function destroy(BranchArea $area): RedirectResponse
    {
        $branchId = $area->branch_id;

        $this->areas->delete($area);

        Toast::success('Area deleted.');

        return redirect()->route('platform.branch-areas.index', [
            'filter' => ['branch_id' => $branchId],
        ]);
    }

    public function transfer(TransferBranchAreaRequest $request, BranchArea $area): RedirectResponse
    {
        $to = Branch::query()->findOrFail($request->integer('to_branch_id'));
        /** @var User $actor */
        $actor = $request->user('platform');

        $this->areas->transfer(
            $area,
            $to,
            $request->date('effective_date') ?? now(),
            (string) $request->string('reason'),
            $actor,
        );

        Toast::success('Area branch updated.');

        return redirect()->route('platform.branch-areas.show', $area);
    }

    /**
     * Paginated branch options for the areas list filter and create form.
     */
    public function branchOptions(Request $request): JsonResponse
    {
        return response()->json(WorkforcePayload::paginatedBranchOptions($request));
    }
}
