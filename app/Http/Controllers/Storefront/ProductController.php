<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
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
        $model = Product::query()->where('slug', $product)->firstOrFail();
        $categorySlug = $detail['categorySlug'];

        return Inertia::render('storefront/product', [
            'product' => $detail,
            'similarProducts' => StorefrontCatalog::similarProducts($model, $branch),
            'branches' => StorefrontCatalog::branches(),
            'selectedBranchId' => $branch?->slug,
            'backHref' => $categorySlug
                ? route('shop.category', ['category' => $categorySlug], false)
                : route('shop', absolute: false),
        ]);
    }
}
