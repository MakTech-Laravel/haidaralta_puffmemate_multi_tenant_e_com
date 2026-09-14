<?php

namespace Database\Seeders;

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Inventory\BranchPricingService;
use App\Services\Inventory\BranchProductAvailabilityService;
use App\Services\Inventory\InventoryService;
use Illuminate\Database\Seeder;

class BranchInventorySeeder extends Seeder
{
    /**
     * Enable catalog products per branch with realistic stock and prices (no Faker).
     * Depends on BranchSeeder + CatalogSeeder.
     */
    public function run(): void
    {
        $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
        $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
        $brisbane = Branch::query()->where('slug', BranchSeeder::BRISBANE_QUEEN)->firstOrFail();

        $availability = app(BranchProductAvailabilityService::class);
        $inventory = app(InventoryService::class);
        $pricing = app(BranchPricingService::class);

        // Sydney CBD flagship: full assortment, healthy stock, some promos.
        $this->seedBranch($sydney, $availability, $inventory, $pricing, [
            'marlboro-gold-soft-pack-20s' => ['qty' => 120, 'min' => 24, 'price' => '48.95', 'discount_type' => 'none', 'discount' => null],
            'winfield-blue-soft-pack-20s' => ['qty' => 140, 'min' => 30, 'price' => '46.50', 'discount_type' => 'none', 'discount' => null],
            'horizon-red-soft-pack-25s' => ['qty' => 80, 'min' => 20, 'price' => '52.90', 'discount_type' => 'fixed', 'discount' => '49.90'],
            'peter-jackson-menthol-20s' => ['qty' => 60, 'min' => 15, 'price' => '47.20', 'discount_type' => 'none', 'discount' => null],
            'iqos-iluma-starter-kit' => ['qty' => 18, 'min' => 4, 'price' => '89.00', 'discount_type' => 'percent', 'discount' => '10'],
            'terea-tobacco-sticks-20' => ['qty' => 90, 'min' => 20, 'price' => '28.00', 'discount_type' => 'none', 'discount' => null],
            'vuse-go-5000-disposable' => ['qty' => 64, 'min' => 16, 'price' => '39.95', 'discount_type' => 'fixed', 'discount' => '34.95'],
            'puffmemate-pulse-5000' => ['qty' => 48, 'min' => 12, 'price' => '34.99', 'discount_type' => 'fixed', 'discount' => '29.99'],
            'puffmemate-mini-bar-1200' => ['qty' => 72, 'min' => 18, 'price' => '14.99', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-core-kit' => ['qty' => 24, 'min' => 6, 'price' => '39.99', 'discount_type' => 'fixed', 'discount' => '34.99'],
            'puffmemate-air-pro' => ['qty' => 18, 'min' => 4, 'price' => '44.99', 'discount_type' => 'none', 'discount' => null],
            'harbor-fruit-blend-60ml' => ['qty' => 36, 'min' => 8, 'price' => '24.99', 'discount_type' => 'none', 'discount' => null],
            'citrus-surge-salt-30ml' => ['qty' => 40, 'min' => 10, 'price' => '19.99', 'discount_type' => 'fixed', 'discount' => '17.99'],
            'vanilla-custard-reserve-60ml' => ['qty' => 28, 'min' => 6, 'price' => '24.99', 'discount_type' => 'none', 'discount' => null],
            'classic-leaf-tobacco-60ml' => ['qty' => 32, 'min' => 8, 'price' => '22.99', 'discount_type' => 'none', 'discount' => null],
            'nordic-mint-pouches' => ['qty' => 55, 'min' => 12, 'price' => '18.50', 'discount_type' => 'none', 'discount' => null],
            'citrus-chill-pouches' => ['qty' => 40, 'min' => 10, 'price' => '18.50', 'discount_type' => 'percent', 'discount' => '5'],
            'core-closed-pods-2-pack' => ['qty' => 60, 'min' => 15, 'price' => '12.99', 'discount_type' => 'none', 'discount' => null],
            'air-pro-mesh-coils-5-pack' => ['qty' => 45, 'min' => 10, 'price' => '16.99', 'discount_type' => 'none', 'discount' => null],
            'clipper-classic-lighter' => ['qty' => 100, 'min' => 20, 'price' => '3.50', 'discount_type' => 'none', 'discount' => null],
            'raw-classic-rolling-papers-ks' => ['qty' => 80, 'min' => 20, 'price' => '2.80', 'discount_type' => 'none', 'discount' => null],
            'usb-c-charge-cable-1m' => ['qty' => 50, 'min' => 10, 'price' => '9.99', 'discount_type' => 'none', 'discount' => null],
            'silicone-device-sleeve' => ['qty' => 35, 'min' => 8, 'price' => '7.99', 'discount_type' => 'none', 'discount' => null],
            'travel-hard-case' => ['qty' => 22, 'min' => 5, 'price' => '19.99', 'discount_type' => 'fixed', 'discount' => '16.99'],
            'lanyard-clip' => ['qty' => 40, 'min' => 10, 'price' => '4.99', 'discount_type' => 'none', 'discount' => null],
        ]);

        // Melbourne Collins: strong cigarette + heated tobacco focus, leaner vape range.
        $this->seedBranch($melbourne, $availability, $inventory, $pricing, [
            'marlboro-gold-soft-pack-20s' => ['qty' => 95, 'min' => 20, 'price' => '49.50', 'discount_type' => 'none', 'discount' => null],
            'winfield-blue-soft-pack-20s' => ['qty' => 110, 'min' => 25, 'price' => '47.00', 'discount_type' => 'none', 'discount' => null],
            'horizon-red-soft-pack-25s' => ['qty' => 40, 'min' => 15, 'price' => '53.40', 'discount_type' => 'none', 'discount' => null],
            'iqos-iluma-starter-kit' => ['qty' => 12, 'min' => 3, 'price' => '92.00', 'discount_type' => 'none', 'discount' => null],
            'terea-tobacco-sticks-20' => ['qty' => 70, 'min' => 15, 'price' => '28.50', 'discount_type' => 'fixed', 'discount' => '26.50'],
            'vuse-go-5000-disposable' => ['qty' => 30, 'min' => 10, 'price' => '40.95', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-pulse-5000' => ['qty' => 20, 'min' => 10, 'price' => '35.99', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-mini-bar-1200' => ['qty' => 8, 'min' => 12, 'price' => '15.49', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-core-kit' => ['qty' => 12, 'min' => 5, 'price' => '41.99', 'discount_type' => 'fixed', 'discount' => '37.99'],
            'harbor-fruit-blend-60ml' => ['qty' => 15, 'min' => 6, 'price' => '25.49', 'discount_type' => 'none', 'discount' => null],
            'citrus-surge-salt-30ml' => ['qty' => 4, 'min' => 8, 'price' => '20.49', 'discount_type' => 'none', 'discount' => null],
            'nordic-mint-pouches' => ['qty' => 28, 'min' => 8, 'price' => '18.95', 'discount_type' => 'none', 'discount' => null],
            'core-closed-pods-2-pack' => ['qty' => 22, 'min' => 10, 'price' => '13.49', 'discount_type' => 'none', 'discount' => null],
            'clipper-classic-lighter' => ['qty' => 70, 'min' => 15, 'price' => '3.50', 'discount_type' => 'none', 'discount' => null],
            'raw-classic-rolling-papers-ks' => ['qty' => 55, 'min' => 12, 'price' => '2.80', 'discount_type' => 'none', 'discount' => null],
            'usb-c-charge-cable-1m' => ['qty' => 18, 'min' => 6, 'price' => '9.99', 'discount_type' => 'none', 'discount' => null],
            'lanyard-clip' => ['qty' => 3, 'min' => 8, 'price' => '4.99', 'discount_type' => 'none', 'discount' => null],
        ]);

        // Brisbane Queen St: compact store — vapes + pouches heavy, limited cigarette SKUs.
        $this->seedBranch($brisbane, $availability, $inventory, $pricing, [
            'winfield-blue-soft-pack-20s' => ['qty' => 48, 'min' => 16, 'price' => '46.80', 'discount_type' => 'none', 'discount' => null],
            'peter-jackson-menthol-20s' => ['qty' => 22, 'min' => 10, 'price' => '47.50', 'discount_type' => 'none', 'discount' => null],
            'vuse-go-5000-disposable' => ['qty' => 42, 'min' => 12, 'price' => '39.50', 'discount_type' => 'percent', 'discount' => '8'],
            'puffmemate-pulse-5000' => ['qty' => 36, 'min' => 10, 'price' => '34.50', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-mini-bar-1200' => ['qty' => 50, 'min' => 15, 'price' => '14.50', 'discount_type' => 'none', 'discount' => null],
            'puffmemate-air-pro' => ['qty' => 10, 'min' => 4, 'price' => '43.99', 'discount_type' => 'none', 'discount' => null],
            'citrus-surge-salt-30ml' => ['qty' => 24, 'min' => 8, 'price' => '19.50', 'discount_type' => 'none', 'discount' => null],
            'classic-leaf-tobacco-60ml' => ['qty' => 18, 'min' => 6, 'price' => '22.50', 'discount_type' => 'none', 'discount' => null],
            'nordic-mint-pouches' => ['qty' => 44, 'min' => 12, 'price' => '17.95', 'discount_type' => 'fixed', 'discount' => '15.95'],
            'citrus-chill-pouches' => ['qty' => 36, 'min' => 10, 'price' => '17.95', 'discount_type' => 'none', 'discount' => null],
            'air-pro-mesh-coils-5-pack' => ['qty' => 20, 'min' => 6, 'price' => '16.50', 'discount_type' => 'none', 'discount' => null],
            'clipper-classic-lighter' => ['qty' => 60, 'min' => 15, 'price' => '3.20', 'discount_type' => 'none', 'discount' => null],
            'usb-c-charge-cable-1m' => ['qty' => 25, 'min' => 8, 'price' => '9.50', 'discount_type' => 'none', 'discount' => null],
            'travel-hard-case' => ['qty' => 9, 'min' => 4, 'price' => '18.99', 'discount_type' => 'none', 'discount' => null],
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
        ], minimumStock: 10);

        $this->seedVariantPrices($sydney, $pricing, 'marlboro-gold-soft-pack-20s', [
            'CIG-MB-GOLD-20-PK' => '48.95',
            'CIG-MB-GOLD-20-CTN' => '475.00',
        ]);

        $this->seedVariantStock($melbourne, $inventory, 'terea-tobacco-sticks-20', [
            'HTP-TEREA-AMBER' => 28,
            'HTP-TEREA-SIENNA' => 24,
            'HTP-TEREA-TURQ' => 18,
        ], minimumStock: 6);

        $this->seedVariantStock($brisbane, $inventory, 'nordic-mint-pouches', [
            'POUCH-NORDIC-6' => 16,
            'POUCH-NORDIC-10' => 14,
            'POUCH-NORDIC-16' => 10,
        ], minimumStock: 4);
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
        BranchProductAvailabilityService $availability,
        InventoryService $inventory,
        BranchPricingService $pricing,
        array $lines,
    ): void {
        foreach ($lines as $slug => $line) {
            $product = Product::query()->where('slug', $slug)->firstOrFail();

            $availability->setEnabled($branch, $product, true);

            $row = $inventory->firstOrCreate(
                $branch,
                $product,
                null,
                ['minimum_stock' => $line['min']],
            );

            if ($row->quantity === 0 && $line['qty'] > 0) {
                $inventory->adjust(
                    $row,
                    InventoryTransactionType::StockIn,
                    $line['qty'],
                );
            } elseif ($row->minimum_stock !== $line['min']) {
                $inventory->setMinimumStock($row, $line['min']);
            }

            $pricing->upsert($branch, $product, [
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
        int $minimumStock = 4,
    ): void {
        $product = Product::query()->where('slug', $productSlug)->firstOrFail();

        foreach ($variantSkus as $sku => $qty) {
            $variant = ProductVariant::query()->where('sku', $sku)->firstOrFail();

            $row = $inventory->firstOrCreate(
                $branch,
                $product,
                $variant,
                ['minimum_stock' => $minimumStock],
            );

            if ($row->quantity === 0 && $qty > 0) {
                $inventory->adjust(
                    $row,
                    InventoryTransactionType::StockIn,
                    $qty,
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
                'selling_price' => $price,
                'discount_type' => 'none',
                'discount_price' => null,
                'status' => true,
            ], $variant);
        }
    }
}
