<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RouteController extends Controller
{
    /**
     * Sequenced neighbourhood batches for this shift. The center field tab — not a GPS map yet.
     */
    public function index(Request $request): Response
    {
        $areas = DemoCatalog::driverAssignedAreas();
        $run = DemoCatalog::driverRunAreas();
        $remaining = DemoCatalog::flattenDriverAreaOrders($areas, 'remaining');
        $completed = DemoCatalog::flattenDriverAreaOrders($areas, 'delivered');
        $highlightId = $request->query('highlight');

        return Inertia::render('driver/route/index', [
            'areas' => $areas,
            'remaining' => $remaining,
            'completed' => $completed,
            'nextStop' => $remaining[0] ?? null,
            'currentArea' => $run['current'],
            'highlightId' => is_string($highlightId) && $highlightId !== '' ? $highlightId : null,
        ]);
    }
}
