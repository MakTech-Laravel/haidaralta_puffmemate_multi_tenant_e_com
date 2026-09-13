<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a storefront category catalog page.
     */
    public function show(string $category): Response
    {
        $resolved = DemoCatalog::resolveCategory($category);

        return Inertia::render('storefront/category', [
            'category' => $resolved,
            'products' => DemoCatalog::productsForCategory($category),
            'branches' => DemoCatalog::branches(),
            'filters' => [
                'search' => '',
                'branchId' => null,
            ],
        ]);
    }
}
