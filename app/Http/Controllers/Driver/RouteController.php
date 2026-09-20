<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class RouteController extends Controller
{
    /**
     * Sequenced stops for this shift. The center field tab — not a GPS map yet.
     */
    public function index(): Response
    {
        $orders = DemoCatalog::driverAssignedOrders();
        $remaining = array_values(array_filter(
            $orders,
            static fn (array $order): bool => $order['status'] !== 'delivered',
        ));
        $completed = array_values(array_filter(
            $orders,
            static fn (array $order): bool => $order['status'] === 'delivered',
        ));

        return Inertia::render('driver/route/index', [
            'remaining' => $remaining,
            'completed' => $completed,
            'nextStop' => $remaining[0] ?? null,
        ]);
    }
}
