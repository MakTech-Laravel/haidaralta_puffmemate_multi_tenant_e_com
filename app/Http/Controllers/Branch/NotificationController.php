<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Display the branch notifications inbox.
     */
    public function index(): Response
    {
        return Inertia::render('branch/notifications/index');
    }
}
