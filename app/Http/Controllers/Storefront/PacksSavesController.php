<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PacksSavesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $branch = StorefrontCatalog::selectedBranch($request);
        $search = $request->string('search')->trim()->toString();
        $sort = $request->string('sort')->trim()->toString();

        $resolvedSort = $sort === '' ? null : $sort;

        return Inertia::render('storefront/packs-saves', [
            'packs' => StorefrontCatalog::packs($branch, $search, $resolvedSort),
            'products' => StorefrontCatalog::productsWithEnabledPackages($search, $resolvedSort),
            'featuredPack' => StorefrontCatalog::featuredPack($branch),
            'filters' => [
                'search' => $search,
                'sort' => $resolvedSort,
            ],
        ]);
    }
}
