<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductTag;
use App\Http\Controllers\Controller;
use App\Services\Cms\HomeBannerService;
use App\Support\Storefront\StorefrontCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomeBannerService $banners,
    ) {}

    public function __invoke(Request $request): Response
    {
        $branch = StorefrontCatalog::selectedBranch($request);
        $limit = (int) config('catalog.home_products_max');

        return Inertia::render('storefront/home', [
            'heroSlides' => $this->banners->publishedForStorefront(),
            'featuredCategories' => StorefrontCatalog::featuredCategories(),
            'bestSellingProducts' => StorefrontCatalog::productsWithTag(
                ProductTag::BestSelling,
                $branch,
                $limit,
            ),
            'newArrivalProducts' => StorefrontCatalog::productsWithTag(
                ProductTag::NewArrival,
                $branch,
                $limit,
            ),
        ]);
    }
}
