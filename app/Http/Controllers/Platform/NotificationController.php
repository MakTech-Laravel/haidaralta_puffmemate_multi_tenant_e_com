<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Display the platform notifications inbox.
     */
    public function index(): Response
    {
        return Inertia::render('platform/notifications/index');
    }
}
