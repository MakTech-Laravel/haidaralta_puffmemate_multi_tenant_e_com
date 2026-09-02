<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the platform dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('platform/dashboard');
    }
}
