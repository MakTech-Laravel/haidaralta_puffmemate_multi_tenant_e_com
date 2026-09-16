<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\InventoryTransactionDataTable;
use App\Enums\InventoryTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\ImportStockRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Services\Inventory\InventoryService;
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

    public function transactions(Request $request): Response
    {
        $this->mergeSelectionFilters($request);

        return Inertia::render('platform/stock/transactions', [
            'table' => InventoryTransactionDataTable::makeFor($request),
            'context' => StockPageContext::fromRequest($request),
        ]);
    }

    public function branchOptions(Request $request): JsonResponse
    {
        return response()->json(StockPageContext::paginatedBranchOptions($request));
    }

    public function productOptions(Request $request): JsonResponse
    {
        return response()->json(StockPageContext::paginatedProductOptions($request));
    }

    private function mergeSelectionFilters(Request $request): void
    {
        $filters = $request->input('filter', []);
        $filters = is_array($filters) ? $filters : [];

        if ($request->filled('product') && empty($filters['product_id'])) {
            $product = Product::query()->find($request->integer('product'))
                ?? Product::query()->where('slug', $request->string('product')->toString())->first();

            abort_unless($product !== null, 404);
            $filters['product_id'] = $product->id;
        } elseif ($request->filled('product')) {
            StockPageContext::fromRequest($request);
        }

        if ($request->filled('branch') && empty($filters['branch_id'])) {
            $branch = Branch::query()->find($request->integer('branch'))
                ?? Branch::query()->where('slug', $request->string('branch')->toString())->first();

            abort_unless($branch !== null, 404);
            $filters['branch_id'] = $branch->id;
        } elseif ($request->filled('branch')) {
            StockPageContext::fromRequest($request);
        }

        $request->merge(['filter' => $filters]);
    }
}
