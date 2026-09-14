<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductTag;
use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $branch = StorefrontCatalog::selectedBranch($request);

        return Inertia::render('storefront/home', [
            'featuredCategories' => StorefrontCatalog::featuredCategories(),
            'bestSellingProducts' => StorefrontCatalog::productsWithTag(ProductTag::BestSelling, $branch),
            'newArrivalProducts' => StorefrontCatalog::productsWithTag(ProductTag::NewArrival, $branch),
            'selectedBranchId' => $branch?->slug,
        ]);
    }
}
