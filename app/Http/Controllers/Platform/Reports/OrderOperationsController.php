<?php

namespace App\Http\Controllers\Platform\Reports;

use App\Http\Controllers\Controller;
use App\Services\Order\OrderOperationsReportService;
use Inertia\Inertia;
use Inertia\Response;

class OrderOperationsController extends Controller
{
    public function index(OrderOperationsReportService $reports): Response
    {
        return Inertia::render('platform/reports/order-operations', [
            'report' => $reports->platform(),
        ]);
    }
}
