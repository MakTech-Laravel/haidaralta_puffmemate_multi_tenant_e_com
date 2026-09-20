<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the driver dashboard.
     */
    public function index(): Response
    {
        $orders = DemoCatalog::driverAssignedOrders();
        $activeOrders = DemoCatalog::driverRemainingOrders();
        $nextStop = $activeOrders[0] ?? null;
        $nextDetail = $nextStop !== null
            ? DemoCatalog::driverOrderDetail($nextStop['id'])
            : null;

        return Inertia::render('driver/dashboard', [
            'summary' => [
                'activeCount' => count($activeOrders),
                'deliveredCount' => count($orders) - count($activeOrders),
                'assignedCount' => count($orders),
                'nextStop' => $nextStop,
                'activeOrders' => array_slice($activeOrders, 0, 6),
            ],
            'hub' => [
                ...DemoCatalog::driverDashboardHub(),
                'nextCustomerPhone' => $nextDetail['phone'] ?? null,
                'nextNotes' => $nextDetail['notes'] ?? null,
            ],
        ]);
    }
}
