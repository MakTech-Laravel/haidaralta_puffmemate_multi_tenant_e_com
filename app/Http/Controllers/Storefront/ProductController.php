<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Storefront\ProductViewService;
use App\Support\Storefront\StorefrontCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\Support\Header;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function __construct(
        private ProductViewService $views,
    ) {}

    /**
     * Display a storefront product details page.
     */
    public function show(Request $request, string $product): Response
    {
        $branch = StorefrontCatalog::selectedBranch($request);
        $model = Product::query()
            ->where('status', true)
            ->where('slug', $product)
            ->with(['categories:id,name,slug', 'images', 'variants'])
            ->first();

        if ($model === null) {
            throw new NotFoundHttpException;
        }

        if ($this->shouldCountView($request)) {
            $this->views->record($request, $model);
        }

        $detail = StorefrontCatalog::productDetail($model, $branch);
        $categorySlug = $detail['categorySlug'] ?? null;

        return Inertia::render('storefront/product', [
            'product' => $detail,
            'similarProducts' => StorefrontCatalog::similarProducts($model, $branch),
            'backHref' => is_string($categorySlug) && $categorySlug !== ''
                ? route('shop.category', ['category' => $categorySlug], false)
                : route('shop', absolute: false),
            'tab' => $request->string('tab')->toString(),
        ]);
    }

    /**
     * Only real page visits count: prefetches and Inertia partial reloads
     * (such as the cart reload after Add to cart redirects back) do not.
     */
    private function shouldCountView(Request $request): bool
    {
        if (strtolower((string) $request->header('Purpose')) === 'prefetch') {
            return false;
        }

        return $request->header(Header::PARTIAL_COMPONENT) !== 'storefront/product';
    }
}
