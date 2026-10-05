<?php

namespace App\Http\Controllers\Branch\Reports;

use App\DataTables\BranchOrderAssignHistoryDataTable;
use App\Enums\DriverAssignmentStatus;
use App\Enums\UserType;
use App\Exports\OrderDriverAssignmentHistoryExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportSpreadsheetRequest;
use App\Models\Branch;
use App\Models\User;
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
        $branch = $this->currentBranch();
        $this->applyDefaultDateFilter($request);

        return Inertia::render('branch/reports/order-assign-history', [
            'branch' => $branch->toPortalContext(),
            'table' => BranchOrderAssignHistoryDataTable::makeForBranch(
                $request,
                $branch->id,
                $branch->slug,
            ),
            'driverOptionsUrl' => route('branch.reports.order-assign-history.driver-options', $branch),
        ]);
    }

    public function export(ExportSpreadsheetRequest $request): BinaryFileResponse
    {
        $branch = $this->currentBranch();
        $this->applyDefaultDateFilter($request);

        $dataTable = BranchOrderAssignHistoryDataTable::instanceFor(
            $request,
            $branch->id,
            $branch->slug,
        );

        return Excel::download(
            new OrderDriverAssignmentHistoryExport($dataTable->exportQuery($request->exportScope())),
            $request->filename('order-assign-history'),
            $request->exportFormat()->writerType(),
        );
    }

    public function driverOptions(Request $request): JsonResponse
    {
        $branch = $this->currentBranch();
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));
        $search = trim((string) $request->input('filter.search', ''));

        $query = User::query()
            ->where('user_type', UserType::DRIVER)
            ->where('is_active', true)
            ->whereHas(
                'driverBranchAssignments',
                fn ($assignments) => $assignments
                    ->where('branch_id', $branch->id)
                    ->where('status', DriverAssignmentStatus::Active),
            )
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($filters) use ($search): void {
                $filters->where('name', 'like', "%{$search}%")
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

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
