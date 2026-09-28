<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchPackDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StorePackRequest;
use App\Http\Requests\Branch\UpdatePackRequest;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Pack;
use App\Models\PackItem;
use App\Models\Product;
use App\Services\Catalog\PackService;
use App\Support\BranchProductPriceDisplay;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
            'availableProducts' => $this->availableProducts($branch),
        ]);
    }

    public function store(StorePackRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $data = $request->safe()->except(['image', 'gallery']);
        $data['status'] = $request->boolean('status', true);
        $data['items'] = $this->normalizedItems($request->validated('items'));

        $pack = $this->packs->create(
            $branch,
            $data,
            $request->file('image'),
            $this->uploadedGallery($request),
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
            'pack' => $this->mapPack($branch, $pack, detailed: true),
        ]);
    }

    public function edit(string $branchSlug, Pack $pack): Response
    {
        $branch = $this->currentBranch();
        $pack->load(['items.product', 'images']);

        return Inertia::render('branch/catalog/packs-saves/edit', [
            'branch' => $branch->toPortalContext(),
            'pack' => $this->mapPack($branch, $pack, detailed: true),
            'availableProducts' => $this->availableProducts($branch),
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
            $this->uploadedGallery($request),
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

    /**
     * Catalog-active SKUs that already have a stock row at this branch.
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     sku: string|null,
     *     image_url: string|null,
     *     selling_price: string|null,
     *     effective_price: string|null,
     *     quantity: int
     * }>
     */
    private function availableProducts(Branch $branch): array
    {
        $inventories = Inventory::query()
            ->where('branch_id', $branch->id)
            ->whereNull('variant_id')
            ->orderBy('product_id')
            ->pluck('quantity', 'product_id');

        if ($inventories->isEmpty()) {
            return [];
        }

        $products = Product::query()
            ->whereIn('id', $inventories->keys())
            ->where('status', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $prices = collect();

        if (BranchProductPriceDisplay::usesBranchPricing() && $products->isNotEmpty()) {
            $prices = BranchProductPrice::query()
                ->where('branch_id', $branch->id)
                ->whereIn('product_id', $products->modelKeys())
                ->whereNull('variant_id')
                ->where('status', true)
                ->get()
                ->keyBy('product_id');
        }

        return $products
            ->map(function (Product $product) use ($prices, $inventories): array {
                /** @var BranchProductPrice|null $price */
                $price = $prices->get($product->id);
                $display = BranchProductPriceDisplay::listDisplayForProductContext(
                    $price,
                    $product,
                );

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'image_url' => $product->imageUrl(),
                    'selling_price' => $display['selling_price'],
                    'effective_price' => $display['effective_price'],
                    'quantity' => (int) $inventories->get($product->id, 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPack(Branch $branch, Pack $pack, bool $detailed = false): array
    {
        $payload = $pack->toPortal($detailed);

        if (! $detailed) {
            return $payload;
        }

        $productIds = $pack->items->pluck('product_id');
        $remaining = Inventory::query()
            ->where('branch_id', $branch->id)
            ->whereIn('product_id', $productIds)
            ->whereNull('variant_id')
            ->pluck('quantity', 'product_id');

        $prices = collect();

        if (BranchProductPriceDisplay::usesBranchPricing() && $productIds->isNotEmpty()) {
            $prices = BranchProductPrice::query()
                ->where('branch_id', $branch->id)
                ->whereIn('product_id', $productIds)
                ->whereNull('variant_id')
                ->where('status', true)
                ->get()
                ->keyBy('product_id');
        }

        $payload['items'] = $pack->items
            ->map(function (PackItem $item) use ($prices, $remaining): array {
                $product = $item->product;
                $display = BranchProductPriceDisplay::listDisplayForProductContext(
                    $prices->get($item->product_id),
                    $product,
                );

                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'product' => [
                        'id' => $product?->id,
                        'name' => $product?->name ?? 'Unknown product',
                        'sku' => $product?->sku,
                        'image_url' => $product?->imageUrl(),
                        'selling_price' => $display['selling_price'],
                        'effective_price' => $display['effective_price'],
                        'quantity' => (int) $remaining->get($item->product_id, 0),
                    ],
                ];
            })
            ->values()
            ->all();

        return $payload;
    }

    /**
     * @param  list<array{product_id: mixed, quantity: mixed}>  $items
     * @return list<array{product_id: int, quantity: int}>
     */
    private function normalizedItems(array $items): array
    {
        return collect($items)
            ->map(fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedGallery(Request $request): array
    {
        $files = $request->file('gallery', []);

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return array_values(array_filter(
            is_array($files) ? $files : [],
            fn ($file): bool => $file instanceof UploadedFile,
        ));
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
