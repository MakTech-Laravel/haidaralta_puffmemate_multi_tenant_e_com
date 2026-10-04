<?php

namespace App\Http\Controllers\Branch\Reports;

use App\DataTables\DriverInventoryTransactionDataTable;
use App\Exports\DriverInventoryTransactionExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportSpreadsheetRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Support\StockPageContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductAssignTransactionController extends Controller
{
    public function productOptions(Request $request): JsonResponse
    {
        return response()->json(StockPageContext::paginatedProductOptions($request));
    }

    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();
        $this->mergeSelectionFilters($request);

        return Inertia::render('branch/reports/product-assign-transactions', [
            'table' => DriverInventoryTransactionDataTable::makeFor(
                $request,
                $branch->id,
                $branch->slug,
            ),
            'context' => StockPageContext::fromRequest($request, $branch),
        ]);
    }

    public function export(ExportSpreadsheetRequest $request): BinaryFileResponse
    {
        $branch = $this->currentBranch();
        $this->mergeSelectionFilters($request);

        $dataTable = DriverInventoryTransactionDataTable::instanceFor(
            $request,
            $branch->id,
            $branch->slug,
        );

        return Excel::download(
            new DriverInventoryTransactionExport($dataTable->exportQuery($request->exportScope())),
            $request->filename('product-assign-transactions'),
            $request->exportFormat()->writerType(),
        );
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
