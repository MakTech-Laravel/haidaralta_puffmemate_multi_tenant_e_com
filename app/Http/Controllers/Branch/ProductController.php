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
use App\Models\BranchProductPrice;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductSuggestionService;
use App\Services\Inventory\BranchPricingService;
use App\Services\Inventory\BranchProductAvailabilityService;
use App\Services\Inventory\InventoryService;
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
            'table' => BranchProductListingDataTable::make($request, $branch, includeImage: true),
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
        $product->load(['categories:id,name,slug', 'variants']);

        $inventory = Inventory::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->first();

        $price = BranchProductPrice::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->where('status', true)
            ->first();

        $transactions = $inventory
            ? $inventory->transactions()
                ->whereNull('variant_id')
                ->latest('created_at')
                ->limit(25)
                ->get()
                ->map(fn ($txn): array => [
                    'id' => $txn->id,
                    'type' => $txn->type->value,
                    'quantity' => $txn->quantity,
                    'before_quantity' => $txn->before_quantity,
                    'after_quantity' => $txn->after_quantity,
                    'created_at' => $txn->created_at?->toIso8601String(),
                ])
                ->all()
            : [];

        return Inertia::render('branch/products/show', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'description' => $product->description,
                'status' => (bool) $product->status,
                'image_url' => $product->imageUrl(),
                'color_code' => $product->color_code,
                'category_ids' => $product->categories->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                'categories' => $product->categories
                    ->map(fn (Category $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ])
                    ->values()
                    ->all(),
                'category_name' => $product->categoryNamesLabel(),
                'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'status' => (bool) $variant->status,
                ])->values()->all(),
                'enabled' => $this->availability->isEnabled($branch, $product),
                'inventory' => $inventory ? [
                    'quantity' => $inventory->quantity,
                    'reserved_quantity' => $inventory->reserved_quantity,
                    'available_quantity' => $inventory->available_quantity,
                    'minimum_stock' => $inventory->minimum_stock,
                    'is_low_stock' => $inventory->isLowStock(),
                ] : null,
                'price' => $price ? [
                    'selling_price' => $price->selling_price,
                    'discount_price' => $price->discount_price,
                    'start_date' => $price->start_date?->toDateString(),
                    'end_date' => $price->end_date?->toDateString(),
                    'status' => (bool) $price->status,
                ] : null,
                'transactions' => $transactions,
            ],
            'transactionTypes' => collect(InventoryTransactionType::cases())
                ->map(fn (InventoryTransactionType $type): array => [
                    'value' => $type->value,
                    'label' => str_replace('_', ' ', ucfirst($type->value)),
                ])
                ->values()
                ->all(),
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
