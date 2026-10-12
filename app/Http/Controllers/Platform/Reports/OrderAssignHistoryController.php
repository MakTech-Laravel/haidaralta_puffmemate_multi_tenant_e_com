<?php

namespace App\Http\Controllers\Platform\Reports;

use App\DataTables\OrderAssignHistoryDataTable;
use App\Enums\DriverAssignmentStatus;
use App\Enums\UserType;
use App\Exports\OrderDriverAssignmentHistoryExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportSpreadsheetRequest;
use App\Models\User;
use App\Support\Phone;
use App\Support\StockPageContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderAssignHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->applyDefaultDateFilter($request);

        return Inertia::render('platform/reports/order-assign-history', [
            'table' => OrderAssignHistoryDataTable::make($request),
            'driverOptionsUrl' => route('platform.reports.order-assign-history.driver-options'),
            'branchOptionsUrl' => route('platform.reports.order-assign-history.branch-options'),
        ]);
    }

    public function export(ExportSpreadsheetRequest $request): BinaryFileResponse
    {
        $this->applyDefaultDateFilter($request);

        $dataTable = OrderAssignHistoryDataTable::instanceFor($request);

        return Excel::download(
            new OrderDriverAssignmentHistoryExport(
                $dataTable->exportQuery($request->exportScope()),
                includeBranch: true,
            ),
            $request->filename('order-assign-history'),
            $request->exportFormat()->writerType(),
        );
    }

    public function branchOptions(Request $request): JsonResponse
    {
        return response()->json(StockPageContext::paginatedBranchOptions($request));
    }

    public function driverOptions(Request $request): JsonResponse
    {
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));
        $search = trim((string) $request->input('filter.search', ''));
        $branchId = $request->filled('filter.branch_id')
            ? (int) $request->input('filter.branch_id')
            : null;

        $query = User::query()
            ->where('user_type', UserType::DRIVER)
            ->where('is_active', true)
            ->orderBy('name');

        if ($branchId !== null && $branchId > 0) {
            $query->whereHas(
                'driverBranchAssignments',
                fn ($assignments) => $assignments
                    ->where('branch_id', $branchId)
                    ->where('status', DriverAssignmentStatus::Active),
            );
        }

        if ($search !== '') {
            $query->where(function ($filters) use ($search): void {
                $filters->where('name', 'like', "%{$search}%");
                Phone::constrainLike($filters, 'phone', $search);
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

    private function applyDefaultDateFilter(Request $request): void
    {
        $filters = $request->input('filter', []);
        $filters = is_array($filters) ? $filters : [];

        $hasOrderScope = filled($filters['fulfillment_id'] ?? null)
            || filled($filters['order_id'] ?? null);
        $hasDate = array_key_exists('date', $filters);

        if (! $hasOrderScope && ! $hasDate) {
            $filters['date'] = now()->toDateString();
            $request->merge(['filter' => $filters]);
        }
    }
}
