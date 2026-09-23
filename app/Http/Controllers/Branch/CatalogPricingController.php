<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\UpsertCatalogProductPriceRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Services\Catalog\CatalogPricingService;
use App\Support\BranchProductShowPayload;
use App\Support\CatalogProductPricingPayload;
use App\Support\StockPageContext;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogPricingController extends Controller
{
    public function __construct(
        private CatalogPricingService $pricing,
    ) {}

    public function manage(Request $request): Response
    {
        abort_unless(! (bool) config('catalog.show_branch_pricing'), 404);

        $product = StockPageContext::optionalProduct($request);

        if ($product === null) {
            return Inertia::render('branch/catalog/pricing/manage', [
                'product' => null,
                'discountTypes' => BranchProductShowPayload::discountTypes(),
            ]);
        }

        return Inertia::render('branch/catalog/pricing/manage', [
            'product' => CatalogProductPricingPayload::product($product),
            'discountTypes' => BranchProductShowPayload::discountTypes(),
        ]);
    }

    public function upsert(
        UpsertCatalogProductPriceRequest $request,
        string $branchSlug,
        Product $product,
    ): RedirectResponse {
        $branch = $this->currentBranch();
        $this->pricing->upsert($product, $request->validated());

        Toast::success('Product price updated.');

        return redirect()->route('branch.catalog.pricing.manage', [
            'branch' => $branch,
            'product' => $product->id,
        ]);
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
