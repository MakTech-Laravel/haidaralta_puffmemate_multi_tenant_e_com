<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreProductReviewRequest;
use App\Services\Customer\ProductToReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductToReviewController extends Controller
{
    public function __construct(
        private ProductToReviewService $reviews,
    ) {}

    /**
     * Display products from the customer's delivered orders.
     */
    public function index(Request $request): Response
    {
        $products = $this->reviews->paginateForCustomer($request->user());

        return Inertia::render('customer/product-to-review/index', [
            'products' => $products->getCollection()->values()->all(),
            'meta' => [
                'currentPage' => $products->currentPage(),
                'lastPage' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * Display the write-review form for a delivered order line.
     */
    public function show(Request $request, int $item): Response|RedirectResponse
    {
        $product = $this->reviews->findForCustomer($request->user(), $item);

        if ($product === null) {
            throw new NotFoundHttpException;
        }

        if ($product['reviewed']) {
            return redirect()->route('customer.product-to-review.index');
        }

        return Inertia::render('customer/product-to-review/show', [
            'product' => $product,
        ]);
    }

    /**
     * Persist a review for a delivered order line.
     */
    public function store(StoreProductReviewRequest $request, int $item): RedirectResponse
    {
        $this->reviews->storeForCustomer(
            $request->user(),
            $item,
            $request->integer('rating'),
            $request->string('body')->toString(),
        );

        return redirect()->route('customer.product-to-review.index');
    }
}
