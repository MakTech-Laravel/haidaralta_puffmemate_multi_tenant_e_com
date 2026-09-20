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
     * Sequenced assigned stops for this shift. The center field tab — not a GPS map yet.
     */
    public function index(Request $request): Response
    {
        $remaining = DemoCatalog::driverRemainingOrders();
        $completed = DemoCatalog::driverCompletedOrders();
        $highlightId = $request->query('highlight');

        return Inertia::render('driver/route/index', [
            'remaining' => $remaining,
            'completed' => $completed,
            'nextStop' => $remaining[0] ?? null,
            'highlightId' => is_string($highlightId) && $highlightId !== '' ? $highlightId : null,
        ]);
    }
}
