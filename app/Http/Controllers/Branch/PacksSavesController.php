<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Product;
use App\Support\BranchProductPriceDisplay;
use Inertia\Inertia;
use Inertia\Response;

class PacksSavesController extends Controller
{
    public function index(): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/packs-saves/index', [
            'branch' => $branch->toPortalContext(),
            'packs' => $this->demoPacks(),
        ]);
    }

    public function create(): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/packs-saves/create', [
            'branch' => $branch->toPortalContext(),
            'availableProducts' => $this->availableProducts($branch),
        ]);
    }

    /**
     * Placeholder rows until packs persist. Do not treat as catalog data.
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     image_url: string|null,
     *     product_count: int,
     *     subtotal: string,
     *     pack_price: string,
     *     offer_type: string,
     *     offer_value: string,
     *     status: bool,
     *     starts_at: string|null,
     *     ends_at: string|null
     * }>
     */
    private function demoPacks(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Starter trio',
                'image_url' => null,
                'product_count' => 3,
                'subtotal' => '100.00',
                'pack_price' => '80.00',
                'offer_type' => 'percent',
                'offer_value' => '20',
                'status' => true,
                'starts_at' => null,
                'ends_at' => null,
            ],
            [
                'id' => 2,
                'name' => 'Weekend duo',
                'image_url' => null,
                'product_count' => 2,
                'subtotal' => '70.00',
                'pack_price' => '55.00',
                'offer_type' => 'fixed',
                'offer_value' => '15.00',
                'status' => true,
                'starts_at' => '2026-10-01',
                'ends_at' => '2026-10-31',
            ],
            [
                'id' => 3,
                'name' => 'Sampler pack',
                'image_url' => null,
                'product_count' => 4,
                'subtotal' => '60.00',
                'pack_price' => '40.00',
                'offer_type' => 'pack_price',
                'offer_value' => '40.00',
                'status' => false,
                'starts_at' => null,
                'ends_at' => null,
            ],
        ];
    }

    /**
     * Catalog-active SKUs that already have a stock row at this branch.
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     sku: string|null,
     *     image_url: string|null,
     *     selling_price: string|null,
     *     effective_price: string|null
     * }>
     */
    private function availableProducts(Branch $branch): array
    {
        $productIds = Inventory::query()
            ->where('branch_id', $branch->id)
            ->distinct()
            ->orderBy('product_id')
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return [];
        }

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('status', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $prices = collect();

        if (BranchProductPriceDisplay::usesBranchPricing() && $products->isNotEmpty()) {
            $prices = BranchProductPrice::query()
                ->where('branch_id', $branch->id)
                ->whereIn('product_id', $products->modelKeys())
                ->whereNull('variant_id')
                ->where('status', true)
                ->get()
                ->keyBy('product_id');
        }

        return $products
            ->map(function (Product $product) use ($prices): array {
                /** @var BranchProductPrice|null $price */
                $price = $prices->get($product->id);
                $display = BranchProductPriceDisplay::listDisplayForProductContext(
                    $price,
                    $product,
                );

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'image_url' => $product->imageUrl(),
                    'selling_price' => $display['selling_price'],
                    'effective_price' => $display['effective_price'],
                ];
            })
            ->values()
            ->all();
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
