<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class PlaceOrderController extends Controller
{
    /**
     * Design-only Place Order screen for drivers (DemoCatalog until live create exists).
     */
    public function index(): Response
    {
        return Inertia::render('driver/place-order/index', DemoCatalog::driverPlaceOrder());
    }
}
