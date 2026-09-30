<?php

namespace App\Http\Controllers\Platform\Reports;

use App\DataTables\DriverInventoryTransactionDataTable;
use App\Enums\DriverAssignmentStatus;
use App\Enums\UserType;
use App\Exports\DriverInventoryTransactionExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportSpreadsheetRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
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

    public function branchOptions(Request $request): JsonResponse
    {
        return response()->json(StockPageContext::paginatedBranchOptions($request));
    }

    public function driverOptions(Request $request): JsonResponse
    {
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));
        $search = trim((string) $request->input('filter.search', ''));
        $branchId = $this->optionalBranchId($request);

        $query = User::query()
            ->where('user_type', UserType::DRIVER)
            ->where('is_active', true)
            ->orderBy('name');

        if ($branchId !== null) {
            $query->whereHas(
                'driverBranchAssignments',
                fn ($q) => $q
                    ->where('branch_id', $branchId)
                    ->where('status', DriverAssignmentStatus::Active),
            );
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (User $driver): array => [
                'id' => $driver->id,
                'name' => $driver->name,
                'sku' => $driver->phone,
            ])->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function index(Request $request): Response
    {
        $this->mergeSelectionFilters($request);

        return Inertia::render('platform/reports/product-assign-transactions', [
            'table' => DriverInventoryTransactionDataTable::makeFor($request),
            'context' => StockPageContext::fromRequest($request),
        ]);
    }

    public function export(ExportSpreadsheetRequest $request): BinaryFileResponse
    {
        $this->mergeSelectionFilters($request);

        $dataTable = DriverInventoryTransactionDataTable::instanceFor($request);

        return Excel::download(
            new DriverInventoryTransactionExport(
                $dataTable->exportQuery($request->exportScope()),
                $dataTable->includesBranchColumn(),
            ),
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

    private function optionalBranchId(Request $request): ?int
    {
        $filters = $request->input('filter', []);
        $filters = is_array($filters) ? $filters : [];
        $raw = $filters['branch_id'] ?? $request->query('branch');

        if ($raw === null || $raw === '' || $raw === 'all') {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }
}
