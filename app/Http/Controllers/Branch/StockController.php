<?php

namespace App\Http\Controllers\Branch;

use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\ImportStockRequest;
use App\Models\Branch;
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
        $branch = $this->currentBranch();
        $context = StockPageContext::fromRequest($request, $branch);
        $product = StockPageContext::optionalProduct($request);

        if ($product === null) {
            return Inertia::render('branch/stock/manage', [
                'context' => $context,
                'product' => null,
                'adjustmentReasons' => InventoryAdjustmentReason::options(),
                'transactionTypes' => BranchProductShowPayload::transactionTypes(),
                'discountTypes' => BranchProductShowPayload::discountTypes(),
                'createProductUrl' => route('branch.products.create', [
                    'branch' => $branch,
                    'return_to' => CatalogReturnTo::currentUrl($request),
                ]),
                'inventoryUrl' => null,
                'priceUrl' => null,
                'transactionsUrl' => null,
                'allBranchesUrl' => null,
                'branchProductsUrl' => null,
            ]);
        }

        return Inertia::render('branch/stock/manage', [
            'context' => $context,
            'product' => BranchProductShowPayload::product($branch, $product),
            'adjustmentReasons' => InventoryAdjustmentReason::options(),
            'transactionTypes' => BranchProductShowPayload::transactionTypes(),
            'discountTypes' => BranchProductShowPayload::discountTypes(),
            'createProductUrl' => route('branch.products.create', [
                'branch' => $branch,
                'return_to' => CatalogReturnTo::currentUrl($request),
            ]),
            'inventoryUrl' => route('branch.products.inventory', [$branch, $product]),
            'priceUrl' => route('branch.products.prices', [$branch, $product]),
            'transactionsUrl' => route('branch.reports.stock-transactions', [
                'branch' => $branch,
                'product' => $product->id,
            ]),
            'allBranchesUrl' => null,
            'branchProductsUrl' => route('branch.products.index', $branch),
        ]);
    }

    public function import(Request $request): Response
    {
        abort_unless((bool) config('catalog.show_bulk_import'), 404);

        $branch = $this->currentBranch();

        return Inertia::render('branch/stock/import', [
            'context' => StockPageContext::fromRequest($request, $branch),
            'createProductUrl' => route('branch.products.create', [
                'branch' => $branch,
                'return_to' => CatalogReturnTo::currentUrl($request),
            ]),
        ]);
    }

    public function storeImport(ImportStockRequest $request): RedirectResponse
    {
        abort_unless((bool) config('catalog.show_bulk_import'), 404);

        $branch = $this->currentBranch();
        $products = StockPageContext::requireProducts($request->validated('product_ids'));
        $quantity = (int) $request->validated('quantity');
        $unitCost = $request->validated('buying_price') ?? null;

        DB::transaction(function () use ($branch, $products, $quantity, $request, $unitCost): void {
            foreach ($products as $product) {
                $inventory = $this->inventory->firstOrCreate($branch, $product);
                $this->inventory->adjust(
                    $inventory,
                    InventoryTransactionType::StockIn,
                    $quantity,
                    $request->user('branch'),
                    unitCost: $unitCost,
                );
            }
        });

        Toast::success('Stock imported.');

        return redirect()->back();
    }

    public function productOptions(Request $request): JsonResponse
    {
        return response()->json(StockPageContext::paginatedProductOptions($request));
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
