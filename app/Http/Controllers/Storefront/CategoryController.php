<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontCatalog;
use App\Support\Storefront\StorefrontShopQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        private readonly StorefrontShopQuery $shopQuery,
    ) {}

    /**
     * Display a storefront category catalog page.
     */
    public function show(Request $request, string $category): Response
    {
        $resolved = StorefrontCatalog::resolveCategory($category);
        $payload = $this->shopQuery->fromRequest($request, $resolved['slug']);

        return Inertia::render('storefront/category', [
            'category' => $resolved,
            'products' => $payload['products'],
            'filters' => $payload['filters'],
        ]);
    }
}
