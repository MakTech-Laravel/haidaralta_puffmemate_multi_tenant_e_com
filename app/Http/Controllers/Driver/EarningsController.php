<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class EarningsController extends Controller
{
    /**
     * Shift pay, tips, and completed-drop totals for this driver.
     */
    public function index(): Response
    {
        return Inertia::render('driver/earnings/index', [
            'earnings' => DemoCatalog::driverEarnings(),
        ]);
    }
}
