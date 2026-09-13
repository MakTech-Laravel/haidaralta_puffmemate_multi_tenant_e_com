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
        $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();
        $uptown = Branch::query()->where('slug', 'uptown')->firstOrFail();

        $availability = app(BranchProductAvailabilityService::class);
        $inventory = app(InventoryService::class);
        $pricing = app(BranchPricingService::class);

        // Downtown: full assortment, healthy stock.
        $this->seedBranch($downtown, $availability, $inventory, $pricing, [
            'puffonmate-pulse-5000' => ['qty' => 48, 'min' => 12, 'price' => '34.99', 'discount' => '29.99'],
            'puffonmate-mini-bar-1200' => ['qty' => 72, 'min' => 18, 'price' => '14.99', 'discount' => null],
            'puffonmate-core-kit' => ['qty' => 24, 'min' => 6, 'price' => '39.99', 'discount' => '34.99'],
            'puffonmate-air-pro' => ['qty' => 18, 'min' => 4, 'price' => '44.99', 'discount' => null],
            'harbor-fruit-blend-60ml' => ['qty' => 36, 'min' => 8, 'price' => '24.99', 'discount' => null],
            'citrus-surge-salt-30ml' => ['qty' => 40, 'min' => 10, 'price' => '19.99', 'discount' => '17.99'],
            'vanilla-custard-reserve-60ml' => ['qty' => 28, 'min' => 6, 'price' => '24.99', 'discount' => null],
            'core-closed-pods-2-pack' => ['qty' => 60, 'min' => 15, 'price' => '12.99', 'discount' => null],
            'air-pro-mesh-coils-5-pack' => ['qty' => 45, 'min' => 10, 'price' => '16.99', 'discount' => null],
            'usb-c-charge-cable-1m' => ['qty' => 50, 'min' => 10, 'price' => '9.99', 'discount' => null],
            'silicone-device-sleeve' => ['qty' => 35, 'min' => 8, 'price' => '7.99', 'discount' => null],
            'lanyard-clip' => ['qty' => 40, 'min' => 10, 'price' => '4.99', 'discount' => null],
        ]);

        // Uptown: smaller assortment; some lines intentionally near low-stock.
        $this->seedBranch($uptown, $availability, $inventory, $pricing, [
            'puffonmate-pulse-5000' => ['qty' => 20, 'min' => 10, 'price' => '35.99', 'discount' => null],
            'puffonmate-mini-bar-1200' => ['qty' => 8, 'min' => 12, 'price' => '15.49', 'discount' => null],
            'puffonmate-core-kit' => ['qty' => 12, 'min' => 5, 'price' => '41.99', 'discount' => '37.99'],
            'harbor-fruit-blend-60ml' => ['qty' => 15, 'min' => 6, 'price' => '25.49', 'discount' => null],
            'citrus-surge-salt-30ml' => ['qty' => 4, 'min' => 8, 'price' => '20.49', 'discount' => null],
            'core-closed-pods-2-pack' => ['qty' => 22, 'min' => 10, 'price' => '13.49', 'discount' => null],
            'usb-c-charge-cable-1m' => ['qty' => 18, 'min' => 6, 'price' => '9.99', 'discount' => null],
            'lanyard-clip' => ['qty' => 3, 'min' => 8, 'price' => '4.99', 'discount' => null],
        ]);

        // Variant-level stock for Pulse flavours at Downtown (product-level row also exists).
        $this->seedVariantStock(
            $downtown,
            $inventory,
            'puffonmate-pulse-5000',
            [
                'POM-PULSE-5000-MANGO' => 16,
                'POM-PULSE-5000-BRAZZ' => 14,
                'POM-PULSE-5000-WM' => 10,
                'POM-PULSE-5000-TOB' => 8,
            ],
        );
    }

    /**
     * @param  array<string, array{qty: int, min: int, price: string, discount: string|null}>  $lines
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
                'discount_type' => $line['discount'] !== null ? 'fixed' : 'none',
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

            $row = $inventory->firstOrCreate(
                $branch,
                $product,
                $variant,
                ['minimum_stock' => 4],
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
}
