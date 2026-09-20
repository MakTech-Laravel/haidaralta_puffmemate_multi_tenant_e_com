<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Stop and delivery alerts. Empty until the driver notification
     * feed is wired; the header bell and tab bar land here.
     */
    public function index(): Response
    {
        return Inertia::render('driver/notifications/index', [
            'unreadCount' => 0,
        ]);
    }
}
