<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Driver\DriverPlaceOrderCatalog;
use Inertia\Inertia;
use Inertia\Response;

class PlaceOrderController extends Controller
{
    /**
     * Place Order screen: real driver_inventories products; demo customers/payments until live create.
     */
    public function index(): Response
    {
        /** @var User $driver */
        $driver = auth('driver')->user();

        return Inertia::render(
            'driver/place-order/index',
            DriverPlaceOrderCatalog::forDriver($driver),
        );
    }
}
