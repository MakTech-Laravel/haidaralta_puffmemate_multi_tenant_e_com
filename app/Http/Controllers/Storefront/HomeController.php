<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Cms\HomeBannerService;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomeBannerService $banners,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('storefront/home', [
            'heroSlides' => $this->banners->publishedForStorefront(),
            'featuredCategories' => DemoCatalog::featuredCategories(),
            'bestSellingProducts' => DemoCatalog::bestSellingProducts(),
            'newArrivalProducts' => DemoCatalog::newArrivalProducts(),
        ]);
    }
}
