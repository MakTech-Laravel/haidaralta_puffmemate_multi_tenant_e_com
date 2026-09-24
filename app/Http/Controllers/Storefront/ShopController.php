<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontShopQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function __construct(
        private readonly StorefrontShopQuery $shopQuery,
    ) {}

    public function __invoke(Request $request): Response
    {
        $payload = $this->shopQuery->fromRequest($request);

        return Inertia::render('storefront/shop', [
            'products' => $payload['products'],
            'filters' => $payload['filters'],
            'filterOptions' => $payload['filterOptions'],
        ]);
    }
}
