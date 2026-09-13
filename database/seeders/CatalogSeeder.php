<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Seed a realistic global product catalog (no Faker).
     * Products are created globally and remain disabled at every branch until BranchInventorySeeder runs.
     */
    public function run(): void
    {
        $creator = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first();

        $disposables = $this->category([
            'name' => 'Disposables',
            'slug' => 'disposables',
            'parent_id' => null,
            'color_code' => ['mode' => 'solid', 'color' => '#E8F0FE'],
        ]);

        $podSystems = $this->category([
            'name' => 'Pod Systems',
            'slug' => 'pod-systems',
            'parent_id' => null,
            'color_code' => [
                'mode' => 'gradient',
                'from' => '#F3E8FF',
                'to' => '#FFFFFF',
                'angle' => 135,
            ],
        ]);

        $eLiquids = $this->category([
            'name' => 'E-Liquids',
            'slug' => 'e-liquids',
            'parent_id' => null,
        ]);

        $coilsPods = $this->category([
            'name' => 'Coils & Pods',
            'slug' => 'coils-pods',
            'parent_id' => null,
        ]);

        $accessories = $this->category([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'parent_id' => null,
        ]);

        $fruitLiquids = $this->category([
            'name' => 'Fruit',
            'slug' => 'e-liquids-fruit',
            'parent_id' => $eLiquids->id,
        ]);

        $dessertLiquids = $this->category([
            'name' => 'Dessert',
            'slug' => 'e-liquids-dessert',
            'parent_id' => $eLiquids->id,
        ]);

        $this->product($disposables, $creator, [
            'name' => 'PuffOnMate Pulse 5000',
            'slug' => 'puffonmate-pulse-5000',
            'sku' => 'POM-PULSE-5000',
            'description' => 'Rechargeable disposable with ~5000 puffs, mesh coil, and USB-C charging. Ideal everyday device for customers who want flavour without refilling.',
            'color_code' => ['mode' => 'solid', 'color' => '#DBEAFE'],
            'variants' => [
                ['name' => 'Iced Mango', 'sku' => 'POM-PULSE-5000-MANGO', 'attributes' => ['flavor' => 'Iced Mango', 'nicotine' => '20mg']],
                ['name' => 'Blue Razz Ice', 'sku' => 'POM-PULSE-5000-BRAZZ', 'attributes' => ['flavor' => 'Blue Razz Ice', 'nicotine' => '20mg']],
                ['name' => 'Watermelon Chill', 'sku' => 'POM-PULSE-5000-WM', 'attributes' => ['flavor' => 'Watermelon Chill', 'nicotine' => '20mg']],
                ['name' => 'Tobacco Classic', 'sku' => 'POM-PULSE-5000-TOB', 'attributes' => ['flavor' => 'Tobacco Classic', 'nicotine' => '20mg']],
            ],
        ]);

        $this->product($disposables, $creator, [
            'name' => 'PuffOnMate Mini Bar 1200',
            'slug' => 'puffonmate-mini-bar-1200',
            'sku' => 'POM-MINI-1200',
            'description' => 'Compact disposable bar with ~1200 puffs. Pocket-friendly and ready out of the pack — popular starter option.',
            'variants' => [
                ['name' => 'Strawberry Ice', 'sku' => 'POM-MINI-1200-STR', 'attributes' => ['flavor' => 'Strawberry Ice', 'nicotine' => '20mg']],
                ['name' => 'Grape Ice', 'sku' => 'POM-MINI-1200-GRP', 'attributes' => ['flavor' => 'Grape Ice', 'nicotine' => '20mg']],
                ['name' => 'Mint Fresh', 'sku' => 'POM-MINI-1200-MINT', 'attributes' => ['flavor' => 'Mint Fresh', 'nicotine' => '20mg']],
            ],
        ]);

        $this->product($podSystems, $creator, [
            'name' => 'PuffOnMate Core Kit',
            'slug' => 'puffonmate-core-kit',
            'sku' => 'POM-CORE-KIT',
            'description' => 'Closed-pod starter kit with 1000mAh battery, draw activation, and magnetic pod connection. Includes USB-C cable.',
            'variants' => [
                ['name' => 'Matte Black', 'sku' => 'POM-CORE-KIT-BLK', 'attributes' => ['color' => 'Matte Black']],
                ['name' => 'Ocean Blue', 'sku' => 'POM-CORE-KIT-BLU', 'attributes' => ['color' => 'Ocean Blue']],
                ['name' => 'Rose Gold', 'sku' => 'POM-CORE-KIT-RSG', 'attributes' => ['color' => 'Rose Gold']],
            ],
        ]);

        $this->product($podSystems, $creator, [
            'name' => 'PuffOnMate Air Pro',
            'slug' => 'puffonmate-air-pro',
            'sku' => 'POM-AIR-PRO',
            'description' => 'Slim open-pod system with adjustable airflow and 2ml refillable cartridge. Built for customers who prefer mixing their own liquids.',
            'variants' => [
                ['name' => 'Graphite', 'sku' => 'POM-AIR-PRO-GPH', 'attributes' => ['color' => 'Graphite']],
                ['name' => 'Silver', 'sku' => 'POM-AIR-PRO-SLV', 'attributes' => ['color' => 'Silver']],
            ],
        ]);

        $this->product($fruitLiquids, $creator, [
            'name' => 'Harbor Fruit Blend 60ml',
            'slug' => 'harbor-fruit-blend-60ml',
            'sku' => 'HFB-60',
            'description' => '70/30 VG/PG freebase liquid with layered tropical fruit notes. Bottle includes child-resistant cap and clear nicotine labelling.',
            'variants' => [
                ['name' => '0mg', 'sku' => 'HFB-60-0', 'attributes' => ['nicotine' => '0mg', 'volume' => '60ml']],
                ['name' => '3mg', 'sku' => 'HFB-60-3', 'attributes' => ['nicotine' => '3mg', 'volume' => '60ml']],
                ['name' => '6mg', 'sku' => 'HFB-60-6', 'attributes' => ['nicotine' => '6mg', 'volume' => '60ml']],
            ],
        ]);

        $this->product($fruitLiquids, $creator, [
            'name' => 'Citrus Surge Salt 30ml',
            'slug' => 'citrus-surge-salt-30ml',
            'sku' => 'CSS-30',
            'description' => 'Nicotine salt e-liquid with bright lemon-lime profile. Formulated for low-wattage pod devices.',
            'variants' => [
                ['name' => '20mg', 'sku' => 'CSS-30-20', 'attributes' => ['nicotine' => '20mg', 'volume' => '30ml']],
                ['name' => '35mg', 'sku' => 'CSS-30-35', 'attributes' => ['nicotine' => '35mg', 'volume' => '30ml']],
            ],
        ]);

        $this->product($dessertLiquids, $creator, [
            'name' => 'Vanilla Custard Reserve 60ml',
            'slug' => 'vanilla-custard-reserve-60ml',
            'sku' => 'VCR-60',
            'description' => 'Rich vanilla custard dessert profile with a soft bakery finish. Steeped for consistency across batches.',
            'variants' => [
                ['name' => '0mg', 'sku' => 'VCR-60-0', 'attributes' => ['nicotine' => '0mg', 'volume' => '60ml']],
                ['name' => '3mg', 'sku' => 'VCR-60-3', 'attributes' => ['nicotine' => '3mg', 'volume' => '60ml']],
                ['name' => '6mg', 'sku' => 'VCR-60-6', 'attributes' => ['nicotine' => '6mg', 'volume' => '60ml']],
            ],
        ]);

        $this->product($coilsPods, $creator, [
            'name' => 'Core Closed Pods (2-pack)',
            'slug' => 'core-closed-pods-2-pack',
            'sku' => 'POM-CORE-POD-2PK',
            'description' => 'Replacement closed pods for the PuffOnMate Core Kit. Mesh coil, 2ml capacity, sold as a twin pack.',
            'variants' => [
                ['name' => 'Iced Mango', 'sku' => 'POM-CORE-POD-MANGO', 'attributes' => ['flavor' => 'Iced Mango', 'resistance' => '1.0Ω']],
                ['name' => 'Mint Fresh', 'sku' => 'POM-CORE-POD-MINT', 'attributes' => ['flavor' => 'Mint Fresh', 'resistance' => '1.0Ω']],
                ['name' => 'Tobacco Classic', 'sku' => 'POM-CORE-POD-TOB', 'attributes' => ['flavor' => 'Tobacco Classic', 'resistance' => '1.0Ω']],
            ],
        ]);

        $this->product($coilsPods, $creator, [
            'name' => 'Air Pro Mesh Coils (5-pack)',
            'slug' => 'air-pro-mesh-coils-5-pack',
            'sku' => 'POM-AIR-COIL-5PK',
            'description' => 'Replacement mesh coils for the Air Pro refillable cartridge. Recommended change every 1–2 weeks depending on usage.',
            'variants' => [
                ['name' => '0.8Ω', 'sku' => 'POM-AIR-COIL-08', 'attributes' => ['resistance' => '0.8Ω']],
                ['name' => '1.2Ω', 'sku' => 'POM-AIR-COIL-12', 'attributes' => ['resistance' => '1.2Ω']],
            ],
        ]);

        $this->product($accessories, $creator, [
            'name' => 'USB-C Charge Cable 1m',
            'slug' => 'usb-c-charge-cable-1m',
            'sku' => 'ACC-USBC-1M',
            'description' => '1 metre USB-C charging cable compatible with PuffOnMate rechargeable disposables and kits.',
            'variants' => [],
        ]);

        $this->product($accessories, $creator, [
            'name' => 'Silicone Device Sleeve',
            'slug' => 'silicone-device-sleeve',
            'sku' => 'ACC-SLEEVE',
            'description' => 'Soft silicone sleeve that improves grip and reduces scuffs on Core Kit and Air Pro devices.',
            'variants' => [
                ['name' => 'Black', 'sku' => 'ACC-SLEEVE-BLK', 'attributes' => ['color' => 'Black']],
                ['name' => 'Clear', 'sku' => 'ACC-SLEEVE-CLR', 'attributes' => ['color' => 'Clear']],
            ],
        ]);

        $this->product($accessories, $creator, [
            'name' => 'Lanyard Clip',
            'slug' => 'lanyard-clip',
            'sku' => 'ACC-LANYARD',
            'description' => 'Detachable lanyard clip for Mini Bar and Pulse disposables — keeps the device handy without pocket wear.',
            'variants' => [],
        ]);
    }

    /**
     * @param  array{name: string, slug: string, parent_id: int|null}  $data
     */
    /**
     * @param  array{
     *     name: string,
     *     slug: string,
     *     parent_id: int|null,
     *     color_code?: array<string, mixed>|null
     * }  $data
     */
    private function category(array $data): Category
    {
        return Category::query()->firstOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'parent_id' => $data['parent_id'],
                'image' => null,
                'color_code' => $data['color_code'] ?? null,
                'status' => true,
            ],
        );
    }

    /**
     * @param  array{
     *     name: string,
     *     slug: string,
     *     sku: string,
     *     description: string,
     *     color_code?: array<string, mixed>|null,
     *     variants: list<array{name: string, sku: string, attributes: array<string, string>}>
     * }  $data
     */
    private function product(Category $category, ?User $creator, array $data): Product
    {
        $product = Product::query()->firstOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'],
                'image' => null,
                'color_code' => $data['color_code'] ?? null,
                'status' => true,
                'created_by' => $creator?->id,
            ],
        );

        $product->categories()->syncWithoutDetaching([$category->id]);

        foreach ($data['variants'] as $variant) {
            ProductVariant::query()->firstOrCreate(
                ['sku' => $variant['sku']],
                [
                    'product_id' => $product->id,
                    'name' => $variant['name'],
                    'attributes' => $variant['attributes'],
                    'status' => true,
                ],
            );
        }

        return $product;
    }
}
