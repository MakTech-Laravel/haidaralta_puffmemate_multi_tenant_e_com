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
        $areas = DemoCatalog::driverAssignedAreas();
        $run = DemoCatalog::driverRunAreas();
        $currentArea = $run['current'];
        $activeOrders = array_values(array_filter(
            $orders,
            static fn (array $order): bool => $order['status'] !== 'delivered',
        ));
        $nextStop = $currentArea !== null
            ? ($currentArea['remaining'][0] ?? null)
            : ($activeOrders[0] ?? null);
        $nextDetail = $nextStop !== null
            ? DemoCatalog::driverOrderDetail($nextStop['id'])
            : null;

        return Inertia::render('driver/dashboard', [
            'summary' => [
                'activeCount' => count($activeOrders),
                'deliveredCount' => count($orders) - count($activeOrders),
                'assignedCount' => count($orders),
                'areaCount' => count($areas),
                'nextStop' => $nextStop,
                'activeOrders' => $currentArea !== null
                    ? $currentArea['remaining']
                    : array_slice($activeOrders, 0, 6),
                'currentArea' => $currentArea,
                'laterAreas' => $run['later'],
            ],
            'hub' => [
                ...DemoCatalog::driverDashboardHub(),
                'nextCustomerPhone' => $nextDetail['phone'] ?? null,
                'nextNotes' => $nextDetail['notes'] ?? null,
            ],
        ]);
    }
}
