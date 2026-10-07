<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\PlatformPackDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePackRequest;
use App\Http\Requests\Platform\UpdatePackRequest;
use App\Models\Branch;
use App\Models\Pack;
use App\Services\Catalog\PackService;
use App\Support\PackPortalPayload;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PacksSavesController extends Controller
{
    public function __construct(private PackService $packs) {}

    public function index(Request $request): Response
    {
        return Inertia::render('platform/catalog/packs-saves/index', [
            'table' => PlatformPackDataTable::make($request),
        ]);
    }

    public function branchOptions(Request $request): JsonResponse
    {
        return response()->json(WorkforcePayload::paginatedBranchOptions($request));
    }

    public function create(Request $request): Response
    {
        $branch = $request->filled('branch')
            ? Branch::query()->where('status', true)->find($request->integer('branch'))
            : null;

        return Inertia::render('platform/catalog/packs-saves/create', [
            'selectedBranch' => $branch?->toPortalContext(),
            'availableProducts' => $branch === null ? [] : PackPortalPayload::availableProducts($branch),
        ]);
    }

    public function store(StorePackRequest $request): RedirectResponse
    {
        $branch = Branch::query()->findOrFail($request->integer('branch_id'));
        $data = $request->safe()->except(['image', 'gallery', 'branch_id']);
        $data['status'] = $request->boolean('status', true);
        $data['is_highlighted'] = $request->boolean('is_highlighted');
        $data['items'] = $request->packItems();

        $pack = $this->packs->create(
            $branch,
            $data,
            $request->file('image'),
            $request->galleryFiles(),
            $request->user('platform'),
        );

        Toast::success('Pack created.');

        return redirect()->route('platform.packs-saves.show', $pack->id);
    }

    public function show(Pack $pack): Response
    {
        $pack->load(['items.product', 'media', 'branch']);

        return Inertia::render('platform/catalog/packs-saves/show', [
            'branch' => $pack->branch->toPortalContext(),
            'pack' => PackPortalPayload::pack($pack->branch, $pack, detailed: true),
        ]);
    }

    public function edit(Pack $pack): Response
    {
        $pack->load(['items.product', 'media', 'branch']);

        return Inertia::render('platform/catalog/packs-saves/edit', [
            'branch' => $pack->branch->toPortalContext(),
            'pack' => PackPortalPayload::pack($pack->branch, $pack, detailed: true),
            'availableProducts' => PackPortalPayload::availableProducts($pack->branch),
        ]);
    }

    public function update(UpdatePackRequest $request, Pack $pack): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'gallery', 'remove_gallery_ids']);
        $data['status'] = $request->boolean('status', true);
        $data['is_highlighted'] = $request->boolean('is_highlighted');
        $data['remove_image'] = $request->boolean('remove_image');
        $data['remove_gallery_ids'] = collect($request->input('remove_gallery_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->all();

        $pack = $this->packs->update(
            $pack,
            $data,
            $request->file('image'),
            $request->galleryFiles(),
            $request->user('platform'),
        );

        Toast::success('Pack updated.');

        return redirect()->route('platform.packs-saves.show', $pack->id);
    }

    public function highlight(Request $request, Pack $pack): RedirectResponse
    {
        $highlighted = $request->boolean('highlighted');

        $this->packs->setHighlighted($pack, $highlighted);

        Toast::success($highlighted ? 'Pack highlighted on the storefront.' : 'Highlight removed.');

        return back();
    }

    public function destroy(Request $request, Pack $pack): RedirectResponse
    {
        $this->packs->delete($pack, $request->user('platform'));

        Toast::success('Pack deleted.');

        return redirect()->route('platform.packs-saves.index');
    }
}
