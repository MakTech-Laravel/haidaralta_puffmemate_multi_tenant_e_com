<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
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

        $products = DemoCatalog::products();

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $products = array_values(array_filter(
                $products,
                function (array $product) use ($needle): bool {
                    return str_contains(mb_strtolower($product['name']), $needle)
                        || str_contains(mb_strtolower($product['description']), $needle)
                        || str_contains(mb_strtolower($product['category']), $needle);
                },
            ));
        }

        $products = match ($sort) {
            'name_desc' => collect($products)->sortByDesc('name')->values()->all(),
            'name_asc' => collect($products)->sortBy('name')->values()->all(),
            'newest' => collect($products)->sortByDesc('id')->values()->all(),
            default => $products,
        };

        return Inertia::render('storefront/shop', [
            'products' => $products,
            'branches' => DemoCatalog::branches(),
            'filters' => [
                'search' => $search,
                'branchId' => $branch?->slug,
                'sort' => $sort,
            ],
        ]);
    }
}
