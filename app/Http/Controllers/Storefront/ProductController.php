<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Storefront\StorefrontCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    /**
     * Display a storefront product details page.
     */
    public function show(Request $request, string $product): Response
    {
        $branch = StorefrontCatalog::selectedBranch($request);
        $model = Product::query()
            ->where('status', true)
            ->where('slug', $product)
            ->with(['categories:id,name,slug', 'images', 'variants'])
            ->first();

        if ($model === null) {
            throw new NotFoundHttpException;
        }

        $detail = StorefrontCatalog::productDetail($model, $branch);
        $categorySlug = $detail['categorySlug'] ?? null;

        return Inertia::render('storefront/product', [
            'product' => $detail,
            'similarProducts' => StorefrontCatalog::similarProducts($model, $branch),
            'backHref' => is_string($categorySlug) && $categorySlug !== ''
                ? route('shop.category', ['category' => $categorySlug], false)
                : route('shop', absolute: false),
        ]);
    }
}
