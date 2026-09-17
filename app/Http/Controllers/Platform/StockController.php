<?php

namespace App\Http\Controllers\Platform;

use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\ImportStockRequest;
use App\Services\Inventory\InventoryService;
use App\Support\BranchProductShowPayload;
use App\Support\CatalogReturnTo;
use App\Support\StockPageContext;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function __construct(
        private InventoryService $inventory,
    ) {}

    public function manage(Request $request): Response
    {
        $context = StockPageContext::fromRequest($request);
        $branch = StockPageContext::optionalBranch($request);
        $product = StockPageContext::optionalProduct($request);

        if ($branch === null || $product === null) {
            return Inertia::render('platform/stock/manage', [
                'context' => $context,
                'product' => null,
                'adjustmentReasons' => InventoryAdjustmentReason::options(),
                'transactionTypes' => BranchProductShowPayload::transactionTypes(),
                'discountTypes' => BranchProductShowPayload::discountTypes(),
                'createProductUrl' => route('platform.products.create', [
                    'return_to' => CatalogReturnTo::currentUrl($request),
                ]),
                'inventoryUrl' => null,
                'priceUrl' => null,
                'transactionsUrl' => null,
                'allBranchesUrl' => null,
                'branchProductsUrl' => null,
            ]);
        }

        return Inertia::render('platform/stock/manage', [
            'context' => $context,
            'product' => BranchProductShowPayload::product($branch, $product),
            'adjustmentReasons' => InventoryAdjustmentReason::options(),
            'transactionTypes' => BranchProductShowPayload::transactionTypes(),
            'discountTypes' => BranchProductShowPayload::discountTypes(),
            'createProductUrl' => route('platform.products.create', [
                'return_to' => CatalogReturnTo::currentUrl($request),
            ]),
            'inventoryUrl' => route('platform.branches.products.inventory', [$branch, $product]),
            'priceUrl' => route('platform.branches.products.prices', [$branch, $product]),
            'transactionsUrl' => route('platform.reports.stock-transactions', [
                'branch' => $branch->id,
                'product' => $product->id,
            ]),
            'allBranchesUrl' => route('platform.products.branches', $product),
            'branchProductsUrl' => route('platform.branches.products.index', $branch),
        ]);
    }

    public function import(Request $request): Response
    {
        return Inertia::render('platform/stock/import', [
            'context' => StockPageContext::fromRequest($request),
            'createProductUrl' => route('platform.products.create', [
                'return_to' => CatalogReturnTo::currentUrl($request),
            ]),
        ]);
    }

    public function storeImport(ImportStockRequest $request): RedirectResponse
    {
        $branches = StockPageContext::requireBranches($request->validated('branch_ids'));
        $products = StockPageContext::requireProducts($request->validated('product_ids'));
        $quantity = (int) $request->validated('quantity');

        DB::transaction(function () use ($branches, $products, $quantity, $request): void {
            foreach ($branches as $branch) {
                foreach ($products as $product) {
                    $inventory = $this->inventory->firstOrCreate($branch, $product);
                    $this->inventory->adjust(
                        $inventory,
                        InventoryTransactionType::StockIn,
                        $quantity,
                        $request->user('platform'),
                    );
                }
            }
        });

        Toast::success('Stock imported.');

        return redirect()->back();
    }

    public function branchOptions(Request $request): JsonResponse
    {
        return response()->json(StockPageContext::paginatedBranchOptions($request));
    }

    public function productOptions(Request $request): JsonResponse
    {
        return response()->json(StockPageContext::paginatedProductOptions($request));
    }
}
