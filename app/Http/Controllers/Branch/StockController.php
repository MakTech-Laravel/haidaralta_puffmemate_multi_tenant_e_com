<?php

namespace App\Http\Controllers\Branch;

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
        $branch = $this->currentBranch();
        $products = StockPageContext::requireProducts($request->validated('product_ids'));
        $quantity = (int) $request->validated('quantity');

        DB::transaction(function () use ($branch, $products, $quantity, $request): void {
            foreach ($products as $product) {
                $inventory = $this->inventory->firstOrCreate($branch, $product);
                $this->inventory->adjust(
                    $inventory,
                    InventoryTransactionType::StockIn,
                    $quantity,
                    $request->user('branch'),
                );
            }
        });

        Toast::success('Stock imported.');

        return redirect()->back();
    }

    public function transactions(Request $request): Response
    {
        $branch = $this->currentBranch();
        $this->mergeSelectionFilters($request);

        return Inertia::render('branch/stock/transactions', [
            'table' => InventoryTransactionDataTable::makeFor($request, $branch->id, 'branch', $branch->slug),
            'context' => StockPageContext::fromRequest($request, $branch),
        ]);
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
            StockPageContext::fromRequest($request, $this->currentBranch());
        }

        $request->merge(['filter' => $filters]);
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
