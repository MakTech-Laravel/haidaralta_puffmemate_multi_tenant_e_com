<?php

namespace App\Http\Controllers\Branch\Reports;

use App\DataTables\InventoryTransactionDataTable;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Support\StockPageContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockTransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();
        $this->mergeSelectionFilters($request);

        return Inertia::render('branch/reports/stock-transactions', [
            'table' => InventoryTransactionDataTable::makeFor($request, $branch->id, 'branch', $branch->slug),
            'context' => StockPageContext::fromRequest($request, $branch),
        ]);
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
