<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /**
     * Display a demo order tracking page.
     */
    public function show(string $order): Response
    {
        $detail = DemoCatalog::customerOrderDetail($order);

        if ($detail === null) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('customer/orders/show', [
            'order' => $detail,
        ]);
    }
}
