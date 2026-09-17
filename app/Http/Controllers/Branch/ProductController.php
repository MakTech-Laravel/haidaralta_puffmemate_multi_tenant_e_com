<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchProductListingDataTable;
use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\AdjustBranchInventoryRequest;
use App\Http\Requests\Branch\ReuseBranchProductRequest;
use App\Http\Requests\Branch\StoreBranchProductRequest;
use App\Http\Requests\Branch\UpdateBranchProductRequest;
use App\Http\Requests\Branch\UpsertBranchProductPriceRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductSuggestionService;
use App\Services\Inventory\BranchPricingService;
use App\Services\Inventory\InventoryService;
use App\Support\CatalogReturnTo;
use App\Support\ProductManageRedirect;
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
            'branch' => $branch->only(['id', 'name', 'slug']),
            'table' => BranchProductListingDataTable::makeForBranch($request, $branch, includeImage: true),
        ]);
    }

    public function create(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/products/create', [
            'branch' => $branch->only(['id', 'name', 'slug']),
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
        $data = $request->safe()->except(['image', 'gallery', 'selling_price', 'discount_price', 'quantity']);
        $data['status'] = $request->boolean('status', true);

        $product = $this->products->createFromBranch(
            $branch,
            $data,
            $request->file('image'),
            $this->uploadedGallery($request),
            $request->user('branch'),
        );

        $this->applyOptionalStockAndPrice($branch, $product, $request->validated());

        Toast::success('Product created.');

        return CatalogReturnTo::afterProductCreate(
            $request,
            $product,
            'branch.stock.manage',
            [
                'branch' => $branch,
                'product' => $product->id,
                'locked' => 'both',
            ],
        );
    }

    public function reuse(ReuseBranchProductRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $product = Product::query()->findOrFail($request->validated('product_id'));

        $this->applyOptionalStockAndPrice($branch, $product, $request->validated());

        Toast::success('Existing product stock and price updated for this branch.');

        return redirect()->to(ProductManageRedirect::branchUrl($branch, $product));
    }

    public function show(string $branchSlug, Product $product): RedirectResponse
    {
        return redirect()->to(ProductManageRedirect::branchUrl($this->currentBranch(), $product));
    }

    public function edit(string $branchSlug, Product $product): Response
    {
        $branch = $this->currentBranch();
        $product->load(['categories:id,name,slug', 'images']);

        return Inertia::render('branch/catalog/products/edit', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'product' => [
                'id' => $product->id,
                'category_ids' => $product->categories->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'description' => $product->description,
                'brand' => $product->brand,
                'tags' => $product->tags ?? [],
                'specifications' => $product->specifications ?? [],
                'image' => $product->image,
                'image_url' => $product->imageUrl(),
                'color_code' => $product->color_code,
                'status' => (bool) $product->status,
                'gallery' => $product->images
                    ->map(fn (ProductImage $image): array => [
                        'id' => $image->id,
                        'path' => $image->path,
                        'url' => $image->url(),
                        'sort_order' => $image->sort_order,
                        'mime_type' => 'image/jpeg',
                    ])
                    ->values()
                    ->all(),
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
        $data = $request->safe()->except(['image', 'gallery', 'remove_gallery_ids']);
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

        return redirect()->to(ProductManageRedirect::branchUrl($branch, $product));
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

        $this->pricing->upsert($branch, $product, $request->validated(), $variant);

        Toast::success('Price updated.');

        return redirect()->back();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyOptionalStockAndPrice(Branch $branch, Product $product, array $data): void
    {
        if (array_key_exists('quantity', $data)) {
            $inventory = $this->inventory->firstOrCreate($branch, $product);

            $qty = (int) ($data['quantity'] ?? 0);

            if ($qty !== 0) {
                $this->inventory->adjust(
                    $inventory->refresh(),
                    $qty > 0 ? InventoryTransactionType::StockIn : InventoryTransactionType::StockOut,
                    abs($qty),
                );
            }
        }

        if (! empty($data['selling_price'])) {
            $this->pricing->upsert($branch, $product, [
                'selling_price' => $data['selling_price'],
                'discount_type' => ! empty($data['discount_price']) ? 'fixed' : 'none',
                'discount_price' => $data['discount_price'] ?? null,
            ]);
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

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
