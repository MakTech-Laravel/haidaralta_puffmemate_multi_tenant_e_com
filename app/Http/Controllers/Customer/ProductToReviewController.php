<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class ProductToReviewController extends Controller
{
    /**
     * Display products awaiting customer review (demo).
     */
    public function index(): Response
    {
        return Inertia::render('customer/product-to-review/index', [
            'products' => DemoCatalog::productsToReview(),
        ]);
    }
}
