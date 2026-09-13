<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchProductListingDataTable;
use App\Enums\InventoryTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\AdjustBranchInventoryRequest;
use App\Http\Requests\Branch\ReuseBranchProductRequest;
use App\Http\Requests\Branch\StoreBranchProductRequest;
use App\Http\Requests\Branch\ToggleBranchProductRequest;
use App\Http\Requests\Branch\UpsertBranchProductPriceRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductSuggestionService;
use App\Services\Inventory\BranchPricingService;
use App\Services\Inventory\BranchProductAvailabilityService;
use App\Services\Inventory\InventoryService;
use App\Support\BranchProductShowPayload;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $products,
        private ProductSuggestionService $suggestions,
        private BranchProductAvailabilityService $availability,
        private InventoryService $inventory,
        private BranchPricingService $pricing,
    ) {}

    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/products/index', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'table' => BranchProductListingDataTable::makeForBranch($request, $branch, includeImage: true),
        ]);
    }

    public function create(): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/products/create', [
            'branch' => $branch->only(['id', 'name', 'slug']),
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

        return response()->json([
            'suggestions' => $this->suggestions->suggest($query),
        ]);
    }

    public function store(StoreBranchProductRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $data = $request->safe()->except(['image', 'selling_price', 'discount_price', 'quantity', 'minimum_stock']);
        $data['status'] = $request->boolean('status', true);

        $product = $this->products->createFromBranch(
            $branch,
            $data,
            $request->file('image'),
            [],
            $request->user('branch'),
        );

        $this->applyOptionalStockAndPrice($branch, $product, $request->validated());

        Toast::success('Product created and enabled for this branch only.');

        return redirect()->route('branch.products.show', [$branch, $product]);
    }

    public function reuse(ReuseBranchProductRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $product = Product::query()->findOrFail($request->validated('product_id'));

        $this->availability->setEnabled($branch, $product, true);
        $this->applyOptionalStockAndPrice($branch, $product, $request->validated());

        Toast::success('Existing product enabled for this branch.');

        return redirect()->route('branch.products.show', [$branch, $product]);
    }

    public function show(string $branchSlug, Product $product): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/products/show', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'product' => BranchProductShowPayload::product(
                $branch,
                $product,
                $this->availability->isEnabled($branch, $product),
            ),
            'transactionTypes' => BranchProductShowPayload::transactionTypes(),
        ]);
    }

    public function toggle(ToggleBranchProductRequest $request, string $branchSlug, Product $product): RedirectResponse
    {
        $branch = $this->currentBranch();
        $enabled = $request->boolean('status');

        $this->availability->setEnabled($branch, $product, $enabled);

        Toast::success($enabled ? 'Product enabled for this branch.' : 'Product disabled for this branch.');

        return redirect()->back();
    }

    public function adjustInventory(
        AdjustBranchInventoryRequest $request,
        string $branchSlug,
        Product $product,
    ): RedirectResponse {
        $branch = $this->currentBranch();
        $variant = $this->resolveVariant($product, $request->validated('variant_id'));

        $inventory = $this->inventory->firstOrCreate($branch, $product, $variant);

        if ($request->filled('minimum_stock')) {
            $this->inventory->setMinimumStock($inventory, (int) $request->validated('minimum_stock'));
            $inventory->refresh();
        }

        try {
            $this->inventory->adjust(
                $inventory,
                InventoryTransactionType::from($request->validated('type')),
                (int) $request->validated('quantity'),
                $request->user('branch'),
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
        $data = $request->validated();
        $data['status'] = $request->boolean('status', true);

        $this->pricing->upsert($branch, $product, $data, $variant);

        Toast::success('Price updated.');

        return redirect()->back();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyOptionalStockAndPrice(Branch $branch, Product $product, array $data): void
    {
        if (array_key_exists('minimum_stock', $data) || array_key_exists('quantity', $data)) {
            $inventory = $this->inventory->firstOrCreate(
                $branch,
                $product,
                null,
                ['minimum_stock' => (int) ($data['minimum_stock'] ?? 0)],
            );

            if (array_key_exists('minimum_stock', $data) && $data['minimum_stock'] !== null) {
                $this->inventory->setMinimumStock($inventory, (int) $data['minimum_stock']);
            }

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
                'discount_price' => $data['discount_price'] ?? null,
                'status' => true,
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

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
