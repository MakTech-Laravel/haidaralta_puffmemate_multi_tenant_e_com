<?php

namespace App\Http\Controllers\Platform\Reports;

use App\Exports\OrderReportsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportSpreadsheetRequest;
use App\Services\Order\OrderReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderReportController extends Controller
{
    public function __construct(
        private OrderReportService $reports,
    ) {}

    public function index(Request $request): Response
    {
        $period = $this->reports->resolvePeriod(
            is_string($request->input('filter.period'))
                ? $request->input('filter.period')
                : null,
        );

        return Inertia::render('platform/reports/order-reports', [
            'dashboard' => $this->reports->platformDashboard($period),
        ]);
    }

    public function export(ExportSpreadsheetRequest $request): BinaryFileResponse
    {
        $period = $this->reports->resolvePeriod(
            is_string($request->input('filter.period'))
                ? $request->input('filter.period')
                : null,
        );

        $dashboard = $this->reports->platformDashboard($period);

        return Excel::download(
            new OrderReportsExport($dashboard),
            $request->filename('order-reports'),
            $request->exportFormat()->writerType(),
        );
    }
}
