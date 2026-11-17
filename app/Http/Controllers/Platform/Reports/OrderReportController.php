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
        return Inertia::render('platform/reports/order-reports', [
            'dashboard' => $this->dashboard($request),
        ]);
    }

    public function export(ExportSpreadsheetRequest $request): BinaryFileResponse
    {
        return Excel::download(
            new OrderReportsExport($this->dashboard($request)),
            $request->filename('order-reports'),
            $request->exportFormat()->writerType(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboard(Request $request): array
    {
        $month = $request->input('filter.month');
        $compare = $request->input('filter.compare');

        return $this->reports->platformDashboard(
            is_string($month) ? $month : null,
            is_string($compare) ? $compare : null,
        );
    }
}
