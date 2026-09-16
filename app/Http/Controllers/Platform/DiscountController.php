<?php

namespace App\Http\Controllers\Platform;

use App\Enums\DiscountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Discount\UpdateDiscountRequest;
use App\Services\Inventory\BranchPricingService;
use App\Support\CatalogReturnTo;
use App\Support\StockPageContext;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class DiscountController extends Controller
{
    public function __construct(
        private BranchPricingService $pricing,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('platform/discounts/index', [
            'context' => StockPageContext::fromRequest($request),
            'discountTypes' => [
                ['value' => DiscountType::None->value, 'label' => 'None'],
                ['value' => DiscountType::Fixed->value, 'label' => 'Fixed'],
                ['value' => DiscountType::Percent->value, 'label' => 'Percent'],
            ],
            'createProductUrl' => route('platform.products.create', [
                'return_to' => CatalogReturnTo::currentUrl($request),
            ]),
        ]);
    }

    public function update(UpdateDiscountRequest $request): RedirectResponse
    {
        $branches = StockPageContext::requireBranches($request->validated('branch_ids'));
        $products = StockPageContext::requireProducts($request->validated('product_ids'));
        $data = $request->safe()->only([
            'discount_type',
            'discount_price',
            'start_date',
            'end_date',
        ]);

        try {
            DB::transaction(function () use ($branches, $products, $data): void {
                foreach ($branches as $branch) {
                    foreach ($products as $product) {
                        $this->pricing->applyDiscount($branch, $product, $data);
                    }
                }
            });
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Discount updated.');

        return redirect()->back();
    }

    public function preview(Request $request): JsonResponse
    {
        $branchIds = collect($request->input('branch_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $productIds = collect($request->input('product_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        return response()->json($this->pricing->previewSelectedPairs($branchIds, $productIds));
    }
}
