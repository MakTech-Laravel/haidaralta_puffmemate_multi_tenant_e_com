<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a storefront product details page.
     */
    public function show(string $product): Response
    {
        $detail = DemoCatalog::productDetail($product);
        $categorySlug = $detail['categorySlug'];

        return Inertia::render('storefront/product', [
            'product' => $detail,
            'similarProducts' => DemoCatalog::similarProducts($product),
            'backHref' => $categorySlug
                ? route('shop.category', ['category' => $categorySlug], false)
                : route('shop', absolute: false),
        ]);
    }
}
