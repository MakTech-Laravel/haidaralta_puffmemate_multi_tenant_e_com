<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('driver/orders/index', [
            'orders' => DemoCatalog::driverAssignedOrders(),
            'status' => $request->session()->get('success'),
        ]);
    }

    public function show(string $order): Response
    {
        $detail = DemoCatalog::driverOrderDetail($order);

        abort_if($detail === null, 404);

        return Inertia::render('driver/orders/show', [
            'order' => $detail,
        ]);
    }

    /**
     * Order-scoped thread. Drivers enter from the stop, never a global inbox.
     */
    public function chat(string $order): Response
    {
        $detail = DemoCatalog::driverOrderDetail($order);

        abort_if($detail === null, 404);

        return Inertia::render('driver/orders/chat', [
            'order' => $detail,
        ]);
    }

    public function deliver(Request $request, string $order): RedirectResponse
    {
        abort_if(DemoCatalog::driverOrderDetail($order) === null, 404);

        $request->validate([
            'otp' => ['required', 'string', 'regex:/^\d{4,}$/'],
        ]);

        return to_route('driver.orders.index')
            ->with('success', 'Order marked as delivered.');
    }
}
