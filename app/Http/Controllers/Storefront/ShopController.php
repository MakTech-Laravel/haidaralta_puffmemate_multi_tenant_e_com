<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $branch = StorefrontCatalog::selectedBranch($request);

        return Inertia::render('storefront/shop', [
            'products' => StorefrontCatalog::products($branch),
            'branches' => StorefrontCatalog::branches(),
            'filters' => [
                'branchId' => $branch?->slug,
            ],
        ]);
    }
}
