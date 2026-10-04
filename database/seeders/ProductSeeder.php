<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Database\Seeders\Concerns\SeedsCatalogMedia;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use ReportsSeedProgress;
    use SeedsCatalogMedia;

    /**
     * Seed catalog products, variants, and gallery images. Depends on CategorySeeder.
     * Products stay unstocked at branches until BranchInventorySeeder runs.
     */
    public function run(): void
    {
        $creator = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first();

        $cigarettes = Category::query()->where('slug', 'cigarettes')->firstOrFail();
        $vapes = Category::query()->where('slug', 'vapes')->firstOrFail();
        $heated = Category::query()->where('slug', 'tobacco')->firstOrFail();
        $pouches = Category::query()->where('slug', 'nicotine-pouches')->firstOrFail();
        $accessories = Category::query()->where('slug', 'accessories')->firstOrFail();

        // ——— Cigarettes ———
        $this->product([$cigarettes], $creator, [
            'name' => 'Marlboro Gold Soft Pack 20s',
            'slug' => 'marlboro-gold-soft-pack-20s',
            'sku' => 'CIG-MB-GOLD-20',
            'brand' => 'Marlboro',
            'pack_label' => 'Gold Pack',
            'pieces_per_pack' => 20,
            'pack_unit' => 'Cigarettes',
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
            'pack_label' => 'Blue Pack',
            'pieces_per_pack' => 20,
            'pack_unit' => 'Cigarettes',
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
            'pack_label' => 'Device Kit • Heat-not-burn',
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
            'pack_label' => 'Disposable • Mixed Berry',
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

        // ——— Extra cigarettes (AU retail staples) ———
        $this->product([$cigarettes], $creator, [
            'name' => 'Benson & Hedges Smooth Soft Pack 20s',
            'slug' => 'benson-hedges-smooth-soft-pack-20s',
            'sku' => 'CIG-BH-SMOOTH-20',
            'brand' => 'Benson & Hedges',
            'tags' => ['popular', 'best_selling'],
            'specifications' => [
                ['label' => 'Variant', 'value' => 'Smooth'],
                ['label' => 'Pack Format', 'value' => 'Soft Pack'],
                ['label' => 'Quantity', 'value' => '20 cigarettes'],
                ['label' => 'Market', 'value' => 'Australia'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Premium smooth Virginia blend in a soft pack of 20. Long-standing favourite among adult Australian smokers. Age-restricted — 18+ only.',
            'image' => 'images/storefront/product-marlboro-gold.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#FEF9C3'],
            'variants' => [
                ['name' => 'Single pack', 'sku' => 'CIG-BH-SMOOTH-20-PK', 'attributes' => ['pack_size' => '20', 'format' => 'Soft pack']],
                ['name' => 'Carton (10 packs)', 'sku' => 'CIG-BH-SMOOTH-20-CTN', 'attributes' => ['pack_size' => '200', 'format' => 'Carton']],
            ],
        ]);

        $this->product([$cigarettes], $creator, [
            'name' => 'Dunhill Fine Cut Soft Pack 20s',
            'slug' => 'dunhill-fine-cut-soft-pack-20s',
            'sku' => 'CIG-DH-FINE-20',
            'brand' => 'Dunhill',
            'tags' => ['new_arrival'],
            'specifications' => [
                ['label' => 'Variant', 'value' => 'Fine Cut'],
                ['label' => 'Pack Format', 'value' => 'Soft Pack'],
                ['label' => 'Quantity', 'value' => '20 cigarettes'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Fine-cut premium cigarettes in a soft pack of 20. Refined taste profile for adult smokers seeking a smoother draw. Age-restricted — 18+ only.',
            'image' => 'images/storefront/product-marlboro-gold.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#E7E5E4'],
            'variants' => [
                ['name' => 'Single pack', 'sku' => 'CIG-DH-FINE-20-PK', 'attributes' => ['pack_size' => '20', 'format' => 'Soft pack']],
                ['name' => 'Carton (10 packs)', 'sku' => 'CIG-DH-FINE-20-CTN', 'attributes' => ['pack_size' => '200', 'format' => 'Carton']],
            ],
        ]);

        $this->product([$cigarettes], $creator, [
            'name' => 'Rothmans Blue Soft Pack 20s',
            'slug' => 'rothmans-blue-soft-pack-20s',
            'sku' => 'CIG-RM-BLUE-20',
            'brand' => 'Rothmans',
            'tags' => ['on_offer', 'popular'],
            'specifications' => [
                ['label' => 'Variant', 'value' => 'Blue'],
                ['label' => 'Pack Format', 'value' => 'Soft Pack'],
                ['label' => 'Quantity', 'value' => '20 cigarettes'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Classic blue Rothmans soft pack of 20. Balanced everyday smoke popular across Australian metros. Age-restricted — 18+ only.',
            'image' => 'images/storefront/product-winfield-blue.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#DBEAFE'],
            'variants' => [
                ['name' => 'Single pack', 'sku' => 'CIG-RM-BLUE-20-PK', 'attributes' => ['pack_size' => '20', 'format' => 'Soft pack']],
                ['name' => 'Carton (10 packs)', 'sku' => 'CIG-RM-BLUE-20-CTN', 'attributes' => ['pack_size' => '200', 'format' => 'Carton']],
            ],
        ]);

        $this->product([$cigarettes], $creator, [
            'name' => 'Winfield Red Soft Pack 20s',
            'slug' => 'winfield-red-soft-pack-20s',
            'sku' => 'CIG-WF-RED-20',
            'brand' => 'Winfield',
            'tags' => ['best_selling'],
            'specifications' => [
                ['label' => 'Variant', 'value' => 'Red'],
                ['label' => 'Pack Format', 'value' => 'Soft Pack'],
                ['label' => 'Quantity', 'value' => '20 cigarettes'],
                ['label' => 'Market', 'value' => 'Australia'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Full-flavour Winfield red in a soft pack of 20. Core Australian brand for adult smokers who prefer a stronger profile. Age-restricted — 18+ only.',
            'image' => 'images/storefront/product-winfield-blue.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#FEE2E2'],
            'variants' => [
                ['name' => 'Single pack', 'sku' => 'CIG-WF-RED-20-PK', 'attributes' => ['pack_size' => '20', 'format' => 'Soft pack']],
                ['name' => 'Carton (10 packs)', 'sku' => 'CIG-WF-RED-20-CTN', 'attributes' => ['pack_size' => '200', 'format' => 'Carton']],
            ],
        ]);

        $this->product([$cigarettes], $creator, [
            'name' => 'Escort Red Soft Pack 25s',
            'slug' => 'escort-red-soft-pack-25s',
            'sku' => 'CIG-ES-RED-25',
            'brand' => 'Escort',
            'tags' => ['on_offer'],
            'specifications' => [
                ['label' => 'Variant', 'value' => 'Red'],
                ['label' => 'Pack Format', 'value' => 'Soft Pack'],
                ['label' => 'Quantity', 'value' => '25 cigarettes'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Value red cigarettes in a 25s soft pack. Popular larger-pack option for adult smokers watching their spend. Age-restricted — 18+ only.',
            'image_url' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=800&q=80',
            'color_code' => ['mode' => 'solid', 'color' => '#FECACA'],
            'variants' => [
                ['name' => 'Single pack', 'sku' => 'CIG-ES-RED-25-PK', 'attributes' => ['pack_size' => '25', 'format' => 'Soft pack']],
            ],
        ]);

        // ——— Extra heated / specialty tobacco ———
        $this->product([$heated], $creator, [
            'name' => 'Drum Original Hand Rolling Tobacco 50g',
            'slug' => 'drum-original-hand-rolling-tobacco-50g',
            'sku' => 'HTP-DRUM-50G',
            'brand' => 'Drum',
            'tags' => ['popular'],
            'specifications' => [
                ['label' => 'Pack Format', 'value' => 'Pouch'],
                ['label' => 'Quantity', 'value' => '50g'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Classic hand-rolling tobacco pouch. Pair with king-size papers for DIY rollies. Age-restricted — 18+ only.',
            'image' => 'images/storefront/category-tobacco.png',
            'color_code' => ['mode' => 'solid', 'color' => '#D6D3D1'],
            'variants' => [
                ['name' => '50g pouch', 'sku' => 'HTP-DRUM-50G-PK', 'attributes' => ['weight' => '50g', 'format' => 'Pouch']],
            ],
        ]);

        $this->product([$heated], $creator, [
            'name' => 'TEREA Menthol Sticks (Pack of 20)',
            'slug' => 'terea-menthol-sticks-20',
            'sku' => 'HTP-TEREA-MENTH-20',
            'brand' => 'TEREA',
            'tags' => ['new_arrival'],
            'specifications' => [
                ['label' => 'Quantity', 'value' => '20 sticks'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Cool menthol heat-not-burn sticks for IQOS ILUMA. Pack of 20. Age-restricted — 18+ only.',
            'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
            'color_code' => ['mode' => 'solid', 'color' => '#D1FAE5'],
            'variants' => [
                ['name' => 'Turquoise Menthol', 'sku' => 'HTP-TEREA-MENTH-TURQ', 'attributes' => ['flavor' => 'Turquoise Menthol']],
                ['name' => 'Silver Menthol', 'sku' => 'HTP-TEREA-MENTH-SLV', 'attributes' => ['flavor' => 'Silver Menthol']],
            ],
        ]);

        // ——— Extra vapes & disposables ———
        $this->product([$vapes], $creator, [
            'name' => 'IGET Bar Plus Disposable',
            'slug' => 'iget-bar-plus-disposable',
            'sku' => 'VAPE-IGET-BAR-PLUS',
            'brand' => 'IGET',
            'tags' => ['best_selling', 'popular'],
            'specifications' => [
                ['label' => 'Pack Format', 'value' => 'Disposable'],
                ['label' => 'Quantity', 'value' => '~6000 puffs'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'High-capacity disposable bar with mesh coil and draw activation. Popular AU retail format for adult nicotine consumers. Age-restricted — 18+ only.',
            'image' => 'images/storefront/907b72737ecd36dbee5ac6c27fa0b02b906a44ee.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#E0E7FF'],
            'variants' => [
                ['name' => 'Lychee Ice', 'sku' => 'VAPE-IGET-BAR-LYCH', 'attributes' => ['flavor' => 'Lychee Ice', 'nicotine' => '20mg', 'puffs' => '~6000']],
                ['name' => 'Cola Ice', 'sku' => 'VAPE-IGET-BAR-COLA', 'attributes' => ['flavor' => 'Cola Ice', 'nicotine' => '20mg', 'puffs' => '~6000']],
                ['name' => 'Grape Ice', 'sku' => 'VAPE-IGET-BAR-GRP', 'attributes' => ['flavor' => 'Grape Ice', 'nicotine' => '20mg', 'puffs' => '~6000']],
                ['name' => 'Tobacco', 'sku' => 'VAPE-IGET-BAR-TOB', 'attributes' => ['flavor' => 'Tobacco', 'nicotine' => '20mg', 'puffs' => '~6000']],
            ],
        ]);

        $this->product([$vapes], $creator, [
            'name' => 'PuffMeMate Cloud Bar 6000',
            'slug' => 'puffmemate-cloud-bar-6000',
            'sku' => 'POM-CLOUD-6000',
            'brand' => 'PuffMeMate',
            'tags' => ['new_arrival', 'on_offer'],
            'specifications' => [
                ['label' => 'Pack Format', 'value' => 'Disposable'],
                ['label' => 'Quantity', 'value' => '~6000 puffs'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'House-brand Elf Bar–style disposable with ~6000 puffs, USB-C recharge, and soft-touch body. Ready out of the pack for adult users.',
            'image' => 'images/storefront/product-vuse.png',
            'color_code' => ['mode' => 'solid', 'color' => '#FCE7F3'],
            'variants' => [
                ['name' => 'Strawberry Kiwi', 'sku' => 'POM-CLOUD-6000-SK', 'attributes' => ['flavor' => 'Strawberry Kiwi', 'nicotine' => '20mg']],
                ['name' => 'Peach Ice', 'sku' => 'POM-CLOUD-6000-PEACH', 'attributes' => ['flavor' => 'Peach Ice', 'nicotine' => '20mg']],
                ['name' => 'Blueberry Sour', 'sku' => 'POM-CLOUD-6000-BLU', 'attributes' => ['flavor' => 'Blueberry Sour', 'nicotine' => '20mg']],
                ['name' => 'Cool Menthol', 'sku' => 'POM-CLOUD-6000-MENTH', 'attributes' => ['flavor' => 'Cool Menthol', 'nicotine' => '20mg']],
            ],
        ]);

        $this->product([$vapes], $creator, [
            'name' => 'PuffMeMate Soft Touch 3500',
            'slug' => 'puffmemate-soft-touch-3500',
            'sku' => 'POM-SOFT-3500',
            'brand' => 'PuffMeMate',
            'tags' => ['popular'],
            'specifications' => [
                ['label' => 'Pack Format', 'value' => 'Disposable'],
                ['label' => 'Quantity', 'value' => '~3500 puffs'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Mid-size house disposable with soft silicone grip and mesh coil. Mid-range puff count for everyday adult use.',
            'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=800&q=80',
            'color_code' => ['mode' => 'solid', 'color' => '#F3E8FF'],
            'variants' => [
                ['name' => 'Pineapple Ice', 'sku' => 'POM-SOFT-3500-PINE', 'attributes' => ['flavor' => 'Pineapple Ice', 'nicotine' => '20mg']],
                ['name' => 'Apple Peach', 'sku' => 'POM-SOFT-3500-AP', 'attributes' => ['flavor' => 'Apple Peach', 'nicotine' => '20mg']],
                ['name' => 'Classic Tobacco', 'sku' => 'POM-SOFT-3500-TOB', 'attributes' => ['flavor' => 'Classic Tobacco', 'nicotine' => '20mg']],
            ],
        ]);

        $this->product([$vapes], $creator, [
            'name' => 'Vuse ePod 2 Device Kit',
            'slug' => 'vuse-epod-2-device-kit',
            'sku' => 'VAPE-VUSE-EPOD2',
            'brand' => 'Vuse',
            'tags' => ['best_selling', 'new_arrival'],
            'specifications' => [
                ['label' => 'Pack Format', 'value' => 'Device Kit'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Closed-pod Vuse ePod 2 starter kit with USB charging cable. Pair with Vuse ePod pods (sold separately). Age-restricted — 18+ only.',
            'image' => 'images/storefront/product-vuse.png',
            'color_code' => ['mode' => 'solid', 'color' => '#E0F2FE'],
            'variants' => [
                ['name' => 'Black', 'sku' => 'VAPE-VUSE-EPOD2-BLK', 'attributes' => ['color' => 'Black']],
                ['name' => 'Rose Gold', 'sku' => 'VAPE-VUSE-EPOD2-RSG', 'attributes' => ['color' => 'Rose Gold']],
                ['name' => 'Silver', 'sku' => 'VAPE-VUSE-EPOD2-SLV', 'attributes' => ['color' => 'Silver']],
            ],
        ]);

        $this->product([$vapes], $creator, [
            'name' => 'PuffMeMate Max Mesh 8000',
            'slug' => 'puffmemate-max-mesh-8000',
            'sku' => 'POM-MAX-8000',
            'brand' => 'PuffMeMate',
            'tags' => ['on_offer', 'best_selling'],
            'specifications' => [
                ['label' => 'Pack Format', 'value' => 'Disposable'],
                ['label' => 'Quantity', 'value' => '~8000 puffs'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Flagship house disposable with dual mesh coils, ~8000 puffs, and USB-C recharge. Built for heavy adult users who want longevity.',
            'image' => 'images/storefront/b4343f447a376e4db4da6328e10eddefc632ddeb.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#CFFAFE'],
            'variants' => [
                ['name' => 'Watermelon Bubblegum', 'sku' => 'POM-MAX-8000-WM', 'attributes' => ['flavor' => 'Watermelon Bubblegum', 'nicotine' => '20mg']],
                ['name' => 'Mixed Berries', 'sku' => 'POM-MAX-8000-BERRY', 'attributes' => ['flavor' => 'Mixed Berries', 'nicotine' => '20mg']],
                ['name' => 'Lemon Lime', 'sku' => 'POM-MAX-8000-LL', 'attributes' => ['flavor' => 'Lemon Lime', 'nicotine' => '20mg']],
                ['name' => 'Rich Tobacco', 'sku' => 'POM-MAX-8000-TOB', 'attributes' => ['flavor' => 'Rich Tobacco', 'nicotine' => '20mg']],
            ],
        ]);

        $this->product([$vapes], $creator, [
            'name' => 'PuffMeMate Nano Pod Kit',
            'slug' => 'puffmemate-nano-pod-kit',
            'sku' => 'POM-NANO-KIT',
            'brand' => 'PuffMeMate',
            'tags' => ['new_arrival'],
            'specifications' => [
                ['label' => 'Pack Format', 'value' => 'Device Kit'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Ultra-slim closed-pod kit with 800mAh battery and magnetic pods. Includes USB-C cable. Compatible with Nano prefilled pods.',
            'image_url' => 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?auto=format&fit=crop&w=800&q=80',
            'variants' => [
                ['name' => 'Matte Black', 'sku' => 'POM-NANO-KIT-BLK', 'attributes' => ['color' => 'Matte Black']],
                ['name' => 'Pearl White', 'sku' => 'POM-NANO-KIT-WHT', 'attributes' => ['color' => 'Pearl White']],
            ],
        ]);

        // ——— Nicotine pouches ———
        $this->product([$pouches], $creator, [
            'name' => 'Nordic Mint Pouches',
            'slug' => 'nordic-mint-pouches',
            'sku' => 'POUCH-NORDIC-MINT',
            'brand' => 'Nordic',
            'pack_label' => 'Nicotine Pouches',
            'pieces_per_pack' => 20,
            'pack_unit' => 'pcs',
            'tags' => ['best_selling', 'popular'],
            'specifications' => [
                ['label' => 'Format', 'value' => 'Slim tin'],
                ['label' => 'Count', 'value' => '20 pouches'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Tobacco-free nicotine pouches with a clean mint profile. Slim format tin of 20 pouches. Age-restricted — 18+ only.',
            'image' => 'images/catalog/products/nordic-mint-pouches.jpg',
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
            'brand' => 'Nordic',
            'tags' => ['new_arrival'],
            'specifications' => [
                ['label' => 'Format', 'value' => 'Slim tin'],
                ['label' => 'Count', 'value' => '20 pouches'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Tobacco-free nicotine pouches with citrus ice flavour. Slim format tin of 20. Age-restricted — 18+ only.',
            'image' => 'images/catalog/products/citrus-chill-pouches.jpg',
            'variants' => [
                ['name' => '8mg', 'sku' => 'POUCH-CITRUS-8', 'attributes' => ['strength' => '8mg', 'count' => '20']],
                ['name' => '12mg', 'sku' => 'POUCH-CITRUS-12', 'attributes' => ['strength' => '12mg', 'count' => '20']],
            ],
        ]);

        $this->product([$pouches], $creator, [
            'name' => 'VELO Cool Mint Pouches',
            'slug' => 'velo-cool-mint-pouches',
            'sku' => 'POUCH-VELO-MINT',
            'brand' => 'VELO',
            'tags' => ['best_selling', 'popular'],
            'specifications' => [
                ['label' => 'Format', 'value' => 'Slim tin'],
                ['label' => 'Count', 'value' => '20 pouches'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Tobacco-free nicotine pouches with a crisp cool mint profile. Slim tin of 20. Age-restricted — 18+ only.',
            'image' => 'images/storefront/category-pouches.png',
            'color_code' => ['mode' => 'solid', 'color' => '#ECFDF5'],
            'variants' => [
                ['name' => '6mg', 'sku' => 'POUCH-VELO-MINT-6', 'attributes' => ['strength' => '6mg', 'count' => '20']],
                ['name' => '10mg', 'sku' => 'POUCH-VELO-MINT-10', 'attributes' => ['strength' => '10mg', 'count' => '20']],
                ['name' => '14mg', 'sku' => 'POUCH-VELO-MINT-14', 'attributes' => ['strength' => '14mg', 'count' => '20']],
            ],
        ]);

        $this->product([$pouches], $creator, [
            'name' => 'PuffMeMate Spearmint Pouches',
            'slug' => 'puffmemate-spearmint-pouches',
            'sku' => 'POUCH-POM-SPEAR',
            'brand' => 'PuffMeMate',
            'tags' => ['new_arrival', 'on_offer'],
            'specifications' => [
                ['label' => 'Format', 'value' => 'Slim tin'],
                ['label' => 'Count', 'value' => '20 pouches'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'House-brand tobacco-free nicotine pouches with clean spearmint flavour. Slim tin of 20. Age-restricted — 18+ only.',
            'image' => 'images/catalog/products/puffmemate-spearmint-pouches.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#D1FAE5'],
            'variants' => [
                ['name' => '8mg', 'sku' => 'POUCH-POM-SPEAR-8', 'attributes' => ['strength' => '8mg', 'count' => '20']],
                ['name' => '12mg', 'sku' => 'POUCH-POM-SPEAR-12', 'attributes' => ['strength' => '12mg', 'count' => '20']],
            ],
        ]);

        $this->product([$pouches], $creator, [
            'name' => 'Nordic Coffee Pouches',
            'slug' => 'nordic-coffee-pouches',
            'sku' => 'POUCH-NORDIC-COFFEE',
            'brand' => 'Nordic',
            'tags' => ['popular'],
            'specifications' => [
                ['label' => 'Format', 'value' => 'Slim tin'],
                ['label' => 'Count', 'value' => '20 pouches'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => 'Tobacco-free nicotine pouches with roasted coffee notes. Slim tin of 20 for adult use. Age-restricted — 18+ only.',
            'image' => 'images/catalog/products/nordic-coffee-pouches.jpg',
            'color_code' => ['mode' => 'solid', 'color' => '#FEF3C7'],
            'variants' => [
                ['name' => '8mg', 'sku' => 'POUCH-NORDIC-COFFEE-8', 'attributes' => ['strength' => '8mg', 'count' => '20']],
                ['name' => '12mg', 'sku' => 'POUCH-NORDIC-COFFEE-12', 'attributes' => ['strength' => '12mg', 'count' => '20']],
            ],
        ]);

        // ——— Accessories ———
        $this->product([$accessories, $cigarettes], $creator, [
            'name' => 'Clipper Classic Lighter',
            'slug' => 'clipper-classic-lighter',
            'sku' => 'ACC-CLIPPER',
            'brand' => 'Clipper',
            'pack_label' => 'Refillable Lighter',
            'tags' => ['popular'],
            'description' => 'Refillable flint lighter with fixed flame. Reliable counter-side staple for cigarette and cigarillo customers.',
            'image' => 'images/catalog/products/clipper-classic-lighter.jpg',
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
            'brand' => 'RAW',
            'tags' => ['best_selling'],
            'description' => 'Unbleached king-size rolling papers. Pack of 32 leaves. Adult-use product.',
            'image' => 'images/catalog/products/raw-classic-rolling-papers-ks.jpg',
            'variants' => [],
        ]);

        $this->product([$accessories], $creator, [
            'name' => 'USB-C Charge Cable 1m',
            'slug' => 'usb-c-charge-cable-1m',
            'sku' => 'ACC-USBC-1M',
            'brand' => 'PuffMeMate',
            'description' => '1 metre USB-C charging cable compatible with PuffMeMate rechargeable disposables, Core Kit, Air Pro, and IQOS pocket chargers.',
            'image' => 'images/catalog/products/usb-c-charge-cable-1m.jpg',
            'variants' => [],
        ]);

        $this->product([$accessories], $creator, [
            'name' => 'Silicone Device Sleeve',
            'slug' => 'silicone-device-sleeve',
            'sku' => 'ACC-SLEEVE',
            'brand' => 'PuffMeMate',
            'description' => 'Soft silicone sleeve that improves grip and reduces scuffs on Core Kit and Air Pro devices.',
            'image' => 'images/catalog/products/silicone-device-sleeve.jpg',
            'variants' => [
                ['name' => 'Black', 'sku' => 'ACC-SLEEVE-BLK', 'attributes' => ['color' => 'Black']],
                ['name' => 'Clear', 'sku' => 'ACC-SLEEVE-CLR', 'attributes' => ['color' => 'Clear']],
            ],
        ]);

        $this->product([$accessories], $creator, [
            'name' => 'Travel Hard Case',
            'slug' => 'travel-hard-case',
            'sku' => 'ACC-HARD-CASE',
            'brand' => 'PuffMeMate',
            'tags' => ['new_arrival'],
            'description' => 'Compact hard case with foam insert for a disposable or pod kit plus spare pods — reduces pocket lint and accidental fire button presses.',
            'image' => 'images/catalog/products/travel-hard-case.jpg',
            'variants' => [
                ['name' => 'Charcoal', 'sku' => 'ACC-HARD-CASE-CHR', 'attributes' => ['color' => 'Charcoal']],
                ['name' => 'Navy', 'sku' => 'ACC-HARD-CASE-NVY', 'attributes' => ['color' => 'Navy']],
            ],
        ]);

        $this->product([$accessories], $creator, [
            'name' => 'Lanyard Clip',
            'slug' => 'lanyard-clip',
            'sku' => 'ACC-LANYARD',
            'brand' => 'PuffMeMate',
            'description' => 'Detachable lanyard clip for Mini Bar and Pulse disposables — keeps the device handy without pocket wear.',
            'image' => 'images/catalog/products/lanyard-clip.jpg',
            'variants' => [],
        ]);

        $this->product([$accessories, $cigarettes], $creator, [
            'name' => 'Bic Maxi Lighter',
            'slug' => 'bic-maxi-lighter',
            'sku' => 'ACC-BIC-MAXI',
            'brand' => 'Bic',
            'tags' => ['popular'],
            'description' => 'Disposable Maxi lighter with fixed flame. Counter staple for cigarette customers. Adult-use product.',
            'image' => 'images/catalog/products/clipper-classic-lighter.jpg',
            'variants' => [
                ['name' => 'Assorted', 'sku' => 'ACC-BIC-MAXI-AST', 'attributes' => ['color' => 'Assorted']],
            ],
        ]);

        $this->product([$accessories, $heated], $creator, [
            'name' => 'OCB Slim Filter Tips',
            'slug' => 'ocb-slim-filter-tips',
            'sku' => 'ACC-OCB-SLIM',
            'brand' => 'OCB',
            'tags' => ['on_offer'],
            'description' => 'Slim cellulose filter tips for hand-rolled cigarettes. Pack of 120 tips. Adult-use product.',
            'image' => 'images/catalog/products/ocb-slim-filter-tips.jpg',
            'variants' => [],
        ]);

        $this->product([$accessories], $creator, [
            'name' => 'Device Screen Wipe Pack',
            'slug' => 'device-screen-wipe-pack',
            'sku' => 'ACC-SCREEN-WIPE',
            'brand' => 'PuffMeMate',
            'description' => 'Alcohol-free screen and contact wipes for pod devices and IQOS holders. Pack of 20 individually wrapped sheets.',
            'image' => 'images/catalog/products/device-screen-wipe-pack.jpg',
            'variants' => [],
        ]);

        $this->runQueuedSeedSteps(
            'Products',
            fn (int $total): string => sprintf('%d core products ready.', $total),
        );
    }

    /**
     * @param  list<Category>  $categories
     * @param  array<string, mixed>  $data
     */
    private function product(array $categories, ?User $creator, array $data): void
    {
        $this->queueSeedStep(fn () => $this->createProduct($categories, $creator, $data));
    }

    /**
     * @param  list<Category>  $categories
     * @param  array{
     *     name: string,
     *     slug: string,
     *     sku: string,
     *     description: string,
     *     brand?: string|null,
     *     pack_label?: string|null,
     *     pieces_per_pack?: int|null,
     *     pack_unit?: string|null,
     *     tags?: list<string>,
     *     specifications?: list<array{label: string, value: string}>,
     *     image?: string|null,
     *     image_url?: string|null,
     *     color_code?: array<string, mixed>|null,
     *     selling_price?: float|string|null,
     *     regular_price?: float|string|null,
     *     variants: list<array{sku: string, name: string, attributes: array<string, mixed>}>,
     *     gallery_urls?: list<string>
     * }  $data
     */
    private function createProduct(array $categories, ?User $creator, array $data): Product
    {
        $imagePath = $this->resolveImagePath(
            publicRelative: $data['image'] ?? null,
            remoteUrl: $data['image_url'] ?? null,
            catalogDirectory: 'products',
            basename: $data['slug'],
        );

        $prices = $this->catalogPricesFor($data['slug'], $data);

        $product = Product::query()->firstOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'],
                'brand' => $data['brand'] ?? null,
                'pack_label' => $data['pack_label'] ?? null,
                'pieces_per_pack' => $data['pieces_per_pack'] ?? null,
                'pack_unit' => $data['pack_unit'] ?? null,
                'tags' => $data['tags'] ?? [],
                'specifications' => $data['specifications'] ?? [],
                'color_code' => $data['color_code'] ?? null,
                'selling_price' => $prices['selling_price'],
                'regular_price' => $prices['regular_price'],
                'status' => true,
                'created_by' => $creator?->id,
            ],
        );

        $product->forceFill([
            'selling_price' => $prices['selling_price'],
            'regular_price' => $prices['regular_price'],
            'pack_label' => $data['pack_label'] ?? null,
            'pieces_per_pack' => $data['pieces_per_pack'] ?? null,
            'pack_unit' => $data['pack_unit'] ?? null,
        ])->save();

        $this->attachSeedMedia($product, 'image', $imagePath);

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

            $this->attachSeedMedia($product, 'gallery', $path);
        }

        return $product;
    }

    /**
     * Catalog common prices (Sydney flagship ticket). Compare-at is ~15% above selling.
     *
     * @param  array<string, mixed>  $data
     * @return array{selling_price: string|null, regular_price: string|null}
     */
    private function catalogPricesFor(string $slug, array $data): array
    {
        if (array_key_exists('selling_price', $data) || array_key_exists('regular_price', $data)) {
            $selling = $data['selling_price'] ?? null;
            $regular = $data['regular_price'] ?? null;

            if ($selling !== null && $regular === null) {
                $regular = number_format(round((float) $selling * 1.15, 2), 2, '.', '');
            }

            return [
                'selling_price' => $selling === null ? null : number_format((float) $selling, 2, '.', ''),
                'regular_price' => $regular === null ? null : number_format((float) $regular, 2, '.', ''),
            ];
        }

        $selling = match ($slug) {
            'marlboro-gold-soft-pack-20s' => '48.95',
            'winfield-blue-soft-pack-20s' => '46.50',
            'horizon-red-soft-pack-25s' => '52.90',
            'peter-jackson-menthol-20s' => '47.20',
            'iqos-iluma-starter-kit' => '89.00',
            'terea-tobacco-sticks-20' => '28.00',
            'vuse-go-5000-disposable' => '39.95',
            'puffmemate-pulse-5000' => '34.99',
            'puffmemate-mini-bar-1200' => '14.99',
            'puffmemate-core-kit' => '39.99',
            'puffmemate-air-pro' => '44.99',
            'harbor-fruit-blend-60ml' => '24.99',
            'citrus-surge-salt-30ml' => '19.99',
            'vanilla-custard-reserve-60ml' => '24.99',
            'classic-leaf-tobacco-60ml' => '22.99',
            'nordic-mint-pouches' => '18.50',
            'citrus-chill-pouches' => '18.50',
            'core-closed-pods-2-pack' => '12.99',
            'air-pro-mesh-coils-5-pack' => '16.99',
            'clipper-classic-lighter' => '3.50',
            'raw-classic-rolling-papers-ks' => '2.80',
            'usb-c-charge-cable-1m' => '9.99',
            'silicone-device-sleeve' => '7.99',
            'travel-hard-case' => '19.99',
            'lanyard-clip' => '4.99',
            'benson-hedges-smooth-soft-pack-20s' => '49.50',
            'dunhill-fine-cut-soft-pack-20s' => '51.90',
            'rothmans-blue-soft-pack-20s' => '45.80',
            'winfield-red-soft-pack-20s' => '46.50',
            'escort-red-soft-pack-25s' => '49.90',
            'drum-original-hand-rolling-tobacco-50g' => '62.00',
            'terea-menthol-sticks-20' => '28.00',
            'iget-bar-plus-disposable' => '36.99',
            'puffmemate-cloud-bar-6000' => '32.99',
            'puffmemate-soft-touch-3500' => '24.99',
            'vuse-epod-2-device-kit' => '29.95',
            'puffmemate-max-mesh-8000' => '39.99',
            'puffmemate-nano-pod-kit' => '34.99',
            'mango-madness-freebase-60ml' => '24.99',
            'berry-blast-salt-30ml' => '19.99',
            'chocolate-fudge-reserve-60ml' => '24.99',
            'smooth-virginia-tobacco-salt-30ml' => '19.99',
            'velo-cool-mint-pouches' => '19.50',
            'puffmemate-spearmint-pouches' => '16.99',
            'nordic-coffee-pouches' => '18.50',
            'nano-prefilled-pods-2-pack' => '14.99',
            'vuse-epod-refill-pods-2-pack' => '15.95',
            'bic-maxi-lighter' => '2.50',
            'ocb-slim-filter-tips' => '3.20',
            'device-screen-wipe-pack' => '5.99',
            default => null,
        };

        if ($selling === null) {
            return [
                'selling_price' => null,
                'regular_price' => null,
            ];
        }

        return [
            'selling_price' => $selling,
            'regular_price' => number_format(round((float) $selling * 1.15, 2), 2, '.', ''),
        ];
    }
}
