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

        return Inertia::render('storefront/packs-saves', [
            'products' => StorefrontCatalog::productsOnOffer($branch),
            'branches' => StorefrontCatalog::branches(),
        ]);
    }
}
