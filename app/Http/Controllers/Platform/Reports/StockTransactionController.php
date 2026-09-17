<?php

namespace App\Http\Controllers\Platform\Reports;

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
        $this->mergeSelectionFilters($request);

        return Inertia::render('platform/reports/stock-transactions', [
            'table' => InventoryTransactionDataTable::makeFor($request),
            'context' => StockPageContext::fromRequest($request),
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
