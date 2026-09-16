<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display the customer orders list (demo).
     */
    public function index(): Response
    {
        return Inertia::render('customer/orders/index', [
            'orders' => DemoCatalog::customerOrders(),
        ]);
    }
}
