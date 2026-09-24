<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a storefront product details page.
     */
    public function show(Request $request, string $product): Response
    {
        $branch = StorefrontCatalog::selectedBranch($request);
        $detail = StorefrontCatalog::productDetail($product, $branch);
        $categorySlug = $detail['categorySlug'] ?? null;

        return Inertia::render('storefront/product', [
            'product' => $detail,
            'similarProducts' => StorefrontCatalog::similarProducts($product, $branch),
            'backHref' => is_string($categorySlug) && $categorySlug !== ''
                ? route('shop.category', ['category' => $categorySlug], false)
                : route('shop', absolute: false),
        ]);
    }
}
