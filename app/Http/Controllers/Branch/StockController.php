<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\InventoryTransactionDataTable;
use App\Enums\DiscountType;
use App\Enums\InventoryTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\AdjustStockRequest;
use App\Http\Requests\Stock\ImportStockRequest;
use App\Http\Requests\Stock\UpdateStockDiscountRequest;
use App\Http\Requests\Stock\UpdateStockPriceRequest;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Product;
use App\Services\Inventory\BranchPricingService;
use App\Services\Inventory\InventoryService;
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

class StockController extends Controller
{
    public function __construct(
        private InventoryService $inventory,
        private BranchPricingService $pricing,
    ) {}

    public function manage(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/stock/manage', [
            'context' => StockPageContext::fromRequest($request, $branch),
            'createProductUrl' => route('branch.products.create', [
                'branch' => $branch,
                'return_to' => CatalogReturnTo::currentUrl($request),
            ]),
        ]);
    }

    public function adjust(AdjustStockRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless((int) $request->validated('branch_id') === $branch->id, 404);

        $product = Product::query()->findOrFail((int) $request->validated('product_id'));
        $inventory = $this->inventory->firstOrCreate($branch, $product);

        try {
            $this->inventory->adjust(
                $inventory,
                InventoryTransactionType::from($request->validated('type')),
                (int) $request->validated('quantity'),
                $request->user('branch'),
            );
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Stock updated.');

        return redirect()->back();
    }

    public function import(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/stock/import', [
            'context' => StockPageContext::fromRequest($request, $branch),
            'createProductUrl' => route('branch.products.create', [
                'branch' => $branch,
                'return_to' => CatalogReturnTo::currentUrl($request),
            ]),
        ]);
    }

    public function storeImport(ImportStockRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $products = StockPageContext::requireProducts($request->validated('product_ids'));
        $quantity = (int) $request->validated('quantity');

        DB::transaction(function () use ($branch, $products, $quantity, $request): void {
            foreach ($products as $product) {
                $inventory = $this->inventory->firstOrCreate($branch, $product);
                $this->inventory->adjust(
                    $inventory,
                    InventoryTransactionType::StockIn,
                    $quantity,
                    $request->user('branch'),
                );
            }
        });

        Toast::success('Stock imported.');

        return redirect()->back();
    }

    public function price(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/stock/price', [
            'context' => StockPageContext::fromRequest($request, $branch),
            'createProductUrl' => route('branch.products.create', [
                'branch' => $branch,
                'return_to' => CatalogReturnTo::currentUrl($request),
            ]),
        ]);
    }

    public function updatePrice(UpdateStockPriceRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless((int) $request->validated('branch_id') === $branch->id, 404);

        $product = Product::query()->findOrFail((int) $request->validated('product_id'));
        $existing = $this->existingPrice($branch, $product);

        $this->pricing->upsert($branch, $product, [
            'selling_price' => $request->validated('selling_price'),
            'discount_type' => $existing?->discount_type ?? DiscountType::None,
            'discount_price' => $existing?->discount_price,
            'start_date' => $existing?->start_date?->toDateString(),
            'end_date' => $existing?->end_date?->toDateString(),
            'status' => true,
        ]);

        Toast::success('Price updated.');

        return redirect()->back();
    }

    public function discount(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/stock/discount', [
            'context' => StockPageContext::fromRequest($request, $branch),
            'discountTypes' => [
                ['value' => DiscountType::None->value, 'label' => 'None'],
                ['value' => DiscountType::Fixed->value, 'label' => 'Fixed'],
                ['value' => DiscountType::Percent->value, 'label' => 'Percent'],
            ],
            'createProductUrl' => route('branch.products.create', [
                'branch' => $branch,
                'return_to' => CatalogReturnTo::currentUrl($request),
            ]),
        ]);
    }

    public function updateDiscount(UpdateStockDiscountRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        abort_unless((int) $request->validated('branch_id') === $branch->id, 404);

        $product = Product::query()->findOrFail((int) $request->validated('product_id'));
        $data = $request->validated();
        $data['status'] = true;

        $this->pricing->upsert($branch, $product, $data);

        Toast::success('Discount updated.');

        return redirect()->back();
    }

    public function transactions(Request $request): Response
    {
        $branch = $this->currentBranch();
        $this->mergeSelectionFilters($request);

        return Inertia::render('branch/stock/transactions', [
            'table' => InventoryTransactionDataTable::makeFor($request, $branch->id, 'branch', $branch->slug),
            'context' => StockPageContext::fromRequest($request, $branch),
        ]);
    }

    public function productOptions(Request $request): JsonResponse
    {
        return response()->json(StockPageContext::paginatedProductOptions($request));
    }

    private function existingPrice(Branch $branch, Product $product): ?BranchProductPrice
    {
        return BranchProductPrice::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->where('status', true)
            ->first();
    }

    private function mergeSelectionFilters(Request $request): void
    {
        $filters = $request->input('filter', []);
        $filters = is_array($filters) ? $filters : [];

        if ($request->filled('product') && empty($filters['product_id'])) {
            $product = Product::query()->find($request->integer('product'))
                ?? Product::query()->where('slug', $request->string('product')->toString())->first();

            abort_unless($product !== null, 404);
            $filters['product_id'] = $product->id;
        } elseif ($request->filled('product')) {
            StockPageContext::fromRequest($request, $this->currentBranch());
        }

        $request->merge(['filter' => $filters]);
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
