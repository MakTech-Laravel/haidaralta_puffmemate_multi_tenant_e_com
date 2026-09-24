<?php

namespace Database\Seeders;

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Inventory\BranchPricingService;
use App\Services\Inventory\InventoryService;
use Illuminate\Database\Seeder;

class BranchInventorySeeder extends Seeder
{
    /**
     * Seed cigarettes / tobacco / vapes stock per branch (no Faker).
     * Depends on BranchSeeder + CatalogSeeder.
     */
    public function run(): void
    {
        $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
        $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
        $brisbane = Branch::query()->where('slug', BranchSeeder::BRISBANE_QUEEN)->firstOrFail();

        $inventory = app(InventoryService::class);
        $pricing = app(BranchPricingService::class);

        // Sydney CBD flagship: full cig / tobacco / vape assortment.
        $this->seedBranch($sydney, $inventory, $pricing, [
            'marlboro-gold-soft-pack-20s' => ['qty' => 120, 'min' => 24, 'price' => '48.95', 'discount_type' => 'none', 'discount' => null],
            'winfield-blue-soft-pack-20s' => ['qty' => 140, 'min' => 30, 'price' => '46.50', 'discount_type' => 'none', 'discount' => null],
            'horizon-red-soft-pack-25s' => ['qty' => 80, 'min' => 20, 'price' => '52.90', 'discount_type' => 'fixed', 'discount' => '3.00'],
            'peter-jackson-menthol-20s' => ['qty' => 60, 'min' => 15, 'price' => '47.20', 'discount_type' => 'none', 'discount' => null],
            'benson-hedges-smooth-soft-pack-20s' => ['qty' => 55, 'min' => 12, 'price' => '49.90', 'discount_type' => 'none', 'discount' => null],
            'dunhill-fine-cut-soft-pack-20s' => ['qty' => 40, 'min' => 10, 'price' => '51.50', 'discount_type' => 'none', 'discount' => null],
            'rothmans-blue-soft-pack-20s' => ['qty' => 70, 'min' => 16, 'price' => '45.50', 'discount_type' => 'fixed', 'discount' => '2.00'],
            'winfield-red-soft-pack-20s' => ['qty' => 90, 'min' => 20, 'price' => '46.80', 'discount_type' => 'none', 'discount' => null],
            'escort-red-soft-pack-25s' => ['qty' => 45, 'min' => 12, 'price' => '50.90', 'discount_type' => 'none', 'discount' => null],
            'iqos-iluma-starter-kit' => ['qty' => 18, 'min' => 4, 'price' => '89.00', 'discount_type' => 'percent', 'discount' => '10'],
            'terea-tobacco-sticks-20' => ['qty' => 90, 'min' => 20, 'price' => '28.00', 'discount_type' => 'none', 'discount' => null],
            'terea-menthol-sticks-20' => ['qty' => 50, 'min' => 12, 'price' => '28.00', 'discount_type' => 'none', 'discount' => null],
            'drum-original-hand-rolling-tobacco-50g' => ['qty' => 35, 'min' => 8, 'price' => '42.00', 'discount_type' => 'none', 'discount' => null],
            'vuse-go-5000-disposable' => ['qty' => 64, 'min' => 16, 'price' => '39.95', 'discount_type' => 'fixed', 'discount' => '5.00'],
            'puffmemate-pulse-5000' => ['qty' => 48, 'min' => 12, 'price' => '34.99', 'discount_type' => 'fixed', 'discount' => '5.00'],
            'puffmemate-mini-bar-1200' => ['qty' => 72, 'min' => 18, 'price' => '14.99', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-core-kit' => ['qty' => 24, 'min' => 6, 'price' => '39.99', 'discount_type' => 'fixed', 'discount' => '5.00'],
            'puffmemate-air-pro' => ['qty' => 18, 'min' => 4, 'price' => '44.99', 'discount_type' => 'none', 'discount' => null],
            'iget-bar-plus-disposable' => ['qty' => 40, 'min' => 10, 'price' => '32.95', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-cloud-bar-6000' => ['qty' => 36, 'min' => 10, 'price' => '36.99', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-soft-touch-3500' => ['qty' => 30, 'min' => 8, 'price' => '29.99', 'discount_type' => 'none', 'discount' => null],
            'vuse-epod-2-device-kit' => ['qty' => 16, 'min' => 4, 'price' => '29.99', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-max-mesh-8000' => ['qty' => 28, 'min' => 8, 'price' => '41.99', 'discount_type' => 'fixed', 'discount' => '4.00'],
            'puffmemate-nano-pod-kit' => ['qty' => 20, 'min' => 5, 'price' => '34.99', 'discount_type' => 'none', 'discount' => null],
        ]);

        // Melbourne Collins: strong cigarette + heated tobacco focus, leaner vape range.
        $this->seedBranch($melbourne, $inventory, $pricing, [
            'marlboro-gold-soft-pack-20s' => ['qty' => 95, 'min' => 20, 'price' => '49.50', 'discount_type' => 'none', 'discount' => null],
            'winfield-blue-soft-pack-20s' => ['qty' => 110, 'min' => 25, 'price' => '47.00', 'discount_type' => 'none', 'discount' => null],
            'horizon-red-soft-pack-25s' => ['qty' => 40, 'min' => 15, 'price' => '53.40', 'discount_type' => 'none', 'discount' => null],
            'benson-hedges-smooth-soft-pack-20s' => ['qty' => 36, 'min' => 10, 'price' => '50.50', 'discount_type' => 'none', 'discount' => null],
            'rothmans-blue-soft-pack-20s' => ['qty' => 42, 'min' => 12, 'price' => '45.90', 'discount_type' => 'none', 'discount' => null],
            'winfield-red-soft-pack-20s' => ['qty' => 60, 'min' => 15, 'price' => '47.20', 'discount_type' => 'none', 'discount' => null],
            'iqos-iluma-starter-kit' => ['qty' => 12, 'min' => 3, 'price' => '92.00', 'discount_type' => 'none', 'discount' => null],
            'terea-tobacco-sticks-20' => ['qty' => 70, 'min' => 15, 'price' => '28.50', 'discount_type' => 'fixed', 'discount' => '2.00'],
            'terea-menthol-sticks-20' => ['qty' => 32, 'min' => 8, 'price' => '28.50', 'discount_type' => 'none', 'discount' => null],
            'drum-original-hand-rolling-tobacco-50g' => ['qty' => 22, 'min' => 6, 'price' => '42.50', 'discount_type' => 'none', 'discount' => null],
            'vuse-go-5000-disposable' => ['qty' => 30, 'min' => 10, 'price' => '40.95', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-pulse-5000' => ['qty' => 20, 'min' => 10, 'price' => '35.99', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-mini-bar-1200' => ['qty' => 8, 'min' => 12, 'price' => '15.49', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-core-kit' => ['qty' => 12, 'min' => 5, 'price' => '41.99', 'discount_type' => 'fixed', 'discount' => '4.00'],
            'iget-bar-plus-disposable' => ['qty' => 18, 'min' => 6, 'price' => '33.50', 'discount_type' => 'none', 'discount' => null],
            'vuse-epod-2-device-kit' => ['qty' => 10, 'min' => 3, 'price' => '30.50', 'discount_type' => 'none', 'discount' => null],
        ]);

        // Brisbane Queen St: compact store — vapes heavy, limited cigarette SKUs.
        $this->seedBranch($brisbane, $inventory, $pricing, [
            'winfield-blue-soft-pack-20s' => ['qty' => 48, 'min' => 16, 'price' => '46.80', 'discount_type' => 'none', 'discount' => null],
            'peter-jackson-menthol-20s' => ['qty' => 22, 'min' => 10, 'price' => '47.50', 'discount_type' => 'none', 'discount' => null],
            'rothmans-blue-soft-pack-20s' => ['qty' => 28, 'min' => 10, 'price' => '45.80', 'discount_type' => 'none', 'discount' => null],
            'terea-tobacco-sticks-20' => ['qty' => 24, 'min' => 8, 'price' => '28.00', 'discount_type' => 'none', 'discount' => null],
            'vuse-go-5000-disposable' => ['qty' => 42, 'min' => 12, 'price' => '39.50', 'discount_type' => 'percent', 'discount' => '8'],
            'puffmemate-pulse-5000' => ['qty' => 36, 'min' => 10, 'price' => '34.50', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-mini-bar-1200' => ['qty' => 50, 'min' => 15, 'price' => '14.50', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-air-pro' => ['qty' => 10, 'min' => 4, 'price' => '43.99', 'discount_type' => 'none', 'discount' => null],
            'iget-bar-plus-disposable' => ['qty' => 32, 'min' => 10, 'price' => '32.50', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-cloud-bar-6000' => ['qty' => 26, 'min' => 8, 'price' => '36.50', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-soft-touch-3500' => ['qty' => 22, 'min' => 6, 'price' => '29.50', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-max-mesh-8000' => ['qty' => 18, 'min' => 6, 'price' => '41.50', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-nano-pod-kit' => ['qty' => 12, 'min' => 4, 'price' => '34.50', 'discount_type' => 'none', 'discount' => null],
        ]);

        $this->seedVariantStock($sydney, $inventory, 'puffmemate-pulse-5000', [
            'POM-PULSE-5000-MANGO' => 16,
            'POM-PULSE-5000-BRAZZ' => 14,
            'POM-PULSE-5000-WM' => 10,
            'POM-PULSE-5000-TOB' => 8,
        ]);

        $this->seedVariantStock($sydney, $inventory, 'vuse-go-5000-disposable', [
            'VAPE-VUSE-GO-BERRY' => 18,
            'VAPE-VUSE-GO-MINT' => 16,
            'VAPE-VUSE-GO-TOB' => 12,
            'VAPE-VUSE-GO-WM' => 14,
        ]);

        $this->seedVariantStock($sydney, $inventory, 'marlboro-gold-soft-pack-20s', [
            'CIG-MB-GOLD-20-PK' => 100,
            'CIG-MB-GOLD-20-CTN' => 12,
        ]);

        $this->seedVariantPrices($sydney, $pricing, 'marlboro-gold-soft-pack-20s', [
            'CIG-MB-GOLD-20-PK' => '48.95',
            'CIG-MB-GOLD-20-CTN' => '475.00',
        ]);

        $this->seedVariantStock($melbourne, $inventory, 'terea-tobacco-sticks-20', [
            'HTP-TEREA-AMBER' => 28,
            'HTP-TEREA-SIENNA' => 24,
            'HTP-TEREA-TURQ' => 18,
        ]);

        $regionalSlugs = [
            BranchSeeder::PERTH_HAY,
            BranchSeeder::ADELAIDE_RUNDLE,
            BranchSeeder::GOLD_COAST_SURFERS,
            BranchSeeder::CANBERRA_CIVIC,
            BranchSeeder::NEWCASTLE_HUNTER,
            BranchSeeder::PARRAMATTA_CHURCH,
        ];

        foreach ($regionalSlugs as $slug) {
            $branch = Branch::query()->where('slug', $slug)->firstOrFail();
            $this->seedBranch($branch, $inventory, $pricing, $this->regionalAssortment());
        }
    }

    /**
     * Mid-size store mix shared by regional branches (hand-tuned, no Faker).
     *
     * @return array<string, array{qty: int, min: int, price: string, discount_type: string, discount: string|null}>
     */
    private function regionalAssortment(): array
    {
        return [
            'marlboro-gold-soft-pack-20s' => ['qty' => 55, 'min' => 16, 'price' => '48.50', 'discount_type' => 'none', 'discount' => null],
            'winfield-blue-soft-pack-20s' => ['qty' => 70, 'min' => 18, 'price' => '46.80', 'discount_type' => 'none', 'discount' => null],
            'winfield-red-soft-pack-20s' => ['qty' => 40, 'min' => 12, 'price' => '46.80', 'discount_type' => 'none', 'discount' => null],
            'benson-hedges-smooth-soft-pack-20s' => ['qty' => 28, 'min' => 10, 'price' => '49.90', 'discount_type' => 'none', 'discount' => null],
            'rothmans-blue-soft-pack-20s' => ['qty' => 32, 'min' => 10, 'price' => '45.50', 'discount_type' => 'fixed', 'discount' => '2.00'],
            'terea-tobacco-sticks-20' => ['qty' => 24, 'min' => 8, 'price' => '28.00', 'discount_type' => 'none', 'discount' => null],
            'drum-original-hand-rolling-tobacco-50g' => ['qty' => 14, 'min' => 4, 'price' => '41.50', 'discount_type' => 'none', 'discount' => null],
            'vuse-go-5000-disposable' => ['qty' => 36, 'min' => 10, 'price' => '39.95', 'discount_type' => 'none', 'discount' => null],
            'iget-bar-plus-disposable' => ['qty' => 30, 'min' => 8, 'price' => '32.95', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-pulse-5000' => ['qty' => 28, 'min' => 8, 'price' => '34.99', 'discount_type' => 'fixed', 'discount' => '3.00'],
            'puffmemate-cloud-bar-6000' => ['qty' => 24, 'min' => 8, 'price' => '36.99', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-mini-bar-1200' => ['qty' => 40, 'min' => 12, 'price' => '14.99', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-core-kit' => ['qty' => 12, 'min' => 4, 'price' => '39.99', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-soft-touch-3500' => ['qty' => 18, 'min' => 6, 'price' => '29.99', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-max-mesh-8000' => ['qty' => 16, 'min' => 5, 'price' => '41.99', 'discount_type' => 'none', 'discount' => null],
        ];
    }

    /**
     * @param  array<string, array{
     *     qty: int,
     *     min: int,
     *     price: string,
     *     discount_type: string,
     *     discount: string|null
     * }>  $lines
     */
    private function seedBranch(
        Branch $branch,
        InventoryService $inventory,
        BranchPricingService $pricing,
        array $lines,
    ): void {
        foreach ($lines as $slug => $line) {
            $product = Product::query()->where('slug', $slug)->firstOrFail();

            $row = $inventory->firstOrCreate($branch, $product);
            $selling = (float) $line['price'];
            $buying = number_format(round($selling * 0.62, 2), 2, '.', '');
            $regular = number_format(round($selling * 1.15, 2), 2, '.', '');

            if ($row->quantity === 0 && $line['qty'] > 0) {
                $inventory->adjust(
                    $row,
                    InventoryTransactionType::StockIn,
                    $line['qty'],
                    unitCost: $buying,
                );
            }

            $pricing->upsert($branch, $product, [
                'buying_price' => $buying,
                'regular_price' => $regular,
                'selling_price' => $line['price'],
                'discount_type' => $line['discount_type'],
                'discount_price' => $line['discount'],
                'status' => true,
            ]);
        }
    }

    /**
     * @param  array<string, int>  $variantSkus
     */
    private function seedVariantStock(
        Branch $branch,
        InventoryService $inventory,
        string $productSlug,
        array $variantSkus,
    ): void {
        $product = Product::query()->where('slug', $productSlug)->firstOrFail();

        foreach ($variantSkus as $sku => $qty) {
            $variant = ProductVariant::query()->where('sku', $sku)->firstOrFail();

            $row = $inventory->firstOrCreate($branch, $product, $variant);

            if ($row->quantity === 0 && $qty > 0) {
                $inventory->adjust(
                    $row,
                    InventoryTransactionType::StockIn,
                    $qty,
                    unitCost: '10.00',
                );
            }
        }
    }

    /**
     * @param  array<string, string>  $variantPrices
     */
    private function seedVariantPrices(
        Branch $branch,
        BranchPricingService $pricing,
        string $productSlug,
        array $variantPrices,
    ): void {
        $product = Product::query()->where('slug', $productSlug)->firstOrFail();

        foreach ($variantPrices as $sku => $price) {
            $variant = ProductVariant::query()->where('sku', $sku)->firstOrFail();

            $pricing->upsert($branch, $product, [
                'buying_price' => number_format(round(((float) $price) * 0.62, 2), 2, '.', ''),
                'regular_price' => number_format(round(((float) $price) * 1.15, 2), 2, '.', ''),
                'selling_price' => $price,
                'discount_type' => 'none',
                'discount_price' => null,
                'status' => true,
            ], $variant);
        }
    }
}
