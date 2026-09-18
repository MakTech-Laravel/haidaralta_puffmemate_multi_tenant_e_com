<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotificationController extends Controller
{
    /**
     * Display the customer notifications list (demo).
     */
    public function index(): Response
    {
        return Inertia::render('customer/notifications/index', [
            'notifications' => DemoCatalog::customerNotifications(),
        ]);
    }

    /**
     * Display a demo notification detail page.
     */
    public function show(string $notification): Response
    {
        $detail = DemoCatalog::customerNotificationDetail($notification);

        if ($detail === null) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('customer/notifications/show', [
            'notification' => $detail,
        ]);
    }
}
