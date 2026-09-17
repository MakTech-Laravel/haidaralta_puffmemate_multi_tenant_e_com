<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\BranchProductListingDataTable;
use App\DataTables\ProductBranchInventoryDataTable;
use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\AdjustBranchInventoryRequest;
use App\Http\Requests\Branch\UpsertBranchProductPriceRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Inventory\BranchPricingService;
use App\Services\Inventory\InventoryService;
use App\Support\BranchProductListingPayload;
use App\Support\ProductBranchInventoryPayload;
use App\Support\ProductManageRedirect;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BranchInventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventory,
        private BranchPricingService $pricing,
    ) {}

    public function index(Request $request, Branch $branch): Response
    {
        return Inertia::render('platform/branches/products/index', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'summary' => BranchProductListingPayload::summary($branch),
            'table' => BranchProductListingDataTable::makeForBranch($request, $branch, includeImage: true),
        ]);
    }

    public function forProduct(Request $request, Product $product): Response
    {
        return Inertia::render('platform/catalog/products/branches', [
            'product' => ProductBranchInventoryPayload::product($product),
            'summary' => ProductBranchInventoryPayload::summary($product),
            'table' => ProductBranchInventoryDataTable::makeForProduct($request, $product),
        ]);
    }

    public function show(Branch $branch, Product $product): RedirectResponse
    {
        return redirect()->to(ProductManageRedirect::platformUrl($branch, $product));
    }

    public function adjustInventory(
        AdjustBranchInventoryRequest $request,
        Branch $branch,
        Product $product,
    ): RedirectResponse {
        $variant = $this->resolveVariant($product, $request->validated('variant_id'));
        $inventory = $this->inventory->firstOrCreate($branch, $product, $variant);

        try {
            $this->inventory->adjust(
                $inventory,
                InventoryTransactionType::from($request->validated('type')),
                (int) $request->validated('quantity'),
                $request->user('platform'),
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
        Branch $branch,
        Product $product,
    ): RedirectResponse {
        $variant = $this->resolveVariant($product, $request->validated('variant_id'));

        $this->pricing->upsert($branch, $product, $request->validated(), $variant);

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
