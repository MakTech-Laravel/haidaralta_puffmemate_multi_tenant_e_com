<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
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
        $branches = DemoCatalog::branches();
        $branchId = $branch?->slug;
        $detail = DemoCatalog::productDetail($product);
        $categorySlug = $detail['categorySlug'];

        if ($branchId !== null) {
            foreach ($branches as $option) {
                if ($option['id'] === $branchId) {
                    $detail['branch'] = $option['name'];
                    break;
                }
            }
        }

        return Inertia::render('storefront/product', [
            'product' => $detail,
            'similarProducts' => DemoCatalog::similarProducts($product),
            'branches' => $branches,
            'branchId' => $branchId,
            'backHref' => $categorySlug
                ? route('shop.category', ['category' => $categorySlug], false)
                : route('shop', absolute: false),
        ]);
    }
}
