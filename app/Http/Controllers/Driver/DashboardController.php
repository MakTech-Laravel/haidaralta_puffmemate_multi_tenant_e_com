<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the driver dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('driver/dashboard');
    }
}
