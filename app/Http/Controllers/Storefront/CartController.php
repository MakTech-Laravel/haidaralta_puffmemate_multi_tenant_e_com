<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    /**
     * Display the storefront cart page.
     */
    public function index(): Response
    {
        $cart = DemoCatalog::cart();

        return Inertia::render('storefront/cart', $cart);
    }
}
