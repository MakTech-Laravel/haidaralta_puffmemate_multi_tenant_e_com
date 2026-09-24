<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class PacksSavesController extends Controller
{
    public function __invoke(): Response
    {
        $products = array_values(array_filter(
            DemoCatalog::products(),
            fn (array $product): bool => filled($product['compareAtPrice'] ?? null),
        ));

        return Inertia::render('storefront/packs-saves', [
            'products' => $products,
            'branches' => DemoCatalog::branches(),
        ]);
    }
}
