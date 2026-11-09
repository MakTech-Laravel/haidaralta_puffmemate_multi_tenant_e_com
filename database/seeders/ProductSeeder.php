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

    private const ZYN_CHEWABLE_SLUG = 'zyn-nic-chewable-flavored';

    /**
     * Seed catalog products, variants, and gallery images. Depends on CategorySeeder.
     * Products stay unstocked at branches until BranchInventorySeeder runs.
     */
    public function run(): void
    {
        $creator = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first();

        $cigarettes = Category::query()->where('slug', 'cigarettes')->firstOrFail();
        $vapes = Category::query()->where('slug', 'vapes')->firstOrFail();
        $tobacco = Category::query()->where('slug', 'tobacco')->firstOrFail();
        $pouches = Category::query()->where('slug', 'nicotine-pouches')->firstOrFail();

        // The catalog is exactly the owner-approved range of four categories (Cigarettes, Tobacco,
        // Vapes & Disposables, Nicotine Pouches). Every product carries both homepage tags.

        // ——— Cigarettes ———
        $this->product([$cigarettes], $creator, [
            'name' => 'Marlboro Gold Soft Pack 20s',
            'slug' => 'marlboro-gold-soft-pack-20s',
            'sku' => 'CIG-MB-GOLD-20',
            'brand' => 'Marlboro',
            'pack_label' => 'Gold Pack',
            'pieces_per_pack' => 20,
            'pack_unit' => 'Cigarettes',
            'tags' => ['new_arrival', 'best_selling'],
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
            'image' => 'images/catalog/products/marlboro-gold.webp',
            'color_code' => ['mode' => 'solid', 'color' => '#F8E7C1'],
            'variants' => [
                ['name' => 'Single pack', 'sku' => 'CIG-MB-GOLD-20-PK', 'attributes' => ['pack_size' => '20', 'format' => 'Soft pack']],
                ['name' => 'Carton (10 packs)', 'sku' => 'CIG-MB-GOLD-20-CTN', 'attributes' => ['pack_size' => '200', 'format' => 'Carton']],
            ],
        ]);

        foreach ($this->listedCigarettes() as $listed) {
            $this->product([$cigarettes], $creator, $listed);
        }

        // ——— Tobacco ———
        // The Tobacco category holds only the owner-approved range (listedTobacco).
        foreach ($this->listedTobacco() as $listed) {
            $this->product([$tobacco], $creator, $listed);
        }

        // ——— Vapes & disposables ———
        // The Vapes & Disposables root holds only the owner-approved range (listedVapes); every other
        // vape lives in its own root category (Disposables / Pod Systems / Pods & Coils / E-Liquids).
        foreach ($this->listedVapes() as $listed) {
            // The ZYN chewable sits in both Vapes & Disposables and Nicotine Pouches.
            $this->product(
                $listed['slug'] === self::ZYN_CHEWABLE_SLUG ? [$vapes, $pouches] : [$vapes],
                $creator,
                $listed,
            );
        }

        // ——— Nicotine pouches ———
        // The Nicotine Pouches category holds only the owner-approved range (the ZYN chewable above
        // plus listedPouches).
        foreach ($this->listedPouches() as $listed) {
            $this->product([$pouches], $creator, $listed);
        }

        $this->runQueuedSeedSteps(
            'Products',
            fn (int $total): string => sprintf('%d core products ready.', $total),
        );
    }

    /**
     * Owner-approved Nicotine Pouches range besides the ZYN chewable (seeded with listedVapes()
     * because it also belongs to Vapes & Disposables). Selling price is the owner's AUD amount
     * (plain number; the storefront formats it as A$); compare-at is derived (+15%). Stock is added later.
     *
     * @return list<array<string, mixed>>
     */
    private function listedPouches(): array
    {
        $range = [
            ['ZYN or Velo', 'zyn-or-velo', 'ZYN-OR-VELO', '20.00'],
        ];

        return array_map(fn (array $row): array => [
            'name' => $row[0],
            'slug' => $row[1],
            'sku' => 'POUCH-'.$row[2],
            'tags' => ['new_arrival', 'best_selling'],
            'specifications' => [
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => "{$row[0]} Contains nicotine. Age-restricted product — 18+ only.",
            'image' => "images/catalog/products/{$row[1]}.webp",
            'color_code' => ['mode' => 'solid', 'color' => '#ECFDF5'],
            'selling_price' => $row[3],
            'variants' => [
                ['name' => 'Single can', 'sku' => 'POUCH-'.$row[2].'-1', 'attributes' => ['format' => 'Can']],
            ],
        ], $range);
    }

    /**
     * Owner-approved Tobacco range. Selling prices are the owner's AUD amounts (plain numbers; the
     * storefront formats them as A$); compare-at is derived (+15%). Stock is added later.
     *
     * @return list<array<string, mixed>>
     */
    private function listedTobacco(): array
    {
        $range = [
            ['Tobacco (100g)', 'tobacco-100g', 'TOBACCO-100G', null, '100g', '50.00'],
            ['Royal Bourbon Premium RYO(40g)', 'royal-bourbon-premium-ryo-40g', 'ROYAL-BOURBON-RYO-40G', 'Royal Bourbon', '40g', '40.00'],
            ['Violin Premium RYO (40g)', 'violin-premium-ryo-40g', 'VIOLIN-RYO-40G', 'Violin', '40g', '25.00'],
        ];

        return array_map(fn (array $row): array => [
            'name' => $row[0],
            'slug' => $row[1],
            'sku' => 'TOB-'.$row[2],
            'brand' => $row[3],
            'pack_label' => $row[4],
            'tags' => ['new_arrival', 'best_selling'],
            'specifications' => [
                ['label' => 'Weight', 'value' => $row[4]],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => "{$row[0]} Age-restricted product — 18+ only.",
            'image' => "images/catalog/products/{$row[1]}.webp",
            'color_code' => ['mode' => 'solid', 'color' => '#D6D3D1'],
            'selling_price' => $row[5],
            'variants' => [
                ['name' => $row[4].' pouch', 'sku' => 'TOB-'.$row[2].'-PK', 'attributes' => ['weight' => $row[4], 'format' => 'Pouch']],
            ],
        ], $range);
    }

    /**
     * Owner-approved Vapes & Disposables range. Selling prices are the owner's AUD amounts (plain
     * numbers; the storefront formats them as A$); compare-at is derived (+15%). Stock is added later.
     *
     * @return list<array<string, mixed>>
     */
    private function listedVapes(): array
    {
        $range = [
            ['ALIBARBAR INGOT 9000 PUFFS', 'alibarbar-ingot-9000-puffs', 'ALIBARBAR-INGOT-9000', 'Alibarbar', '9000 puffs', '60.00', 'alibarbar-ingot-9000-puffs'],
            ['KUZ NOVA 16000: ULTIMATE HIGH-CAPACITY DISPOSABLE VAPE.', 'kuz-nova-16000', 'KUZ-NOVA-16000', 'KUZ', '16000 puffs', '65.00', 'kuz-nova-16000'],
            ['KUZ PEARL 30000 PUFFS', 'kuz-pearl-30000-puffs', 'KUZ-PEARL-30000', 'KUZ', '30000 puffs', '75.00', 'kuz-pearl-30000-puffs'],
            ['IGET ONE 12000', 'iget-one-12000', 'IGET-ONE-12000', 'IGET', '12000 puffs', '75.00', 'iget-one-12000'],
            ['IGET BAR PRO 10K', 'iget-bar-pro-10k', 'IGET-BAR-PRO-10K', 'IGET', '10000 puffs', '60.00', 'iget-bar-pro-10k'],
            ['ZYN NIC CHEWABLE FLAVORED PRACTICAL AND SMOKE-FREE', 'zyn-nic-chewable-flavored', 'ZYN-NIC-CHEWABLE', 'ZYN', 'Smoke-free', '25.00', 'zyn-nic-chewable'],
        ];

        return array_map(fn (array $row): array => [
            'name' => $row[0],
            'slug' => $row[1],
            'sku' => 'VAPE-'.$row[2],
            'brand' => $row[3],
            'pack_label' => $row[4],
            'tags' => ['new_arrival', 'best_selling'],
            'specifications' => [
                ['label' => 'Variant', 'value' => $row[4]],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => "{$row[0]} Age-restricted product — 18+ only.",
            'image' => "images/catalog/products/{$row[6]}.webp",
            'color_code' => ['mode' => 'solid', 'color' => '#E8F0FE'],
            'selling_price' => $row[5],
            'variants' => [
                ['name' => 'Single unit', 'sku' => 'VAPE-'.$row[2].'-1', 'attributes' => ['format' => $row[4]]],
            ],
        ], $range);
    }

    /**
     * Owner-approved Cigarettes range (besides Marlboro Gold Soft Pack 20s above). Selling prices are the
     * owner's AUD amounts (stored as plain numbers; the storefront formats them as A$); compare-at is
     * derived (+15%). Stock is added later per branch.
     *
     * @return list<array<string, mixed>>
     */
    private function listedCigarettes(): array
    {
        $range = [
            ['Double Happiness (Soft Pack)', 'double-happiness-soft-pack', 'DH-SP', 'Double Happiness', 'Soft Pack', 'Soft Pack', '140.00', 'double-happiness'],
            ['Manchester Special Edition', 'manchester-special-edition', 'MC-SE', 'Manchester', 'Special Edition', 'Standard', '150.00', 'manchester-special-edition'],
            ['Manchester Light', 'manchester-light', 'MC-LT', 'Manchester', 'Light', 'Standard', '150.00', 'manchester-light'],
            ['Manchester Royal Red', 'manchester-royal-red', 'MC-RR', 'Manchester', 'Royal Red', 'Standard', '140.00', 'manchester-royal-red'],
            ['Manchester Double Drive', 'manchester-double-drive', 'MC-DD', 'Manchester', 'Double Drive', 'Standard', '170.00', 'manchester-double-drive'],
            ['Manchester Sapphire Blue', 'manchester-sapphire-blue', 'MC-SB', 'Manchester', 'Sapphire Blue', 'Standard', '160.00', 'manchester-sapphire-blue'],
            ['Manchester Red', 'manchester-red', 'MC-RD', 'Manchester', 'Red', 'Standard', '140.00', 'manchester-red'],
            ['Marlboro Red', 'marlboro-red', 'MB-RED', 'Marlboro', 'Red', 'Standard', '140.00', 'marlboro-red'],
            ['Marlboro Ice Blast', 'marlboro-ice-blast', 'MB-ICE', 'Marlboro', 'Ice Blast', 'Standard', '160.00', 'marlboro-ice-blast'],
            ['Benson & Hedges Special Filter', 'benson-hedges-special-filter', 'BH-SF', 'Benson & Hedges', 'Special Filter', 'Standard', '150.00', 'benson-hedges-special-filter'],
            ['Dunhill Blue', 'dunhill-blue', 'DH-BLUE', 'Dunhill', 'Blue', 'Standard', '150.00', 'dunhill-blue'],
            ['MAC', 'mac', 'MAC', 'MAC', 'Original', 'Standard', '140.00', 'mac'],
            ['Kent Switch', 'kent-switch', 'KT-SW', 'Kent', 'Switch', 'Standard', '150.00', 'kent-switch'],
            ['Eights (8mg)', 'eights-8mg', 'EI-8MG', 'Eights', '8mg', 'Standard', '155.00', 'eights-8mg'],
        ];

        return array_map(fn (array $row): array => [
            'name' => $row[0],
            'slug' => $row[1],
            'sku' => 'CIG-'.$row[2],
            'brand' => $row[3],
            'pack_label' => $row[4],
            'pieces_per_pack' => 20,
            'pack_unit' => 'Cigarettes',
            'tags' => ['new_arrival', 'best_selling'],
            'specifications' => [
                ['label' => 'Variant', 'value' => $row[4]],
                ['label' => 'Pack Format', 'value' => $row[5]],
                ['label' => 'Quantity', 'value' => '20 cigarettes'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'description' => "{$row[0]} cigarettes. Age-restricted product — 18+ only.",
            'image' => "images/catalog/products/{$row[7]}.webp",
            'color_code' => ['mode' => 'solid', 'color' => '#F1F5F9'],
            'selling_price' => $row[6],
            'variants' => [
                ['name' => 'Single pack', 'sku' => 'CIG-'.$row[2].'-PK', 'attributes' => ['pack_size' => '20', 'format' => $row[5]]],
            ],
        ], $range);
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
            'marlboro-gold-soft-pack-20s' => '140.00',
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
