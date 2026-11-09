<?php

namespace Database\Seeders;

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Inventory\BranchPricingService;
use App\Services\Inventory\InventoryService;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;

class BranchInventorySeeder extends Seeder
{
    use ReportsSeedProgress;

    /**
     * Seed cigarettes / tobacco / vapes / pouches / accessories stock per branch (no Faker).
     * Depends on BranchSeeder + CategorySeeder + ProductSeeder.
     */
    public function run(): void
    {
        $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
        $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

        $inventory = app(InventoryService::class);
        $pricing = app(BranchPricingService::class);

        // Sydney CBD flagship: the full owner range (cigarettes, tobacco, vapes, pouches) with demo stock.
        $this->seedBranch($sydney, $inventory, $pricing, [
            'marlboro-gold-soft-pack-20s' => ['qty' => 120, 'min' => 24, 'price' => '140.00', 'discount_type' => 'none', 'discount' => null],
            'double-happiness-soft-pack' => ['qty' => 50, 'min' => 10, 'price' => '140.00', 'discount_type' => 'none', 'discount' => null],
            'manchester-special-edition' => ['qty' => 50, 'min' => 10, 'price' => '150.00', 'discount_type' => 'none', 'discount' => null],
            'manchester-light' => ['qty' => 50, 'min' => 10, 'price' => '150.00', 'discount_type' => 'none', 'discount' => null],
            'manchester-royal-red' => ['qty' => 50, 'min' => 10, 'price' => '140.00', 'discount_type' => 'none', 'discount' => null],
            'manchester-double-drive' => ['qty' => 50, 'min' => 10, 'price' => '170.00', 'discount_type' => 'none', 'discount' => null],
            'manchester-sapphire-blue' => ['qty' => 50, 'min' => 10, 'price' => '160.00', 'discount_type' => 'none', 'discount' => null],
            'manchester-red' => ['qty' => 50, 'min' => 10, 'price' => '140.00', 'discount_type' => 'none', 'discount' => null],
            'marlboro-red' => ['qty' => 50, 'min' => 10, 'price' => '140.00', 'discount_type' => 'none', 'discount' => null],
            'marlboro-ice-blast' => ['qty' => 50, 'min' => 10, 'price' => '160.00', 'discount_type' => 'none', 'discount' => null],
            'benson-hedges-special-filter' => ['qty' => 50, 'min' => 10, 'price' => '150.00', 'discount_type' => 'none', 'discount' => null],
            'dunhill-blue' => ['qty' => 50, 'min' => 10, 'price' => '150.00', 'discount_type' => 'none', 'discount' => null],
            'mac' => ['qty' => 50, 'min' => 10, 'price' => '140.00', 'discount_type' => 'none', 'discount' => null],
            'kent-switch' => ['qty' => 50, 'min' => 10, 'price' => '150.00', 'discount_type' => 'none', 'discount' => null],
            'eights-8mg' => ['qty' => 50, 'min' => 10, 'price' => '155.00', 'discount_type' => 'none', 'discount' => null],
            'tobacco-100g' => ['qty' => 50, 'min' => 10, 'price' => '50.00', 'discount_type' => 'none', 'discount' => null],
            'royal-bourbon-premium-ryo-40g' => ['qty' => 50, 'min' => 10, 'price' => '40.00', 'discount_type' => 'none', 'discount' => null],
            'violin-premium-ryo-40g' => ['qty' => 50, 'min' => 10, 'price' => '25.00', 'discount_type' => 'none', 'discount' => null],
            'alibarbar-ingot-9000-puffs' => ['qty' => 50, 'min' => 10, 'price' => '60.00', 'discount_type' => 'none', 'discount' => null],
            'kuz-nova-16000' => ['qty' => 50, 'min' => 10, 'price' => '65.00', 'discount_type' => 'none', 'discount' => null],
            'kuz-pearl-30000-puffs' => ['qty' => 50, 'min' => 10, 'price' => '75.00', 'discount_type' => 'none', 'discount' => null],
            'iget-one-12000' => ['qty' => 50, 'min' => 10, 'price' => '75.00', 'discount_type' => 'none', 'discount' => null],
            'iget-bar-pro-10k' => ['qty' => 50, 'min' => 10, 'price' => '60.00', 'discount_type' => 'none', 'discount' => null],
            'zyn-nic-chewable-flavored' => ['qty' => 50, 'min' => 10, 'price' => '25.00', 'discount_type' => 'none', 'discount' => null],
            'zyn-or-velo' => ['qty' => 50, 'min' => 10, 'price' => '20.00', 'discount_type' => 'none', 'discount' => null],
        ]);

        // Melbourne Collins: Marlboro Gold only.
        $this->seedBranch($melbourne, $inventory, $pricing, [
            'marlboro-gold-soft-pack-20s' => ['qty' => 95, 'min' => 20, 'price' => '49.50', 'discount_type' => 'none', 'discount' => null],
        ]);

        $this->seedVariantStock($sydney, $inventory, 'marlboro-gold-soft-pack-20s', [
            'CIG-MB-GOLD-20-PK' => 100,
            'CIG-MB-GOLD-20-CTN' => 12,
        ]);

        $this->seedVariantPrices($sydney, $pricing, 'marlboro-gold-soft-pack-20s', [
            'CIG-MB-GOLD-20-PK' => '140.00',
            'CIG-MB-GOLD-20-CTN' => '475.00',
        ]);

        $regionalSlugs = [
            BranchSeeder::PERTH_HAY,
            BranchSeeder::IPSWICH_BRISBANE_ST,
        ];

        foreach ($regionalSlugs as $slug) {
            $branch = Branch::query()->where('slug', $slug)->firstOrFail();
            $this->seedBranch($branch, $inventory, $pricing, $this->regionalAssortment());
        }

        $this->runQueuedSeedSteps(
            'Core stock',
            fn (int $total): string => sprintf('%d hand-tuned stock and price lines across the 5 core branches.', $total),
        );
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
            $this->queueSeedStep(fn () => $this->seedLine($branch, $inventory, $pricing, $slug, $line));
        }
    }

    /**
     * @param  array{qty: int, min: int, price: string, discount_type: string, discount: string|null}  $line
     */
    private function seedLine(
        Branch $branch,
        InventoryService $inventory,
        BranchPricingService $pricing,
        string $slug,
        array $line,
    ): void {
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

    /**
     * @param  array<string, int>  $variantSkus
     */
    private function seedVariantStock(
        Branch $branch,
        InventoryService $inventory,
        string $productSlug,
        array $variantSkus,
    ): void {
        foreach ($variantSkus as $sku => $qty) {
            $this->queueSeedStep(function () use ($branch, $inventory, $productSlug, $sku, $qty): void {
                $product = Product::query()->where('slug', $productSlug)->firstOrFail();
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
            });
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
        foreach ($variantPrices as $sku => $price) {
            $this->queueSeedStep(function () use ($branch, $pricing, $productSlug, $sku, $price): void {
                $product = Product::query()->where('slug', $productSlug)->firstOrFail();
                $variant = ProductVariant::query()->where('sku', $sku)->firstOrFail();

                $pricing->upsert($branch, $product, [
                    'buying_price' => number_format(round(((float) $price) * 0.62, 2), 2, '.', ''),
                    'regular_price' => number_format(round(((float) $price) * 1.15, 2), 2, '.', ''),
                    'selling_price' => $price,
                    'discount_type' => 'none',
                    'discount_price' => null,
                    'status' => true,
                ], $variant);
            });
        }
    }
}
