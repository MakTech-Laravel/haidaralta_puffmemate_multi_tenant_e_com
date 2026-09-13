<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    /**
     * Display the storefront checkout page.
     */
    public function index(): Response
    {
        return Inertia::render('storefront/checkout', DemoCatalog::checkout());
    }
}
