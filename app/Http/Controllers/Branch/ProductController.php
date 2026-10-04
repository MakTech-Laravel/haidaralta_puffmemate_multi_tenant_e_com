<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchProductListingDataTable;
use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryTransactionType;
use App\Enums\PriceChangeSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\AdjustBranchInventoryRequest;
use App\Http\Requests\Branch\ReuseBranchProductRequest;
use App\Http\Requests\Branch\StoreBranchProductRequest;
use App\Http\Requests\Branch\UpdateBranchProductRequest;
use App\Http\Requests\Branch\UpsertBranchProductPriceRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductSuggestionService;
use App\Services\Inventory\BranchPricingService;
use App\Services\Inventory\InventoryService;
use App\Support\CatalogMediaPayload;
use App\Support\CatalogReturnTo;
use App\Support\ProductManageRedirect;
use App\Support\ProductSpecifications;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $products,
        private ProductSuggestionService $suggestions,
        private InventoryService $inventory,
        private BranchPricingService $pricing,
    ) {}

    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/products/index', [
            'branch' => $branch->toPortalContext(),
            'table' => BranchProductListingDataTable::makeForBranch($request, $branch, includeImage: true),
        ]);
    }

    public function create(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/products/create', [
            'branch' => $branch->toPortalContext(),
            'returnTo' => CatalogReturnTo::fromRequest($request),
            'categoryOptions' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->all(),
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->toString();
        $excludeProductId = $request->integer('exclude') ?: null;

        return response()->json([
            'suggestions' => $this->suggestions->suggest(
                $query,
                excludeProductId: $excludeProductId,
            ),
        ]);
    }

    public function store(StoreBranchProductRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $except = ['image', 'gallery', 'buying_price', 'discount_price', 'quantity'];

        if ((bool) config('catalog.show_branch_pricing') || ! (bool) config('catalog.branch_can_manage_catalog_pricing')) {
            $except = [...$except, 'selling_price', 'regular_price'];
        }

        $data = $request->safe()->except($except);
        $data['status'] = $request->boolean('status', true);

        $product = $this->products->createFromBranch(
            $branch,
            $data,
            $request->file('image'),
            $this->uploadedGallery($request),
            $request->user('branch'),
        );

        $this->applyOptionalStockAndPrice($branch, $product, $request->validated(), $request->user('branch'));

        Toast::success('Product created.');

        return CatalogReturnTo::afterProductCreate(
            $request,
            $product,
            'branch.stock.manage',
            [
                'branch' => $branch,
                'product' => $product->id,
            ],
        );
    }

    public function reuse(ReuseBranchProductRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $product = Product::query()->findOrFail($request->validated('product_id'));

        $this->applyOptionalStockAndPrice($branch, $product, $request->validated(), $request->user('branch'));

        Toast::success('Existing product stock and price updated for this branch.');

        return redirect()->to(ProductManageRedirect::branchUrl($branch, $product));
    }

    public function show(string $branchSlug, Product $product): Response
    {
        $branch = $this->currentBranch();
        $product->load(['categories:id,name,slug', 'media', 'creator:id,name']);

        return Inertia::render('branch/catalog/products/show', [
            'branch' => $branch->toPortalContext(),
            'product' => $this->mapCatalogProduct($product),
        ]);
    }

    public function edit(string $branchSlug, Product $product): Response
    {
        $branch = $this->currentBranch();
        $product->load(['categories:id,name,slug', 'media']);

        return Inertia::render('branch/catalog/products/edit', [
            'branch' => $branch->toPortalContext(),
            'product' => [
                'id' => $product->id,
                'category_ids' => $product->categories->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'description' => $product->description,
                'brand' => $product->brand,
                'pack_label' => $product->pack_label,
                'pieces_per_pack' => $product->pieces_per_pack,
                'pack_unit' => $product->pack_unit,
                'tags' => $product->tags ?? [],
                'specifications' => $product->specifications ?? [],
                'image' => CatalogMediaPayload::fileName($product),
                'image_url' => $product->imageUrl(),
                'color_code' => $product->color_code,
                'selling_price' => $product->selling_price,
                'regular_price' => $product->regular_price,
                'status' => (bool) $product->status,
                'gallery' => CatalogMediaPayload::gallery($product),
            ],
            'categoryOptions' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->all(),
        ]);
    }

    public function update(UpdateBranchProductRequest $request, string $branchSlug, Product $product): RedirectResponse
    {
        $branch = $this->currentBranch();
        $except = ['image', 'gallery', 'remove_gallery_ids'];

        if ((bool) config('catalog.show_branch_pricing') || ! (bool) config('catalog.branch_can_manage_catalog_pricing')) {
            $except = [...$except, 'selling_price', 'regular_price'];
        }

        $data = $request->safe()->except($except);
        $data['status'] = $request->boolean('status', true);
        $data['remove_image'] = $request->boolean('remove_image');
        $data['remove_gallery_ids'] = collect($request->input('remove_gallery_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->all();

        $product = $this->products->update(
            $product,
            $data,
            $request->file('image'),
            $this->uploadedGallery($request),
        );

        Toast::success('Product updated.');

        return redirect()->route('branch.products.show', [$branch, $product]);
    }

    public function adjustInventory(
        AdjustBranchInventoryRequest $request,
        string $branchSlug,
        Product $product,
    ): RedirectResponse {
        $branch = $this->currentBranch();
        $variant = $this->resolveVariant($product, $request->validated('variant_id'));

        $inventory = $this->inventory->firstOrCreate($branch, $product, $variant);

        try {
            $this->inventory->adjust(
                $inventory,
                InventoryTransactionType::from($request->validated('type')),
                (int) $request->validated('quantity'),
                $request->user('branch'),
                reason: InventoryAdjustmentReason::fromNullable($request->validated('reason')),
                reasonNote: InventoryAdjustmentReason::noteFrom($request->validated('reason_note')),
                unitCost: $request->validated('buying_price'),
            );
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Inventory updated.');

        return redirect()->back();
    }

    public function upsertPrice(
        UpsertBranchProductPriceRequest $request,
        string $branchSlug,
        Product $product,
    ): RedirectResponse {
        $branch = $this->currentBranch();
        $variant = $this->resolveVariant($product, $request->validated('variant_id'));

        $this->pricing->upsert($branch, $product, $request->validated(), $variant, $request->user('branch'));

        Toast::success('Price updated.');

        return redirect()->back();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyOptionalStockAndPrice(Branch $branch, Product $product, array $data, ?User $actor = null): void
    {
        if (array_key_exists('quantity', $data)) {
            $inventory = $this->inventory->firstOrCreate($branch, $product);

            $qty = (int) ($data['quantity'] ?? 0);

            if ($qty !== 0) {
                $this->inventory->adjust(
                    $inventory->refresh(),
                    $qty > 0 ? InventoryTransactionType::StockIn : InventoryTransactionType::StockOut,
                    abs($qty),
                    $actor,
                    unitCost: $qty > 0 ? ($data['buying_price'] ?? null) : null,
                );
            }
        }

        if (! (bool) config('catalog.show_branch_pricing')) {
            return;
        }

        if (! empty($data['selling_price'])) {
            $this->pricing->upsert(
                $branch,
                $product,
                [
                    'selling_price' => $data['selling_price'],
                    'buying_price' => $data['buying_price'] ?? null,
                    'regular_price' => $data['regular_price'] ?? null,
                    'discount_type' => ! empty($data['discount_price']) ? 'fixed' : 'none',
                    'discount_price' => $data['discount_price'] ?? null,
                ],
                actor: $actor,
                source: PriceChangeSource::ProductCreate,
            );
        }
    }

    private function resolveVariant(Product $product, mixed $variantId): ?ProductVariant
    {
        if ($variantId === null || $variantId === '') {
            return null;
        }

        $variant = ProductVariant::query()->findOrFail((int) $variantId);
        abort_unless($variant->product_id === $product->id, 404);

        return $variant;
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

    /**
     * @return array<string, mixed>
     */
    private function mapCatalogProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'categories' => $product->categories
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->values()
                ->all(),
            'category_name' => $product->categoryNamesLabel(),
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'brand' => $product->brand,
            'pack_label' => $product->pack_label,
            'pieces_per_pack' => $product->pieces_per_pack,
            'pack_unit' => $product->pack_unit,
            'pack_summary' => $product->packSummary(),
            'tags' => $product->tags ?? [],
            'specifications' => ProductSpecifications::normalize($product->specifications),
            'image_url' => $product->imageUrl(),
            'color_code' => $product->color_code,
            'status' => (bool) $product->status,
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
            'created_by_name' => $product->creator?->name,
            'gallery' => CatalogMediaPayload::gallery($product),
        ];
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
