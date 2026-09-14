<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a storefront category catalog page.
     */
    public function show(Request $request, string $category): Response
    {
        $branch = StorefrontCatalog::selectedBranch($request);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('storefront/category', [
            'category' => StorefrontCatalog::resolveCategory($category),
            'products' => StorefrontCatalog::productsForCategory(
                $category,
                $branch,
                $search !== '' ? $search : null,
            ),
            'branches' => StorefrontCatalog::branches(),
            'filters' => [
                'search' => $search,
                'branchId' => $branch?->slug,
            ],
        ]);
    }
}
