<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the customer dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('customer/dashboard');
    }
}
