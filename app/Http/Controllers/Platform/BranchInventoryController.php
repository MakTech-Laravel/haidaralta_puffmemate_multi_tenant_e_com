<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\BranchProductListingDataTable;
use App\Enums\InventoryTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\AdjustBranchInventoryRequest;
use App\Http\Requests\Branch\ToggleBranchProductRequest;
use App\Http\Requests\Branch\UpsertBranchProductPriceRequest;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Inventory\BranchPricingService;
use App\Services\Inventory\BranchProductAvailabilityService;
use App\Services\Inventory\InventoryService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BranchInventoryController extends Controller
{
    public function __construct(
        private BranchProductAvailabilityService $availability,
        private InventoryService $inventory,
        private BranchPricingService $pricing,
    ) {}

    public function index(Request $request, Branch $branch): Response
    {
        return Inertia::render('platform/branches/products/index', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'table' => BranchProductListingDataTable::make($request, $branch),
        ]);
    }

    public function show(Branch $branch, Product $product): Response
    {
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

        return Inertia::render('platform/branches/products/show', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'status' => (bool) $product->status,
                'image_url' => $product->imageUrl(),
                'color_code' => $product->color_code,
                'category_ids' => $product->categories->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                'categories' => $product->categories
                    ->map(fn ($category): array => [
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

    public function toggle(ToggleBranchProductRequest $request, Branch $branch, Product $product): RedirectResponse
    {
        $enabled = $request->boolean('status');
        $this->availability->setEnabled($branch, $product, $enabled);

        Toast::success($enabled ? 'Product enabled for branch.' : 'Product disabled for branch.');

        return redirect()->back();
    }

    public function adjustInventory(
        AdjustBranchInventoryRequest $request,
        Branch $branch,
        Product $product,
    ): RedirectResponse {
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
                $request->user('platform'),
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
        Branch $branch,
        Product $product,
    ): RedirectResponse {
        $variant = $this->resolveVariant($product, $request->validated('variant_id'));
        $data = $request->validated();
        $data['status'] = $request->boolean('status', true);

        $this->pricing->upsert($branch, $product, $data, $variant);

        Toast::success('Price updated.');

        return redirect()->back();
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
}
