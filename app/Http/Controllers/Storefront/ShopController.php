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
        $search = $request->string('search')->trim()->toString();
        $sort = $request->string('sort')->trim()->toString();
        $sort = $sort !== '' ? $sort : null;

        return Inertia::render('storefront/shop', [
            'products' => StorefrontCatalog::products(
                $branch,
                $search !== '' ? $search : null,
                $sort,
            ),
            'branches' => StorefrontCatalog::branches(),
            'filters' => [
                'search' => $search,
                'branchId' => $branch?->slug,
                'sort' => $sort,
            ],
        ]);
    }
}
