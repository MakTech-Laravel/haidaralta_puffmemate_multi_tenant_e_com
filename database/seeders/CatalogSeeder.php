<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CatalogSeeder extends Seeder
{
    /**
     * Seed a realistic cigarettes / vapes / accessories catalog (no Faker).
     * Products stay disabled at every branch until BranchInventorySeeder runs.
     *
     * Images live under public/images (storefront or catalog downloads).
     * DB stores web paths like /images/storefront/... — never raw remote URLs.
     */
    public function run(): void
    {
        $this->clearObsoleteSeedStorage();

        $creator = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first();

        $cigarettes = $this->category([
            'name' => 'Cigarettes',
            'slug' => 'cigarettes',
            'parent_id' => null,
            'featured' => true,
            'show_in_nav' => true,
            'nav_sort' => 1,
            'slogan' => 'Premium tobacco, best brands',
            'image' => 'images/storefront/category-cigarettes.png',
            'color_code' => ['mode' => 'solid', 'color' => '#F5E6D3'],
        ]);

        $vapes = $this->category([
            'name' => 'Vapes & Disposables',
            'slug' => 'vapes',
            'parent_id' => null,
            'featured' => true,
            'show_in_nav' => true,
            'nav_sort' => 3,
            'slogan' => 'Disposable & rechargeable',
            'image' => 'images/storefront/category-vapes.png',
            'color_code' => ['mode' => 'solid', 'color' => '#E8F0FE'],
        ]);

        $heated = $this->category([
            'name' => 'Tobacco',
            'slug' => 'tobacco',
            'parent_id' => null,
            'featured' => true,
            'show_in_nav' => true,
            'nav_sort' => 2,
            'slogan' => 'Rolling & specialty blends',
            'image' => 'images/storefront/category-tobacco.png',
            'color_code' => [
                'mode' => 'gradient',
                'from' => '#1F2937',
                'to' => '#6B7280',
                'angle' => 140,
            ],
        ]);

        $eLiquids = $this->category([
            'name' => 'E-Liquids',
            'slug' => 'e-liquids',
            'parent_id' => null,
            'featured' => false,
            'color_code' => ['mode' => 'solid', 'color' => '#ECFDF5'],
        ]);

        $fruitLiquids = $this->category([
            'name' => 'Fruit',
            'slug' => 'e-liquids-fruit',
            'parent_id' => $eLiquids->id,
            'featured' => false,
        ]);

        $dessertLiquids = $this->category([
            'name' => 'Dessert',
            'slug' => 'e-liquids-dessert',
            'parent_id' => $eLiquids->id,
            'featured' => false,
        ]);

        $tobaccoLiquids = $this->category([
            'name' => 'Tobacco Liquids',
            'slug' => 'e-liquids-tobacco',
            'parent_id' => $eLiquids->id,
            'featured' => false,
        ]);

        $pouches = $this->category([
            'name' => 'Nicotine Pouches',
            'slug' => 'nicotine-pouches',
            'parent_id' => null,
            'featured' => true,
            'show_in_nav' => true,
            'nav_sort' => 4,
            'slogan' => 'Smoke-free alternatives',
            'image' => 'images/storefront/category-pouches.png',
            'color_code' => ['mode' => 'solid', 'color' => '#FEF3C7'],
        ]);

        $coilsPods = $this->category([
            'name' => 'Coils & Pods',
            'slug' => 'coils-pods',
            'parent_id' => null,
            'featured' => false,
            'color_code' => ['mode' => 'solid', 'color' => '#F3E8FF'],
        ]);

        $accessories = $this->category([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'parent_id' => null,
            'featured' => true,
            'show_in_nav' => false,
            'slogan' => 'Everything you need to complete your setup',
            'image' => 'images/storefront/category-accessories.png',
            'color_code' => ['mode' => 'solid', 'color' => '#F1F5F9'],
        ]);

        // Keep legacy parent slug used by older local DBs / demos.
        $this->category([
            'name' => 'Disposables',
            'slug' => 'disposables',
            'parent_id' => $vapes->id,
            'featured' => false,
        ]);

        $this->category([
            'name' => 'Pod Systems',
            'slug' => 'pod-systems',
            'parent_id' => $vapes->id,
            'featured' => false,
        ]);

        $this->category([
            'name' => 'Packs & Saves',
            'slug' => 'packs-saves',
            'parent_id' => null,
            'featured' => false,
            'show_in_nav' => true,
            'nav_sort' => 5,
            'slogan' => 'Bundles and multipacks',
        ]);

        $this->category([
            'name' => 'Wholesale',
            'slug' => 'wholesale',
            'parent_id' => null,
            'featured' => false,
            'show_in_nav' => true,
            'nav_sort' => 6,
            'slogan' => 'Trade and bulk pricing',
        ]);

        // ——— Cigarettes ———
        $this->product([$cigarettes], $creator, [
            'name' => 'Marlboro Gold Soft Pack 20s',
            'slug' => 'marlboro-gold-soft-pack-20s',
            'sku' => 'CIG-MB-GOLD-20',
            'brand' => 'Marlboro',
            'tags' => ['best_selling', 'popular'],
            'specifications' => [
                ['label' => 'Variant', 'value' => 'Gold'],
                ['label' => 'Pack Format', 'value' => 'Soft Pack'],
                ['label' => 'Quantity', 'value' => '20 cigarettes'],
                ['label' => 'Filter', 'value' => 'Firm Filter'],
                ['label' => 'Market', 'value' => 'Australia'],
                ['label' => 'Packaging', 'value' => 'Australian compliant plain packaging'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Premium Virginia blend cigarettes in a soft pack of 20. Smooth gold profile popular with everyday adult smokers. Age-restricted product — 18+ only.',
            'image' => 'images/storefront/product-marlboro-gold.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#F8E7C1'],
            'variants' => [
                ['name' => 'Single pack', 'sku' => 'CIG-MB-GOLD-20-PK', 'attributes' => ['pack_size' => '20', 'format' => 'Soft pack']],
                ['name' => 'Carton (10 packs)', 'sku' => 'CIG-MB-GOLD-20-CTN', 'attributes' => ['pack_size' => '200', 'format' => 'Carton']],
            ],
        ]);

        $this->product([$cigarettes], $creator, [
            'name' => 'Winfield Blue Soft Pack 20s',
            'slug' => 'winfield-blue-soft-pack-20s',
            'sku' => 'CIG-WF-BLUE-20',
            'brand' => 'Winfield',
            'tags' => ['best_selling', 'new_arrival'],
            'specifications' => [
                ['label' => 'Variant', 'value' => 'Blue'],
                ['label' => 'Pack Format', 'value' => 'Soft Pack'],
                ['label' => 'Quantity', 'value' => '20 cigarettes'],
                ['label' => 'Market', 'value' => 'Australia'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Australian staple blue brand in a soft pack of 20. Balanced taste with familiar packaging. Age-restricted product — 18+ only.',
            'image' => 'images/storefront/product-winfield-blue.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#DBEAFE'],
            'variants' => [
                ['name' => 'Single pack', 'sku' => 'CIG-WF-BLUE-20-PK', 'attributes' => ['pack_size' => '20', 'format' => 'Soft pack']],
                ['name' => 'Carton (10 packs)', 'sku' => 'CIG-WF-BLUE-20-CTN', 'attributes' => ['pack_size' => '200', 'format' => 'Carton']],
            ],
        ]);

        $this->product([$cigarettes], $creator, [
            'name' => 'Horizon Red Soft Pack 25s',
            'slug' => 'horizon-red-soft-pack-25s',
            'sku' => 'CIG-HZ-RED-25',
            'brand' => 'Horizon',
            'tags' => ['on_offer', 'popular'],
            'specifications' => [
                ['label' => 'Variant', 'value' => 'Red'],
                ['label' => 'Pack Format', 'value' => 'Soft Pack'],
                ['label' => 'Quantity', 'value' => '25 cigarettes'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Value red cigarettes in a 25s soft pack for customers who prefer larger packs. Age-restricted product — 18+ only.',
            'image_url' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=800&q=80',
            'color_code' => ['mode' => 'solid', 'color' => '#FEE2E2'],
            'variants' => [
                ['name' => 'Single pack', 'sku' => 'CIG-HZ-RED-25-PK', 'attributes' => ['pack_size' => '25', 'format' => 'Soft pack']],
            ],
        ]);

        $this->product([$cigarettes], $creator, [
            'name' => 'Peter Jackson Menthol 20s',
            'slug' => 'peter-jackson-menthol-20s',
            'sku' => 'CIG-PJ-MENTH-20',
            'brand' => 'Peter Jackson',
            'tags' => ['new_arrival'],
            'specifications' => [
                ['label' => 'Variant', 'value' => 'Menthol'],
                ['label' => 'Pack Format', 'value' => 'Soft Pack'],
                ['label' => 'Quantity', 'value' => '20 cigarettes'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Menthol-style pack of 20 for adult smokers who prefer a cooler finish. Age-restricted product — 18+ only.',
            'image' => 'images/storefront/product-winfield-blue.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#D1FAE5'],
            'variants' => [
                ['name' => 'Single pack', 'sku' => 'CIG-PJ-MENTH-20-PK', 'attributes' => ['pack_size' => '20', 'format' => 'Soft pack']],
            ],
        ]);

        // ——— Heated tobacco ———
        $this->product([$heated], $creator, [
            'name' => 'IQOS ILUMA Starter Kit',
            'slug' => 'iqos-iluma-starter-kit',
            'sku' => 'HTP-IQOS-ILUMA-KIT',
            'brand' => 'IQOS',
            'tags' => ['best_selling', 'new_arrival'],
            'specifications' => [
                ['label' => 'Pack Format', 'value' => 'Device Kit'],
                ['label' => 'Market', 'value' => 'Australia'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Induction heated tobacco starter kit with holder, pocket charger, USB-C cable, and quick-start guide. Compatible with TEREA sticks. Age-restricted — 18+ only.',
            'image' => 'images/storefront/product-iqos.png',
            'color_code' => ['mode' => 'solid', 'color' => '#E5E7EB'],
            'variants' => [
                ['name' => 'Pebble Grey', 'sku' => 'HTP-IQOS-ILUMA-GREY', 'attributes' => ['color' => 'Pebble Grey']],
                ['name' => 'Azure Blue', 'sku' => 'HTP-IQOS-ILUMA-BLUE', 'attributes' => ['color' => 'Azure Blue']],
                ['name' => 'Sunset Red', 'sku' => 'HTP-IQOS-ILUMA-RED', 'attributes' => ['color' => 'Sunset Red']],
            ],
            'gallery_urls' => [
                'https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=800&q=80',
            ],
        ]);

        $this->product([$heated], $creator, [
            'name' => 'TEREA Tobacco Sticks (Pack of 20)',
            'slug' => 'terea-tobacco-sticks-20',
            'sku' => 'HTP-TEREA-20',
            'brand' => 'TEREA',
            'tags' => ['popular'],
            'specifications' => [
                ['label' => 'Quantity', 'value' => '20 sticks'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Heat-not-burn tobacco sticks for IQOS ILUMA. Sold as a pack of 20 sticks. Age-restricted — 18+ only.',
            'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
            'color_code' => ['mode' => 'solid', 'color' => '#FEF3C7'],
            'variants' => [
                ['name' => 'Amber', 'sku' => 'HTP-TEREA-AMBER', 'attributes' => ['flavor' => 'Amber']],
                ['name' => 'Sienna', 'sku' => 'HTP-TEREA-SIENNA', 'attributes' => ['flavor' => 'Sienna']],
                ['name' => 'Turquoise Menthol', 'sku' => 'HTP-TEREA-TURQ', 'attributes' => ['flavor' => 'Turquoise Menthol']],
            ],
        ]);

        // ——— Vapes & disposables ———
        $this->product([$vapes], $creator, [
            'name' => 'Vuse Go 5000 Disposable',
            'slug' => 'vuse-go-5000-disposable',
            'sku' => 'VAPE-VUSE-GO-5000',
            'brand' => 'Vuse',
            'tags' => ['best_selling', 'on_offer'],
            'specifications' => [
                ['label' => 'Pack Format', 'value' => 'Disposable'],
                ['label' => 'Quantity', 'value' => '~5000 puffs'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Rechargeable disposable vape with approximately 5000 puffs, mesh coil, and USB-C charging. Ready out of the pack. Age-restricted — 18+ only.',
            'image' => 'images/storefront/product-vuse.png',
            'color_code' => ['mode' => 'solid', 'color' => '#DBEAFE'],
            'variants' => [
                ['name' => 'Berry Blend', 'sku' => 'VAPE-VUSE-GO-BERRY', 'attributes' => ['flavor' => 'Berry Blend', 'nicotine' => '20mg', 'puffs' => '~5000']],
                ['name' => 'Mint Ice', 'sku' => 'VAPE-VUSE-GO-MINT', 'attributes' => ['flavor' => 'Mint Ice', 'nicotine' => '20mg', 'puffs' => '~5000']],
                ['name' => 'Tobacco Rich', 'sku' => 'VAPE-VUSE-GO-TOB', 'attributes' => ['flavor' => 'Tobacco Rich', 'nicotine' => '20mg', 'puffs' => '~5000']],
                ['name' => 'Watermelon Ice', 'sku' => 'VAPE-VUSE-GO-WM', 'attributes' => ['flavor' => 'Watermelon Ice', 'nicotine' => '20mg', 'puffs' => '~5000']],
            ],
        ]);

        $this->product([$vapes], $creator, [
            'name' => 'PuffMeMate Pulse 5000',
            'slug' => 'puffmemate-pulse-5000',
            'sku' => 'POM-PULSE-5000',
            'brand' => 'PuffMeMate',
            'tags' => ['new_arrival', 'popular'],
            'specifications' => [
                ['label' => 'Pack Format', 'value' => 'Disposable'],
                ['label' => 'Quantity', 'value' => '~5000 puffs'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'House-brand rechargeable disposable with ~5000 puffs, mesh coil, and USB-C charging. Everyday device for customers who want flavour without refilling.',
            'image' => 'images/storefront/907b72737ecd36dbee5ac6c27fa0b02b906a44ee.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#DBEAFE'],
            'variants' => [
                ['name' => 'Iced Mango', 'sku' => 'POM-PULSE-5000-MANGO', 'attributes' => ['flavor' => 'Iced Mango', 'nicotine' => '20mg']],
                ['name' => 'Blue Razz Ice', 'sku' => 'POM-PULSE-5000-BRAZZ', 'attributes' => ['flavor' => 'Blue Razz Ice', 'nicotine' => '20mg']],
                ['name' => 'Watermelon Chill', 'sku' => 'POM-PULSE-5000-WM', 'attributes' => ['flavor' => 'Watermelon Chill', 'nicotine' => '20mg']],
                ['name' => 'Tobacco Classic', 'sku' => 'POM-PULSE-5000-TOB', 'attributes' => ['flavor' => 'Tobacco Classic', 'nicotine' => '20mg']],
            ],
        ]);

        $this->product([$vapes], $creator, [
            'name' => 'PuffMeMate Mini Bar 1200',
            'slug' => 'puffmemate-mini-bar-1200',
            'sku' => 'POM-MINI-1200',
            'description' => 'Compact disposable bar with ~1200 puffs. Pocket-friendly starter option for adult nicotine consumers.',
            'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => 'Strawberry Ice', 'sku' => 'POM-MINI-1200-STR', 'attributes' => ['flavor' => 'Strawberry Ice', 'nicotine' => '20mg']],
                ['name' => 'Grape Ice', 'sku' => 'POM-MINI-1200-GRP', 'attributes' => ['flavor' => 'Grape Ice', 'nicotine' => '20mg']],
                ['name' => 'Mint Fresh', 'sku' => 'POM-MINI-1200-MINT', 'attributes' => ['flavor' => 'Mint Fresh', 'nicotine' => '20mg']],
            ],
        ]);

        $this->product([$vapes], $creator, [
            'name' => 'PuffMeMate Core Kit',
            'slug' => 'puffmemate-core-kit',
            'sku' => 'POM-CORE-KIT',
            'description' => 'Closed-pod starter kit with 1000mAh battery, draw activation, and magnetic pod connection. Includes USB-C cable.',
            'image_url' => 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => 'Matte Black', 'sku' => 'POM-CORE-KIT-BLK', 'attributes' => ['color' => 'Matte Black']],
                ['name' => 'Ocean Blue', 'sku' => 'POM-CORE-KIT-BLU', 'attributes' => ['color' => 'Ocean Blue']],
                ['name' => 'Rose Gold', 'sku' => 'POM-CORE-KIT-RSG', 'attributes' => ['color' => 'Rose Gold']],
            ],
        ]);

        $this->product([$vapes], $creator, [
            'name' => 'PuffMeMate Air Pro',
            'slug' => 'puffmemate-air-pro',
            'sku' => 'POM-AIR-PRO',
            'description' => 'Slim open-pod system with adjustable airflow and 2ml refillable cartridge for customers who prefer mixing their own liquids.',
            'image' => 'images/storefront/b4343f447a376e4db4da6328e10eddefc632ddeb.jpg',
            'variants' => [
                ['name' => 'Graphite', 'sku' => 'POM-AIR-PRO-GPH', 'attributes' => ['color' => 'Graphite']],
                ['name' => 'Silver', 'sku' => 'POM-AIR-PRO-SLV', 'attributes' => ['color' => 'Silver']],
            ],
        ]);

        // ——— E-liquids ———
        $this->product([$fruitLiquids, $eLiquids], $creator, [
            'name' => 'Harbor Fruit Blend 60ml',
            'slug' => 'harbor-fruit-blend-60ml',
            'sku' => 'HFB-60',
            'description' => '70/30 VG/PG freebase liquid with layered tropical fruit notes. Child-resistant cap and clear nicotine labelling.',
            'image_url' => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => '0mg', 'sku' => 'HFB-60-0', 'attributes' => ['nicotine' => '0mg', 'volume' => '60ml']],
                ['name' => '3mg', 'sku' => 'HFB-60-3', 'attributes' => ['nicotine' => '3mg', 'volume' => '60ml']],
                ['name' => '6mg', 'sku' => 'HFB-60-6', 'attributes' => ['nicotine' => '6mg', 'volume' => '60ml']],
            ],
        ]);

        $this->product([$fruitLiquids, $eLiquids], $creator, [
            'name' => 'Citrus Surge Salt 30ml',
            'slug' => 'citrus-surge-salt-30ml',
            'sku' => 'CSS-30',
            'description' => 'Nicotine salt e-liquid with bright lemon-lime profile. Formulated for low-wattage pod devices.',
            'image_url' => 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => '20mg', 'sku' => 'CSS-30-20', 'attributes' => ['nicotine' => '20mg', 'volume' => '30ml']],
                ['name' => '35mg', 'sku' => 'CSS-30-35', 'attributes' => ['nicotine' => '35mg', 'volume' => '30ml']],
            ],
        ]);

        $this->product([$dessertLiquids, $eLiquids], $creator, [
            'name' => 'Vanilla Custard Reserve 60ml',
            'slug' => 'vanilla-custard-reserve-60ml',
            'sku' => 'VCR-60',
            'description' => 'Rich vanilla custard dessert profile with a soft bakery finish. Steeped for batch consistency.',
            'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => '0mg', 'sku' => 'VCR-60-0', 'attributes' => ['nicotine' => '0mg', 'volume' => '60ml']],
                ['name' => '3mg', 'sku' => 'VCR-60-3', 'attributes' => ['nicotine' => '3mg', 'volume' => '60ml']],
                ['name' => '6mg', 'sku' => 'VCR-60-6', 'attributes' => ['nicotine' => '6mg', 'volume' => '60ml']],
            ],
        ]);

        $this->product([$tobaccoLiquids, $eLiquids], $creator, [
            'name' => 'Classic Leaf Tobacco 60ml',
            'slug' => 'classic-leaf-tobacco-60ml',
            'sku' => 'CLT-60',
            'description' => 'Traditional tobacco freebase liquid with mild roasted notes — popular bridge flavour for smokers switching to vaping.',
            'image_url' => 'https://images.unsplash.com/photo-1601050690117-94f5f6fa1a6e?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => '3mg', 'sku' => 'CLT-60-3', 'attributes' => ['nicotine' => '3mg', 'volume' => '60ml']],
                ['name' => '6mg', 'sku' => 'CLT-60-6', 'attributes' => ['nicotine' => '6mg', 'volume' => '60ml']],
                ['name' => '12mg', 'sku' => 'CLT-60-12', 'attributes' => ['nicotine' => '12mg', 'volume' => '60ml']],
            ],
        ]);

        // ——— Nicotine pouches ———
        $this->product([$pouches], $creator, [
            'name' => 'Nordic Mint Pouches',
            'slug' => 'nordic-mint-pouches',
            'sku' => 'POUCH-NORDIC-MINT',
            'description' => 'Tobacco-free nicotine pouches with a clean mint profile. Slim format tin of 20 pouches. Age-restricted — 18+ only.',
            'image' => 'images/storefront/product-marlboro-gold.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#ECFDF5'],
            'variants' => [
                ['name' => '6mg', 'sku' => 'POUCH-NORDIC-6', 'attributes' => ['strength' => '6mg', 'count' => '20']],
                ['name' => '10mg', 'sku' => 'POUCH-NORDIC-10', 'attributes' => ['strength' => '10mg', 'count' => '20']],
                ['name' => '16mg', 'sku' => 'POUCH-NORDIC-16', 'attributes' => ['strength' => '16mg', 'count' => '20']],
            ],
        ]);

        $this->product([$pouches], $creator, [
            'name' => 'Citrus Chill Pouches',
            'slug' => 'citrus-chill-pouches',
            'sku' => 'POUCH-CITRUS',
            'description' => 'Tobacco-free nicotine pouches with citrus ice flavour. Slim format tin of 20. Age-restricted — 18+ only.',
            'image_url' => 'https://images.unsplash.com/photo-1550572017-edd951aa8f72?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => '8mg', 'sku' => 'POUCH-CITRUS-8', 'attributes' => ['strength' => '8mg', 'count' => '20']],
                ['name' => '12mg', 'sku' => 'POUCH-CITRUS-12', 'attributes' => ['strength' => '12mg', 'count' => '20']],
            ],
        ]);

        // ——— Coils & pods ———
        $this->product([$coilsPods], $creator, [
            'name' => 'Core Closed Pods (2-pack)',
            'slug' => 'core-closed-pods-2-pack',
            'sku' => 'POM-CORE-POD-2PK',
            'description' => 'Replacement closed pods for the PuffMeMate Core Kit. Mesh coil, 2ml capacity, sold as a twin pack.',
            'image_url' => 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => 'Iced Mango', 'sku' => 'POM-CORE-POD-MANGO', 'attributes' => ['flavor' => 'Iced Mango', 'resistance' => '1.0Ω']],
                ['name' => 'Mint Fresh', 'sku' => 'POM-CORE-POD-MINT', 'attributes' => ['flavor' => 'Mint Fresh', 'resistance' => '1.0Ω']],
                ['name' => 'Tobacco Classic', 'sku' => 'POM-CORE-POD-TOB', 'attributes' => ['flavor' => 'Tobacco Classic', 'resistance' => '1.0Ω']],
            ],
        ]);

        $this->product([$coilsPods], $creator, [
            'name' => 'Air Pro Mesh Coils (5-pack)',
            'slug' => 'air-pro-mesh-coils-5-pack',
            'sku' => 'POM-AIR-COIL-5PK',
            'description' => 'Replacement mesh coils for the Air Pro refillable cartridge. Change every 1–2 weeks depending on usage.',
            'image_url' => 'https://images.unsplash.com/photo-1581092160562-40aa08e78837?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => '0.8Ω', 'sku' => 'POM-AIR-COIL-08', 'attributes' => ['resistance' => '0.8Ω']],
                ['name' => '1.2Ω', 'sku' => 'POM-AIR-COIL-12', 'attributes' => ['resistance' => '1.2Ω']],
            ],
        ]);

        // ——— Accessories ———
        $this->product([$accessories, $cigarettes], $creator, [
            'name' => 'Clipper Classic Lighter',
            'slug' => 'clipper-classic-lighter',
            'sku' => 'ACC-CLIPPER',
            'description' => 'Refillable flint lighter with fixed flame. Reliable counter-side staple for cigarette and cigarillo customers.',
            'image' => 'images/storefront/product-marlboro-gold.jpg',
            'variants' => [
                ['name' => 'Black', 'sku' => 'ACC-CLIPPER-BLK', 'attributes' => ['color' => 'Black']],
                ['name' => 'Red', 'sku' => 'ACC-CLIPPER-RED', 'attributes' => ['color' => 'Red']],
                ['name' => 'Blue', 'sku' => 'ACC-CLIPPER-BLU', 'attributes' => ['color' => 'Blue']],
            ],
        ]);

        $this->product([$accessories, $cigarettes], $creator, [
            'name' => 'RAW Classic Rolling Papers King Size',
            'slug' => 'raw-classic-rolling-papers-ks',
            'sku' => 'ACC-RAW-KS',
            'description' => 'Unbleached king-size rolling papers. Pack of 32 leaves. Adult-use product.',
            'image_url' => 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?auto=format&fit=crop&w=800&q=80',
            'variants' => [],
        ]);

        $this->product([$accessories], $creator, [
            'name' => 'USB-C Charge Cable 1m',
            'slug' => 'usb-c-charge-cable-1m',
            'sku' => 'ACC-USBC-1M',
            'description' => '1 metre USB-C charging cable compatible with PuffMeMate rechargeable disposables, Core Kit, Air Pro, and IQOS pocket chargers.',
            'image_url' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=800&q=80',
            'variants' => [],
        ]);

        $this->product([$accessories], $creator, [
            'name' => 'Silicone Device Sleeve',
            'slug' => 'silicone-device-sleeve',
            'sku' => 'ACC-SLEEVE',
            'description' => 'Soft silicone sleeve that improves grip and reduces scuffs on Core Kit and Air Pro devices.',
            'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => 'Black', 'sku' => 'ACC-SLEEVE-BLK', 'attributes' => ['color' => 'Black']],
                ['name' => 'Clear', 'sku' => 'ACC-SLEEVE-CLR', 'attributes' => ['color' => 'Clear']],
            ],
        ]);

        $this->product([$accessories], $creator, [
            'name' => 'Travel Hard Case',
            'slug' => 'travel-hard-case',
            'sku' => 'ACC-HARD-CASE',
            'description' => 'Compact hard case with foam insert for a disposable or pod kit plus spare pods — reduces pocket lint and accidental fire button presses.',
            'image_url' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => 'Charcoal', 'sku' => 'ACC-HARD-CASE-CHR', 'attributes' => ['color' => 'Charcoal']],
                ['name' => 'Navy', 'sku' => 'ACC-HARD-CASE-NVY', 'attributes' => ['color' => 'Navy']],
            ],
        ]);

        $this->product([$accessories], $creator, [
            'name' => 'Lanyard Clip',
            'slug' => 'lanyard-clip',
            'sku' => 'ACC-LANYARD',
            'description' => 'Detachable lanyard clip for Mini Bar and Pulse disposables — keeps the device handy without pocket wear.',
            'image_url' => 'https://images.unsplash.com/photo-1620799140408-edac6e80be8e?auto=format&fit=crop&w=800&q=80',
            'variants' => [],
        ]);
    }

    /**
     * @param  array{
     *     name: string,
     *     slug: string,
     *     parent_id: int|null,
     *     featured?: bool,
     *     show_in_nav?: bool,
     *     nav_sort?: int|null,
     *     slogan?: string|null,
     *     image?: string|null,
     *     image_url?: string|null,
     *     color_code?: array<string, mixed>|null
     * }  $data
     */
    private function category(array $data): Category
    {
        $imagePath = $this->resolveImagePath(
            publicRelative: $data['image'] ?? null,
            remoteUrl: $data['image_url'] ?? null,
            catalogDirectory: 'categories',
            basename: $data['slug'],
        );

        return Category::query()->firstOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'parent_id' => $data['parent_id'],
                'slogan' => $data['slogan'] ?? null,
                'image' => $imagePath,
                'color_code' => $data['color_code'] ?? null,
                'status' => true,
                'featured' => $data['featured'] ?? false,
                'show_in_nav' => $data['show_in_nav'] ?? false,
                'nav_sort' => $data['nav_sort'] ?? null,
            ],
        );
    }

    /**
     * @param  list<Category>  $categories
     * @param  array{
     *     name: string,
     *     slug: string,
     *     sku: string,
     *     description: string,
     *     brand?: string|null,
     *     tags?: list<string>,
     *     specifications?: list<array{label: string, value: string}>,
     *     image?: string|null,
     *     image_url?: string|null,
     *     gallery_urls?: list<string>,
     *     color_code?: array<string, mixed>|null,
     *     variants: list<array{name: string, sku: string, attributes: array<string, string>}>
     * }  $data
     */
    private function product(array $categories, ?User $creator, array $data): Product
    {
        $imagePath = $this->resolveImagePath(
            publicRelative: $data['image'] ?? null,
            remoteUrl: $data['image_url'] ?? null,
            catalogDirectory: 'products',
            basename: $data['slug'],
        );

        $product = Product::query()->firstOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'],
                'brand' => $data['brand'] ?? null,
                'tags' => $data['tags'] ?? [],
                'specifications' => $data['specifications'] ?? [],
                'image' => $imagePath,
                'color_code' => $data['color_code'] ?? null,
                'status' => true,
                'created_by' => $creator?->id,
            ],
        );

        $product->categories()->syncWithoutDetaching(
            collect($categories)->map(fn (Category $category): int => $category->id)->all(),
        );

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

        foreach ($data['gallery_urls'] ?? [] as $index => $galleryUrl) {
            $path = $this->storeRemoteImage(
                $galleryUrl,
                'gallery',
                $data['slug'].'-gallery-'.($index + 1),
            );

            if ($path === null) {
                continue;
            }

            ProductImage::query()->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'path' => $path,
                ],
                [
                    'sort_order' => $index + 1,
                ],
            );
        }

        return $product;
    }

    private function resolveImagePath(
        ?string $publicRelative,
        ?string $remoteUrl,
        string $catalogDirectory,
        string $basename,
    ): ?string {
        if (is_string($publicRelative) && $publicRelative !== '') {
            $fromPublic = $this->publicWebPath($publicRelative);

            if ($fromPublic !== null) {
                return $fromPublic;
            }
        }

        if (is_string($remoteUrl) && $remoteUrl !== '') {
            return $this->storeRemoteImage($remoteUrl, $catalogDirectory, $basename);
        }

        return null;
    }

    private function publicWebPath(string $relativePublicPath): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relativePublicPath), '/');
        $absolute = public_path($relative);

        if (! is_file($absolute)) {
            return null;
        }

        return '/'.$relative;
    }

    private function storeRemoteImage(string $url, string $catalogDirectory, string $basename): ?string
    {
        $directory = 'images/catalog/'.trim($catalogDirectory, '/');
        $absoluteDirectory = public_path($directory);

        if (! is_dir($absoluteDirectory) && ! mkdir($absoluteDirectory, 0755, true) && ! is_dir($absoluteDirectory)) {
            return null;
        }

        $slug = Str::slug($basename);
        $existing = $this->existingCatalogFile($absoluteDirectory, $slug);

        if ($existing !== null) {
            return '/'.$directory.'/'.$existing;
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'PuffMeMateCatalogSeeder/1.0'])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            $extension = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };

            $filename = $slug.'.'.$extension;
            $absolute = $absoluteDirectory.DIRECTORY_SEPARATOR.$filename;

            file_put_contents($absolute, $response->body());

            return '/'.$directory.'/'.$filename;
        } catch (Throwable) {
            return null;
        }
    }

    private function existingCatalogFile(string $absoluteDirectory, string $slug): ?string
    {
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $extension) {
            $filename = $slug.'.'.$extension;

            if (is_file($absoluteDirectory.DIRECTORY_SEPARATOR.$filename)) {
                return $filename;
            }
        }

        return null;
    }

    private function clearObsoleteSeedStorage(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $disk = Storage::disk('public');

        foreach (['categories', 'products'] as $directory) {
            if ($disk->exists($directory)) {
                $disk->deleteDirectory($directory);
            }
        }
    }
}
