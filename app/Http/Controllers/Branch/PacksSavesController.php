<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchPackDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StorePackRequest;
use App\Http\Requests\Branch\UpdatePackRequest;
use App\Models\Branch;
use App\Models\Pack;
use App\Services\Catalog\PackService;
use App\Support\PackPortalPayload;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PacksSavesController extends Controller
{
    public function __construct(private PackService $packs) {}

    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/packs-saves/index', [
            'branch' => $branch->toPortalContext(),
            'table' => BranchPackDataTable::makeForBranch($request, $branch),
        ]);
    }

    public function create(): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/packs-saves/create', [
            'branch' => $branch->toPortalContext(),
            'availableProducts' => PackPortalPayload::availableProducts($branch),
        ]);
    }

    public function store(StorePackRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $data = $request->safe()->except(['image', 'gallery']);
        $data['status'] = $request->boolean('status', true);
        $data['items'] = $request->packItems();

        $pack = $this->packs->create(
            $branch,
            $data,
            $request->file('image'),
            $request->galleryFiles(),
            $request->user('branch'),
        );

        Toast::success('Pack created.');

        return redirect()->route('branch.packs-saves.show', [$branch, $pack]);
    }

    public function show(string $branchSlug, Pack $pack): Response
    {
        $branch = $this->currentBranch();
        $pack->load(['items.product', 'images']);

        return Inertia::render('branch/catalog/packs-saves/show', [
            'branch' => $branch->toPortalContext(),
            'pack' => PackPortalPayload::pack($branch, $pack, detailed: true),
        ]);
    }

    public function edit(string $branchSlug, Pack $pack): Response
    {
        $branch = $this->currentBranch();
        $pack->load(['items.product', 'images']);

        return Inertia::render('branch/catalog/packs-saves/edit', [
            'branch' => $branch->toPortalContext(),
            'pack' => PackPortalPayload::pack($branch, $pack, detailed: true),
            'availableProducts' => PackPortalPayload::availableProducts($branch),
        ]);
    }

    public function update(UpdatePackRequest $request, string $branchSlug, Pack $pack): RedirectResponse
    {
        $branch = $this->currentBranch();
        $data = $request->safe()->except(['image', 'gallery', 'remove_gallery_ids']);
        $data['status'] = $request->boolean('status', true);
        $data['remove_image'] = $request->boolean('remove_image');
        $data['remove_gallery_ids'] = collect($request->input('remove_gallery_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->all();

        $pack = $this->packs->update(
            $pack,
            $data,
            $request->file('image'),
            $request->galleryFiles(),
            $request->user('branch'),
        );

        Toast::success('Pack updated.');

        return redirect()->route('branch.packs-saves.show', [$branch, $pack]);
    }

    public function destroy(Request $request, string $branchSlug, Pack $pack): RedirectResponse
    {
        $branch = $this->currentBranch();

        $this->packs->delete($pack, $request->user('branch'));

        Toast::success('Pack deleted.');

        return redirect()->route('branch.packs-saves.index', $branch);
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
