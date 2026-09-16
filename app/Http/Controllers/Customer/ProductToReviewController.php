<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /**
     * Display the write-review form for a demo product.
     */
    public function show(string $product): Response
    {
        $item = DemoCatalog::productToReview($product);

        if ($item === null) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('customer/product-to-review/show', [
            'product' => $item,
        ]);
    }
}
