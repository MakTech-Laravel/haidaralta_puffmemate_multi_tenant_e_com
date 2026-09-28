<?php

namespace App\Http\Controllers\Branch\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Order\OrderOperationsReportService;
use Inertia\Inertia;
use Inertia\Response;

class OrderOperationsController extends Controller
{
    public function index(OrderOperationsReportService $reports): Response
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return Inertia::render('branch/reports/order-operations', [
            'branch' => $branch->toPortalContext(),
            'report' => $reports->branch($branch),
        ]);
    }
}
