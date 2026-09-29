<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Database\Seeders\Concerns\SeedsCatalogMedia;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @phpstan-type CatalogItem array{
 *     name: string,
 *     slug: string,
 *     sku: string,
 *     brand: string,
 *     categories: list<string>,
 *     description: string,
 *     specifications: list<array{label: string, value: string}>,
 *     image: string,
 *     selling_price: string,
 *     pack_label: string|null,
 *     pieces_per_pack: int|null,
 *     pack_unit: string|null
 * }
 */
class ExtendedProductSeeder extends Seeder
{
    use ReportsSeedProgress;
    use SeedsCatalogMedia;
    use WithoutModelEvents;

    private const CIGARETTE_IMAGES = [
        'Marlboro' => 'images/storefront/product-marlboro-gold.jpg',
        'Winfield' => 'images/storefront/product-winfield-blue.jpg',
        'Horizon' => 'images/catalog/products/horizon-red-soft-pack-25s.jpg',
        'Peter Jackson' => 'images/catalog/products/peter-jackson-menthol-20s.jpg',
        'Escort' => 'images/catalog/products/escort-red-soft-pack-25s.jpg',
    ];

    private const DISPOSABLE_IMAGES = [
        'images/storefront/product-vuse.png',
        'images/catalog/products/puffmemate-pulse-5000.jpg',
        'images/catalog/products/puffmemate-mini-bar-1200.jpg',
        'images/catalog/products/puffmemate-soft-touch-3500.jpg',
    ];

    private const POD_KIT_IMAGES = [
        'images/catalog/products/puffmemate-core-kit.jpg',
        'images/catalog/products/puffmemate-air-pro.jpg',
        'images/catalog/products/puffmemate-nano-pod-kit.jpg',
    ];

    private const POD_IMAGES = [
        'images/catalog/products/core-closed-pods-2-pack.jpg',
        'images/catalog/products/nano-prefilled-pods-2-pack.jpg',
        'images/catalog/products/air-pro-mesh-coils-5-pack.jpg',
    ];

    private const E_LIQUID_IMAGES = [
        'images/catalog/products/harbor-fruit-blend-60ml.jpg',
        'images/catalog/products/citrus-surge-salt-30ml.jpg',
        'images/catalog/products/berry-blast-salt-30ml.jpg',
        'images/catalog/products/mango-madness-freebase-60ml.jpg',
        'images/catalog/products/vanilla-custard-reserve-60ml.jpg',
        'images/catalog/products/chocolate-fudge-reserve-60ml.jpg',
        'images/catalog/products/smooth-virginia-tobacco-salt-30ml.jpg',
        'images/catalog/products/classic-leaf-tobacco-60ml.jpg',
    ];

    private const POUCH_IMAGES = [
        'images/catalog/products/nordic-mint-pouches.jpg',
        'images/catalog/products/citrus-chill-pouches.jpg',
        'images/catalog/products/puffmemate-spearmint-pouches.jpg',
        'images/catalog/products/nordic-coffee-pouches.jpg',
    ];

    private const TOBACCO_IMAGE = 'images/storefront/category-tobacco.png';

    /**
     * @var array<string, string|null>
     */
    private array $resolvedImages = [];

    /**
     * Seed ~600 real-world AU retail catalog items on top of ProductSeeder's hand-written set.
     * Built deterministically from handwritten brand / line / flavour / pack-size lists (no Faker),
     * so re-running is idempotent and every environment gets the same catalog.
     */
    public function run(): void
    {
        $creator = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first();
        $categoryIds = Category::query()->pluck('id', 'slug');
        $items = $this->catalog();

        $this->startSeedProgress('Products', $items->count());

        foreach ($items as $item) {
            $this->product($item, $categoryIds, $creator);
            $this->advanceSeedProgress();
        }

        $this->finishSeedProgress(sprintf(
            '%d extended products ready (%d products in catalog).',
            $items->count(),
            Product::query()->count(),
        ));
    }

    /**
     * @return Collection<int, CatalogItem>
     */
    private function catalog(): Collection
    {
        return collect([
            ...$this->cigarettes(),
            ...$this->rollingTobacco(),
            ...$this->heatedTobacco(),
            ...$this->cigars(),
            ...$this->pipeTobacco(),
            ...$this->disposables(),
            ...$this->podKits(),
            ...$this->podsAndCoils(),
            ...$this->eLiquids(),
            ...$this->pouches(),
            ...$this->lighters(),
            ...$this->papersAndFilters(),
            ...$this->rollingMachines(),
            ...$this->casesAndStorage(),
            ...$this->batteriesAndChargers(),
        ])->keyBy('slug')->values();
    }

    /**
     * @param  CatalogItem  $item
     * @param  Collection<string, int>  $categoryIds
     */
    private function product(array $item, Collection $categoryIds, ?User $creator): void
    {
        $product = Product::query()->firstOrCreate(
            ['slug' => $item['slug']],
            [
                'name' => $item['name'],
                'sku' => $item['sku'],
                'description' => $item['description'],
                'brand' => $item['brand'],
                'pack_label' => $item['pack_label'],
                'pieces_per_pack' => $item['pieces_per_pack'],
                'pack_unit' => $item['pack_unit'],
                'tags' => $this->tagsFor($item['slug']),
                'specifications' => $item['specifications'],
                'image' => $this->image($item['image']),
                'selling_price' => $item['selling_price'],
                'regular_price' => number_format(round((float) $item['selling_price'] * 1.15, 2), 2, '.', ''),
                'status' => true,
                'created_by' => $creator?->id,
            ],
        );

        $product->categories()->syncWithoutDetaching(
            collect($item['categories'])
                ->map(fn (string $slug): ?int => $categoryIds[$slug] ?? null)
                ->filter()
                ->values()
                ->all(),
        );
    }

    /**
     * @return list<CatalogItem>
     */
    private function cigarettes(): array
    {
        $brands = [
            ['brand' => 'Marlboro', 'tier' => 'premium', 'base' => 49.50, 'shades' => ['Red', 'Gold', 'Fine Touch'], 'sizes' => [20, 25], 'note' => 'American blend with a firm filter and a full, rounded draw.'],
            ['brand' => 'Benson & Hedges', 'tier' => 'premium', 'base' => 48.90, 'shades' => ['Classic', 'Smooth', 'Ultimate'], 'sizes' => [20, 25], 'note' => 'Classic Virginia-led blend with a smooth, even burn.'],
            ['brand' => 'Dunhill', 'tier' => 'premium', 'base' => 51.00, 'shades' => ['Distinct', 'Infinite'], 'sizes' => [20, 25], 'note' => 'Premium tobaccos finished with a refined filter.'],
            ['brand' => 'Peter Stuyvesant', 'tier' => 'premium', 'base' => 47.90, 'shades' => ['Classic', 'Blue'], 'sizes' => [20, 25, 40], 'note' => 'International blend with a light, balanced character.'],
            ['brand' => 'Camel', 'tier' => 'premium', 'base' => 47.50, 'shades' => ['Original', 'Blue'], 'sizes' => [20, 25], 'note' => 'Turkish and American tobaccos blended for a warm finish.'],
            ['brand' => 'Winfield', 'tier' => 'value', 'base' => 45.90, 'shades' => ['Blue', 'Red', 'Gold', 'Sky Blue', 'Grey', 'Optimum Blue'], 'sizes' => [25, 30, 40], 'note' => 'Australia\'s everyday classic with a dependable, familiar taste.'],
            ['brand' => 'Rothmans', 'tier' => 'value', 'base' => 44.90, 'shades' => ['Red', 'Blue', 'Silver'], 'sizes' => [25, 30, 40], 'note' => 'Straightforward Virginia blend at an everyday price.'],
            ['brand' => 'Pall Mall', 'tier' => 'value', 'base' => 42.90, 'shades' => ['Red', 'Blue', 'Amber'], 'sizes' => [20, 25, 30], 'note' => 'Value-focused blend with a consistent draw.'],
            ['brand' => 'JPS', 'tier' => 'value', 'base' => 41.90, 'shades' => ['Red', 'Blue', 'Grey'], 'sizes' => [25, 30, 40], 'note' => 'Bold value blend packed for longer-lasting packs.'],
            ['brand' => 'Horizon', 'tier' => 'value', 'base' => 41.50, 'shades' => ['Red', 'Blue', 'Grey'], 'sizes' => [20, 30, 40], 'note' => 'Budget-friendly blend with a clean finish.'],
            ['brand' => 'Longbeach', 'tier' => 'value', 'base' => 44.50, 'shades' => ['Original', 'Blue', 'Fine'], 'sizes' => [25, 30, 40], 'note' => 'Long-standing Aussie favourite with a mellow profile.'],
            ['brand' => 'Holiday', 'tier' => 'value', 'base' => 44.00, 'shades' => ['Red', 'Blue'], 'sizes' => [25, 40], 'note' => 'Easygoing blend in popular larger formats.'],
            ['brand' => 'Peter Jackson', 'tier' => 'value', 'base' => 43.90, 'shades' => ['Rich', 'Original', 'Blue'], 'sizes' => [25, 30, 40], 'note' => 'Full-flavoured blend with a steady burn.'],
            ['brand' => 'Bond Street', 'tier' => 'value', 'base' => 40.90, 'shades' => ['Red', 'Blue', 'Silver'], 'sizes' => [25, 40], 'note' => 'No-frills blend built for value.'],
            ['brand' => 'Parker & Simpson', 'tier' => 'value', 'base' => 39.90, 'shades' => ['Red', 'Blue', 'Silver'], 'sizes' => [20, 25, 30], 'note' => 'Crisp, simple blend at an entry price.'],
            ['brand' => 'Choice', 'tier' => 'value', 'base' => 38.90, 'shades' => ['Red', 'Blue'], 'sizes' => [25, 30, 40], 'note' => 'Economy blend with a consistent pack-to-pack taste.'],
            ['brand' => 'Chesterfield', 'tier' => 'value', 'base' => 40.50, 'shades' => ['Red', 'Blue'], 'sizes' => [20, 25, 30], 'note' => 'Heritage American-style blend at a value price.'],
            ['brand' => 'Philip Morris', 'tier' => 'value', 'base' => 39.50, 'shades' => ['Red', 'Blue', 'Gold'], 'sizes' => [20, 25, 30], 'note' => 'Balanced everyday blend with a firm filter.'],
            ['brand' => 'Escort', 'tier' => 'value', 'base' => 39.90, 'shades' => ['Blue', 'Grey'], 'sizes' => [25, 30], 'note' => 'Light value blend in a slim-profile pack.'],
            ['brand' => 'Stradbroke', 'tier' => 'value', 'base' => 38.50, 'shades' => ['Red', 'Blue'], 'sizes' => [25, 40], 'note' => 'Entry-level blend in large-format packs.'],
            ['brand' => 'Lucky Strike', 'tier' => 'value', 'base' => 45.00, 'shades' => ['Red', 'Blue'], 'sizes' => [20, 25], 'note' => 'Toasted American blend with a bright finish.'],
        ];

        $items = [];

        foreach ($brands as $brand) {
            foreach ($brand['shades'] as $shadeIndex => $shade) {
                foreach ($brand['sizes'] as $size) {
                    $categories = ['cigarettes', $brand['tier'] === 'premium' ? 'premium-cigarettes' : 'value-cigarettes'];

                    if ($size >= 30) {
                        $categories[] = 'large-packs';
                    }

                    $items[] = $this->item(
                        name: "{$brand['brand']} {$shade} {$size}s",
                        brand: $brand['brand'],
                        categories: $categories,
                        price: $this->retailPrice($brand['base'] * (($size / 20) ** 0.95) + ($shadeIndex * 0.40)),
                        image: self::CIGARETTE_IMAGES[$brand['brand']] ?? $this->pick(array_values(self::CIGARETTE_IMAGES), $brand['brand']),
                        description: "{$brand['brand']} {$shade} in a {$size}-stick pack. {$brand['note']} Supplied in Australian plain packaging. Age-restricted product — 18+ only.",
                        specifications: [
                            ['label' => 'Variant', 'value' => $shade],
                            ['label' => 'Pack Size', 'value' => "{$size} cigarettes"],
                            ['label' => 'Filter', 'value' => 'Standard filter'],
                            ['label' => 'Packaging', 'value' => 'Australian compliant plain packaging'],
                        ],
                        packLabel: "{$shade} Pack",
                        piecesPerPack: $size,
                        packUnit: 'Cigarettes',
                    );
                }
            }
        }

        return $items;
    }

    /**
     * @return list<CatalogItem>
     */
    private function rollingTobacco(): array
    {
        $basePrices = [25 => 62.00, 30 => 73.00, 50 => 118.00];
        $brands = [
            ['brand' => 'Port Royal', 'blends' => ['Original', 'Smooth'], 'sizes' => [25, 30, 50], 'offset' => 1.50, 'note' => 'Medium-cut Virginia and burley blend that rolls evenly.'],
            ['brand' => 'Champion', 'blends' => ['Legendary', 'Ruby'], 'sizes' => [25, 30, 50], 'offset' => 0.00, 'note' => 'Moist fine-cut tobacco with a rich, rounded flavour.'],
            ['brand' => 'White Ox', 'blends' => ['Original', 'Smooth'], 'sizes' => [25, 30, 50], 'offset' => 2.50, 'note' => 'Premium fine-cut with a full-bodied character.'],
            ['brand' => 'Drum', 'blends' => ['Original', 'Blue'], 'sizes' => [25, 30], 'offset' => 2.00, 'note' => 'Dutch-style shag with a mild, natural taste.'],
            ['brand' => 'Bond Street', 'blends' => ['Original', 'Smooth'], 'sizes' => [25, 30, 50], 'offset' => -3.00, 'note' => 'Value fine-cut that holds moisture well in the pouch.'],
            ['brand' => 'Winfield', 'blends' => ['Original', 'Gold'], 'sizes' => [25, 30, 50], 'offset' => -1.00, 'note' => 'Familiar Winfield flavour in a roll-your-own format.'],
            ['brand' => 'JPS', 'blends' => ['Original', 'Smooth'], 'sizes' => [25, 30], 'offset' => -2.50, 'note' => 'Bold fine-cut at an everyday price.'],
            ['brand' => 'Riverstone', 'blends' => ['Original'], 'sizes' => [25, 30, 50], 'offset' => -4.00, 'note' => 'Economy fine-cut built for value rollers.'],
            ['brand' => 'Golden Virginia', 'blends' => ['Original', 'Classic'], 'sizes' => [25, 30, 50], 'offset' => 3.00, 'note' => 'Golden Virginia leaf with a naturally sweet note.'],
            ['brand' => 'Rothmans', 'blends' => ['Original'], 'sizes' => [25, 30], 'offset' => -1.50, 'note' => 'Straightforward fine-cut with a steady burn.'],
            ['brand' => 'Holiday', 'blends' => ['Original'], 'sizes' => [25, 50], 'offset' => -2.00, 'note' => 'Easy-rolling fine-cut in value sizes.'],
            ['brand' => 'Peter Jackson', 'blends' => ['Original'], 'sizes' => [25, 30], 'offset' => -1.00, 'note' => 'Full-flavoured fine-cut from a trusted name.'],
        ];

        $items = [];

        foreach ($brands as $brand) {
            foreach ($brand['blends'] as $blend) {
                foreach ($brand['sizes'] as $grams) {
                    $items[] = $this->item(
                        name: "{$brand['brand']} {$blend} Rolling Tobacco {$grams}g",
                        brand: $brand['brand'],
                        categories: ['tobacco', 'rolling-tobacco'],
                        price: $this->retailPrice($basePrices[$grams] + ($brand['offset'] * $grams / 25)),
                        image: self::TOBACCO_IMAGE,
                        description: "{$brand['brand']} {$blend} fine-cut rolling tobacco in a {$grams}g resealable pouch. {$brand['note']} Age-restricted product — 18+ only.",
                        specifications: [
                            ['label' => 'Blend', 'value' => $blend],
                            ['label' => 'Weight', 'value' => "{$grams}g"],
                            ['label' => 'Cut', 'value' => 'Fine cut'],
                            ['label' => 'Packaging', 'value' => 'Australian compliant plain packaging'],
                        ],
                        packLabel: "{$grams}g Pouch",
                        piecesPerPack: $grams,
                        packUnit: 'Grams',
                    );
                }
            }
        }

        return $items;
    }

    /**
     * @return list<CatalogItem>
     */
    private function heatedTobacco(): array
    {
        $items = [];
        $sticks = ['Amber', 'Sienna', 'Turquoise', 'Russet', 'Yellow', 'Silver', 'Bronze', 'Teak', 'Blue'];

        foreach ($sticks as $shade) {
            foreach ([['label' => '20 Sticks', 'count' => 20, 'price' => '28.00'], ['label' => 'Carton 200 Sticks', 'count' => 200, 'price' => '265.00']] as $format) {
                $items[] = $this->item(
                    name: "TEREA {$shade} {$format['label']}",
                    brand: 'TEREA',
                    categories: ['tobacco', 'heated-tobacco'],
                    price: $format['price'],
                    image: $this->pick(['images/catalog/products/terea-tobacco-sticks-20.jpg', 'images/catalog/products/terea-menthol-sticks-20.jpg'], $shade),
                    description: "TEREA {$shade} tobacco sticks for IQOS ILUMA devices ({$format['count']} sticks). Heated, not burned — no ash. Age-restricted product — 18+ only.",
                    specifications: [
                        ['label' => 'Compatible With', 'value' => 'IQOS ILUMA series'],
                        ['label' => 'Sticks', 'value' => (string) $format['count']],
                        ['label' => 'Technology', 'value' => 'SMARTCORE induction'],
                    ],
                    packLabel: $format['count'] === 20 ? 'Pack' : 'Carton',
                    piecesPerPack: $format['count'],
                    packUnit: 'Sticks',
                );
            }
        }

        $devices = [
            ['model' => 'IQOS ILUMA ONE', 'brand' => 'IQOS', 'price' => '69.00', 'colours' => ['Pebble Grey', 'Azure Blue', 'Moss Green', 'Sunset Red'], 'note' => 'All-in-one heated tobacco device, up to 20 uses per charge.'],
            ['model' => 'IQOS ILUMA', 'brand' => 'IQOS', 'price' => '119.00', 'colours' => ['Pebble Grey', 'Azure Blue', 'Moss Green', 'Sunset Red'], 'note' => 'Holder-and-charger kit with blade-free SMARTCORE induction.'],
            ['model' => 'IQOS ILUMA PRIME', 'brand' => 'IQOS', 'price' => '179.00', 'colours' => ['Jade Green', 'Obsidian Black', 'Bronze Taupe', 'Golden Khaki'], 'note' => 'Premium ILUMA kit with an aluminium body and wrap.'],
            ['model' => 'glo Hyper X2', 'brand' => 'glo', 'price' => '49.00', 'colours' => ['Black', 'Blue', 'White'], 'note' => 'Compact heated tobacco device with boost mode.'],
        ];

        foreach ($devices as $device) {
            foreach ($device['colours'] as $colour) {
                $items[] = $this->item(
                    name: "{$device['model']} Kit {$colour}",
                    brand: $device['brand'],
                    categories: ['tobacco', 'heated-tobacco'],
                    price: $device['price'],
                    image: $this->pick(['images/storefront/product-iqos.png', 'images/catalog/gallery/iqos-iluma-starter-kit-gallery-1.jpg'], $colour),
                    description: "{$device['model']} in {$colour}. {$device['note']} Includes USB-C cable. Age-restricted product — 18+ only.",
                    specifications: [
                        ['label' => 'Colour', 'value' => $colour],
                        ['label' => 'Charging', 'value' => 'USB-C'],
                        ['label' => 'Warranty', 'value' => '12 months'],
                    ],
                    packLabel: 'Kit',
                    piecesPerPack: 1,
                    packUnit: 'Device',
                );
            }
        }

        return $items;
    }

    /**
     * @return list<CatalogItem>
     */
    private function cigars(): array
    {
        $cigars = [
            ['Café Crème Original Tin 10', 'Café Crème', '32.90', 10],
            ['Café Crème Original Tin 20', 'Café Crème', '62.90', 20],
            ['Café Crème Blue Tin 10', 'Café Crème', '32.90', 10],
            ['Café Crème Arome Tin 10', 'Café Crème', '33.90', 10],
            ['Café Crème Red Tin 10', 'Café Crème', '33.90', 10],
            ['Davidoff Mini Cigarillos Gold 20', 'Davidoff', '52.90', 20],
            ['Davidoff Mini Cigarillos Silver 20', 'Davidoff', '52.90', 20],
            ['Davidoff Mini Cigarillos Platinum 20', 'Davidoff', '56.90', 20],
            ['Davidoff Mini Cigarillos Nicaragua 20', 'Davidoff', '56.90', 20],
            ['Villiger Premium No.7 Sumatra 5', 'Villiger', '24.90', 5],
            ['Villiger Export Pressed 5', 'Villiger', '26.90', 5],
            ['Villiger Mini Sumatra 20', 'Villiger', '38.90', 20],
            ['Handelsgold Classic Tip 5', 'Handelsgold', '18.90', 5],
            ['Cohiba Club 10', 'Cohiba', '79.00', 10],
            ['Cohiba Mini 20', 'Cohiba', '96.00', 20],
            ['Cohiba Short 10', 'Cohiba', '69.00', 10],
            ['Montecristo Club 20', 'Montecristo', '89.00', 20],
            ['Montecristo Mini 20', 'Montecristo', '72.00', 20],
            ['Montecristo No.4 Single', 'Montecristo', '42.00', 1],
            ['Romeo y Julieta Mini 20', 'Romeo y Julieta', '68.00', 20],
            ['Romeo y Julieta Club 10', 'Romeo y Julieta', '49.00', 10],
            ['Romeo y Julieta Short Churchill Single', 'Romeo y Julieta', '48.00', 1],
            ['Partagás Club 20', 'Partagás', '86.00', 20],
            ['Partagás Serie D No.4 Single', 'Partagás', '58.00', 1],
            ['Toscano Classico 5', 'Toscano', '34.90', 5],
            ['Toscano Antico 5', 'Toscano', '39.90', 5],
            ['King Edward Imperial 5', 'King Edward', '22.90', 5],
            ['Punch Club 20', 'Punch', '74.00', 20],
            ['H. Upmann Half Corona 5', 'H. Upmann', '145.00', 5],
            ['Bolivar Coronas Junior 5', 'Bolivar', '129.00', 5],
        ];

        return array_map(fn (array $cigar): array => $this->item(
            name: $cigar[0],
            brand: $cigar[1],
            categories: ['tobacco', 'cigars-cigarillos'],
            price: $cigar[2],
            image: self::TOBACCO_IMAGE,
            description: "{$cigar[0]} from {$cigar[1]}. Hand-selected for a consistent draw and even burn — store in a humidor or cool dry place. Age-restricted product — 18+ only.",
            specifications: [
                ['label' => 'Quantity', 'value' => $cigar[3] === 1 ? 'Single cigar' : "{$cigar[3]} pieces"],
                ['label' => 'Storage', 'value' => '65–72% humidity'],
            ],
            packLabel: $cigar[3] === 1 ? 'Single' : 'Pack',
            piecesPerPack: $cigar[3],
            packUnit: 'Cigars',
        ), $cigars);
    }

    /**
     * @return list<CatalogItem>
     */
    private function pipeTobacco(): array
    {
        $blends = [
            ['Borkum Riff Original 50g', 'Borkum Riff', '112.00'],
            ['Borkum Riff Whiskey 50g', 'Borkum Riff', '114.00'],
            ['Borkum Riff Ready Rubbed 50g', 'Borkum Riff', '112.00'],
            ['Erinmore Flake 50g', 'Erinmore', '118.00'],
            ['Erinmore Mixture 50g', 'Erinmore', '116.00'],
            ['Amphora Full Aroma 50g', 'Amphora', '109.00'],
            ['Amphora Mild Cavendish 50g', 'Amphora', '109.00'],
            ['Captain Black Original 42g', 'Captain Black', '98.00'],
            ['Captain Black Gold 42g', 'Captain Black', '98.00'],
            ['Peterson Irish Flake 50g', 'Peterson', '124.00'],
            ['Peterson Old Dublin 50g', 'Peterson', '124.00'],
            ['Mac Baren Navy Flake 50g', 'Mac Baren', '119.00'],
            ['Mac Baren Scottish Mixture 50g', 'Mac Baren', '117.00'],
            ['Condor Ready Rubbed 50g', 'Condor', '105.00'],
            ['Holger Danske Black & Bourbon 50g', 'Holger Danske', '115.00'],
        ];

        return array_map(fn (array $blend): array => $this->item(
            name: $blend[0].' Pipe Tobacco',
            brand: $blend[1],
            categories: ['tobacco', 'pipe-tobacco'],
            price: $blend[2],
            image: self::TOBACCO_IMAGE,
            description: "{$blend[0]} pipe tobacco from {$blend[1]}. Slow-burning blend for a cool, relaxed smoke. Age-restricted product — 18+ only.",
            specifications: [
                ['label' => 'Format', 'value' => 'Pipe tobacco pouch'],
                ['label' => 'Packaging', 'value' => 'Australian compliant plain packaging'],
            ],
            packLabel: 'Pouch',
            piecesPerPack: null,
            packUnit: null,
        ), $blends);
    }

    /**
     * @return list<CatalogItem>
     */
    private function disposables(): array
    {
        $flavours = [
            'Watermelon Ice', 'Grape Ice', 'Strawberry Kiwi', 'Mango Ice', 'Blueberry Raspberry', 'Peach Ice',
            'Lychee Ice', 'Pineapple Coconut', 'Double Apple', 'Banana Ice', 'Cola Ice', 'Mixed Berries',
            'Passionfruit Guava', 'Cool Mint', 'Classic Tobacco', 'Strawberry Watermelon',
        ];

        $models = [
            ['brand' => 'IGET', 'model' => 'Bar Plus', 'puffs' => 6000, 'price' => '32.99', 'count' => 10],
            ['brand' => 'IGET', 'model' => 'Legend', 'puffs' => 4000, 'price' => '29.99', 'count' => 8],
            ['brand' => 'IGET', 'model' => 'King', 'puffs' => 2600, 'price' => '24.99', 'count' => 6],
            ['brand' => 'Alibarbar', 'model' => 'Ingot', 'puffs' => 9000, 'price' => '38.99', 'count' => 8],
            ['brand' => 'HQD', 'model' => 'Cuvie Plus', 'puffs' => 1200, 'price' => '19.99', 'count' => 8],
            ['brand' => 'Elf Bar', 'model' => 'BC5000', 'puffs' => 5000, 'price' => '34.99', 'count' => 8],
            ['brand' => 'Lost Mary', 'model' => 'OS5000', 'puffs' => 5000, 'price' => '34.99', 'count' => 8],
            ['brand' => 'Gunnpod', 'model' => 'Classic', 'puffs' => 2000, 'price' => '22.99', 'count' => 6],
            ['brand' => 'WAKA', 'model' => 'soPro PA10000', 'puffs' => 10000, 'price' => '42.99', 'count' => 6],
            ['brand' => 'KUZ', 'model' => 'Lux', 'puffs' => 9000, 'price' => '39.99', 'count' => 6],
            ['brand' => 'Vozol', 'model' => 'Gear', 'puffs' => 10000, 'price' => '44.99', 'count' => 6],
            ['brand' => 'RandM', 'model' => 'Tornado', 'puffs' => 7000, 'price' => '36.99', 'count' => 6],
            ['brand' => 'Geek Bar', 'model' => 'Pulse', 'puffs' => 15000, 'price' => '49.99', 'count' => 6],
        ];

        $items = [];

        foreach ($models as $modelIndex => $model) {
            for ($offset = 0; $offset < $model['count']; $offset++) {
                $flavour = $flavours[($modelIndex * 3 + $offset) % count($flavours)];

                $items[] = $this->item(
                    name: "{$model['brand']} {$model['model']} {$model['puffs']} {$flavour}",
                    brand: $model['brand'],
                    categories: ['vapes', 'disposables'],
                    price: $model['price'],
                    image: $this->pick(self::DISPOSABLE_IMAGES, $model['brand'].$flavour),
                    description: "{$model['brand']} {$model['model']} disposable in {$flavour}. Up to {$model['puffs']} puffs, pre-filled and pre-charged — no refills or buttons. Contains nicotine. 18+ only.",
                    specifications: [
                        ['label' => 'Flavour', 'value' => $flavour],
                        ['label' => 'Puffs', 'value' => 'Up to '.number_format($model['puffs'])],
                        ['label' => 'Nicotine', 'value' => '50mg/ml nic salt'],
                        ['label' => 'Activation', 'value' => 'Draw-activated'],
                    ],
                    packLabel: 'Single',
                    piecesPerPack: 1,
                    packUnit: 'Device',
                );
            }
        }

        return $items;
    }

    /**
     * @return list<CatalogItem>
     */
    private function podKits(): array
    {
        $kits = [
            ['brand' => 'Vaporesso', 'model' => 'XROS 4', 'price' => '49.90', 'battery' => 1000, 'colours' => ['Black', 'Silver', 'Sky Blue']],
            ['brand' => 'Vaporesso', 'model' => 'XROS 4 Mini', 'price' => '39.90', 'battery' => 1000, 'colours' => ['Black', 'Pink', 'Green']],
            ['brand' => 'Uwell', 'model' => 'Caliburn G3', 'price' => '54.90', 'battery' => 900, 'colours' => ['Black', 'Grey', 'Red']],
            ['brand' => 'Uwell', 'model' => 'Caliburn A3S', 'price' => '34.90', 'battery' => 520, 'colours' => ['Black', 'Blue', 'Rose Gold']],
            ['brand' => 'Voopoo', 'model' => 'Argus P1', 'price' => '49.90', 'battery' => 800, 'colours' => ['Black', 'Grey', 'Blue']],
            ['brand' => 'Voopoo', 'model' => 'Vinci 3', 'price' => '64.90', 'battery' => 1800, 'colours' => ['Black', 'Grey', 'Red']],
            ['brand' => 'SMOK', 'model' => 'Nord 5', 'price' => '69.90', 'battery' => 2000, 'colours' => ['Black', 'Grey', 'Blue']],
            ['brand' => 'Aspire', 'model' => 'Gotek X', 'price' => '39.90', 'battery' => 650, 'colours' => ['Black', 'Blue', 'Silver']],
            ['brand' => 'GeekVape', 'model' => 'Wenax Q', 'price' => '44.90', 'battery' => 1000, 'colours' => ['Black', 'Grey', 'Blue']],
            ['brand' => 'Lost Vape', 'model' => 'Ursa Nano', 'price' => '44.90', 'battery' => 800, 'colours' => ['Black', 'Grey', 'Blue']],
            ['brand' => 'Innokin', 'model' => 'Endura T22 Pro', 'price' => '49.90', 'battery' => 3000, 'colours' => ['Black', 'Silver']],
        ];

        $items = [];

        foreach ($kits as $kit) {
            foreach ($kit['colours'] as $colour) {
                $items[] = $this->item(
                    name: "{$kit['brand']} {$kit['model']} Pod Kit {$colour}",
                    brand: $kit['brand'],
                    categories: ['vapes', 'pod-systems'],
                    price: $kit['price'],
                    image: $this->pick(self::POD_KIT_IMAGES, $kit['model'].$colour),
                    description: "{$kit['brand']} {$kit['model']} refillable pod kit in {$colour}. {$kit['battery']}mAh built-in battery with USB-C fast charging and adjustable airflow. Pods sold separately where noted. 18+ only.",
                    specifications: [
                        ['label' => 'Colour', 'value' => $colour],
                        ['label' => 'Battery', 'value' => "{$kit['battery']}mAh"],
                        ['label' => 'Charging', 'value' => 'USB-C'],
                        ['label' => 'Refillable', 'value' => 'Yes'],
                    ],
                    packLabel: 'Kit',
                    piecesPerPack: 1,
                    packUnit: 'Device',
                );
            }
        }

        return $items;
    }

    /**
     * @return list<CatalogItem>
     */
    private function podsAndCoils(): array
    {
        $parts = [
            ['Vaporesso XROS Cartridge 0.6 Ohm 4 Pack', 'Vaporesso', '19.90', 4],
            ['Vaporesso XROS Cartridge 0.8 Ohm 4 Pack', 'Vaporesso', '19.90', 4],
            ['Vaporesso XROS Cartridge 1.0 Ohm 4 Pack', 'Vaporesso', '19.90', 4],
            ['Vaporesso XROS Cartridge 1.2 Ohm 4 Pack', 'Vaporesso', '19.90', 4],
            ['Uwell Caliburn G3 Pods 0.6 Ohm 4 Pack', 'Uwell', '21.90', 4],
            ['Uwell Caliburn G3 Pods 0.9 Ohm 4 Pack', 'Uwell', '21.90', 4],
            ['Uwell Caliburn A3 Pods 1.0 Ohm 4 Pack', 'Uwell', '18.90', 4],
            ['Voopoo PnP-X Pod Cartridge 0.3 Ohm 2 Pack', 'Voopoo', '16.90', 2],
            ['Voopoo PnP-X Pod Cartridge 0.6 Ohm 2 Pack', 'Voopoo', '16.90', 2],
            ['Voopoo PnP VM1 0.3 Ohm Coils 5 Pack', 'Voopoo', '22.90', 5],
            ['Voopoo PnP TW30 0.15 Ohm Coils 5 Pack', 'Voopoo', '22.90', 5],
            ['SMOK RPM 3 Meshed 0.23 Ohm Coils 5 Pack', 'SMOK', '24.90', 5],
            ['SMOK RPM 3 Meshed 0.15 Ohm Coils 5 Pack', 'SMOK', '24.90', 5],
            ['SMOK Nord 5 RPM 3 Empty Pod 2 Pack', 'SMOK', '14.90', 2],
            ['Aspire BP 0.6 Ohm Coils 5 Pack', 'Aspire', '19.90', 5],
            ['GeekVape Q Series Pod 0.8 Ohm 2 Pack', 'GeekVape', '15.90', 2],
            ['GeekVape Q Series Pod 1.2 Ohm 2 Pack', 'GeekVape', '15.90', 2],
            ['Lost Vape UB Nano Pod 0.8 Ohm 3 Pack', 'Lost Vape', '17.90', 3],
            ['Innokin Endura T18 1.5 Ohm Coils 5 Pack', 'Innokin', '18.90', 5],
        ];

        return array_map(fn (array $part): array => $this->item(
            name: $part[0],
            brand: $part[1],
            categories: ['vapes', 'pods-coils'],
            price: $part[2],
            image: $this->pick(self::POD_IMAGES, $part[0]),
            description: "Genuine {$part[1]} replacement part: {$part[0]}. Replace every 1–2 weeks for the best flavour and consistent vapour. 18+ only.",
            specifications: [
                ['label' => 'Pack Quantity', 'value' => (string) $part[3]],
                ['label' => 'Genuine', 'value' => 'Manufacturer original'],
            ],
            packLabel: "{$part[3]} Pack",
            piecesPerPack: $part[3],
            packUnit: 'Pieces',
        ), $parts);
    }

    /**
     * @return list<CatalogItem>
     */
    private function eLiquids(): array
    {
        $formats = [
            'freebase' => ['label' => '60ml Freebase', 'strength' => '3mg', 'price' => 32.90],
            'salt' => ['label' => '30ml Nic Salt', 'strength' => '20mg', 'price' => 24.90],
        ];

        $ranges = [
            ['brand' => 'Nasty Juice', 'flavours' => ['Cushman Mango', 'Bad Blood', 'Slow Blow', 'ASAP Grape', 'Devil Teeth', 'Trap Queen'], 'formats' => ['freebase', 'salt'], 'offset' => 0.00],
            ['brand' => 'Dinner Lady', 'flavours' => ['Lemon Tart', 'Strawberry Macaroon', 'Blackberry Crumble', 'Watermelon Slices'], 'formats' => ['freebase', 'salt'], 'offset' => 1.00],
            ['brand' => 'Pod Salt', 'flavours' => ['Cool Mint', 'Tobacco Gold', 'Mango Ice', 'Blue Razz Ice'], 'formats' => ['salt'], 'offset' => -2.00],
            ['brand' => 'Beard Vape Co', 'flavours' => ['No.05', 'No.24', 'No.32', 'No.42'], 'formats' => ['freebase'], 'offset' => 2.00],
            ['brand' => 'Juice Head', 'flavours' => ['Peach Pear', 'Watermelon Lime', 'Blueberry Lemon', 'Pineapple Grapefruit'], 'formats' => ['freebase', 'salt'], 'offset' => 0.00],
            ['brand' => 'Twist', 'flavours' => ['Pink No.1', 'Honeydew Melon Chew'], 'formats' => ['freebase'], 'offset' => -1.00],
            ['brand' => 'Coastal Clouds', 'flavours' => ['Mango Berry', 'Iced Apple Peach'], 'formats' => ['freebase'], 'offset' => -1.00],
            ['brand' => 'Vapetasia', 'flavours' => ['Killer Kustard', 'Pink Lemonade'], 'formats' => ['freebase'], 'offset' => 1.00],
        ];

        $items = [];

        foreach ($ranges as $range) {
            foreach ($range['flavours'] as $flavour) {
                foreach ($range['formats'] as $formatKey) {
                    $format = $formats[$formatKey];

                    $items[] = $this->item(
                        name: "{$range['brand']} {$flavour} {$format['label']}",
                        brand: $range['brand'],
                        categories: ['vapes', 'e-liquids'],
                        price: $this->retailPrice($format['price'] + $range['offset']),
                        image: $this->pick(self::E_LIQUID_IMAGES, $range['brand'].$flavour.$formatKey),
                        description: "{$range['brand']} {$flavour} e-liquid, {$format['label']} at {$format['strength']}. Smooth throat hit and full flavour for refillable devices. Contains nicotine. 18+ only.",
                        specifications: [
                            ['label' => 'Flavour', 'value' => $flavour],
                            ['label' => 'Format', 'value' => $format['label']],
                            ['label' => 'Nicotine Strength', 'value' => $format['strength']],
                            ['label' => 'VG/PG', 'value' => $formatKey === 'salt' ? '50/50' : '70/30'],
                        ],
                        packLabel: 'Bottle',
                        piecesPerPack: $formatKey === 'salt' ? 30 : 60,
                        packUnit: 'ml',
                    );
                }
            }
        }

        return $items;
    }

    /**
     * @return list<CatalogItem>
     */
    private function pouches(): array
    {
        $ranges = [
            ['brand' => 'ZYN', 'flavours' => ['Cool Mint', 'Spearmint', 'Citrus', 'Coffee', 'Wintergreen', 'Smooth'], 'strengths' => ['3mg', '6mg'], 'price' => '19.90'],
            ['brand' => 'VELO', 'flavours' => ['Ice Cool', 'Freeze', 'Crispy Peppermint', 'Tropic Breeze', 'Ruby Berry'], 'strengths' => ['6mg', '10mg'], 'price' => '19.50'],
            ['brand' => 'Nordic Spirit', 'flavours' => ['Spearmint', 'Mint', 'Bergamot Wildberry', 'Elderflower'], 'strengths' => ['6mg', '9mg'], 'price' => '18.90'],
            ['brand' => 'on!', 'flavours' => ['Mint', 'Citrus', 'Coffee', 'Berry', 'Wintergreen'], 'strengths' => ['4mg', '8mg'], 'price' => '17.90'],
            ['brand' => 'White Fox', 'flavours' => ['Full Charge', 'Peppered Mint', 'Black Edition'], 'strengths' => ['12mg'], 'price' => '21.90'],
            ['brand' => 'Killa', 'flavours' => ['Cold Mint', 'Watermelon'], 'strengths' => ['16mg'], 'price' => '21.90'],
            ['brand' => 'XQS', 'flavours' => ['Blueberry Mint', 'Tropical'], 'strengths' => ['8mg'], 'price' => '18.50'],
            ['brand' => 'LOOP', 'flavours' => ['Jalapeño Lime', 'Mint Mania'], 'strengths' => ['9mg'], 'price' => '19.50'],
        ];

        $items = [];

        foreach ($ranges as $range) {
            foreach ($range['flavours'] as $flavour) {
                $isMint = preg_match('/mint|wintergreen|ice|freeze|full charge/i', $flavour) === 1;

                foreach ($range['strengths'] as $strength) {
                    $items[] = $this->item(
                        name: "{$range['brand']} {$flavour} {$strength} Nicotine Pouches",
                        brand: $range['brand'],
                        categories: ['nicotine-pouches', $isMint ? 'mint-pouches' : 'flavoured-pouches'],
                        price: $range['price'],
                        image: $this->pick(self::POUCH_IMAGES, $range['brand'].$flavour),
                        description: "{$range['brand']} {$flavour} tobacco-free nicotine pouches, {$strength} per pouch. Discreet, smoke-free and spit-free — 20 pouches per can. Contains nicotine. 18+ only.",
                        specifications: [
                            ['label' => 'Flavour', 'value' => $flavour],
                            ['label' => 'Strength', 'value' => "{$strength} per pouch"],
                            ['label' => 'Pouches', 'value' => '20 per can'],
                            ['label' => 'Tobacco', 'value' => 'Tobacco-free'],
                        ],
                        packLabel: 'Can',
                        piecesPerPack: 20,
                        packUnit: 'Pouches',
                    );
                }
            }
        }

        return $items;
    }

    /**
     * @return list<CatalogItem>
     */
    private function lighters(): array
    {
        return $this->accessoryItems(['accessories', 'lighters'], 'images/catalog/products/clipper-classic-lighter.jpg', 'Reliable ignition for everyday carry.', [
            ['Zippo Classic Brushed Chrome Lighter', 'Zippo', '49.90'],
            ['Zippo Black Matte Lighter', 'Zippo', '54.90'],
            ['Zippo Street Chrome Lighter', 'Zippo', '44.90'],
            ['Zippo Armor High Polish Chrome Lighter', 'Zippo', '89.90'],
            ['Zippo Lighter Fluid 125ml', 'Zippo', '9.90'],
            ['Zippo Flints 6 Pack', 'Zippo', '3.90'],
            ['Clipper Metal Jet Lighter', 'Clipper', '24.90'],
            ['Clipper Mini Lighter 4 Pack', 'Clipper', '8.90'],
            ['Clipper Large Lighter Assorted 5 Pack', 'Clipper', '12.90'],
            ['BIC Mini Lighter', 'BIC', '2.20'],
            ['BIC Maxi Lighter 5 Pack', 'BIC', '11.50'],
            ['BIC Classic Lighter 2 Pack', 'BIC', '4.90'],
            ['Ronson Jetlite Lighter', 'Ronson', '3.50'],
            ['Ronson Butane Refill 75ml', 'Ronson', '6.90'],
            ['Djeep Classic Lighter', 'Djeep', '2.90'],
            ['Jobon Triple Jet Torch Lighter', 'Jobon', '29.90'],
            ['Rechargeable USB Arc Lighter', 'PuffMeMate', '19.90'],
        ]);
    }

    /**
     * @return list<CatalogItem>
     */
    private function papersAndFilters(): array
    {
        $papers = $this->accessoryItems(['accessories', 'rolling-papers'], 'images/catalog/products/raw-classic-rolling-papers-ks.jpg', 'Even-burning papers with a clean gum line.', [
            ['Rizla Blue Regular Papers', 'Rizla', '2.20'],
            ['Rizla Green Regular Papers', 'Rizla', '2.20'],
            ['Rizla Silver Regular Papers', 'Rizla', '2.30'],
            ['Rizla Red Regular Papers', 'Rizla', '2.20'],
            ['Rizla Silver King Size Slim Papers', 'Rizla', '2.90'],
            ['OCB Premium Slim King Size Papers', 'OCB', '2.90'],
            ['OCB Organic Hemp Slim Papers', 'OCB', '3.20'],
            ['OCB Virgin Regular Papers', 'OCB', '2.40'],
            ['Zig-Zag Ultra Thin Regular Papers', 'Zig-Zag', '2.30'],
            ['Zig-Zag Blue Kutcorners Papers', 'Zig-Zag', '2.30'],
            ['Zig-Zag King Size Slim Papers', 'Zig-Zag', '2.90'],
            ['RAW Classic 1¼ Papers', 'RAW', '2.60'],
            ['RAW Organic Hemp King Size Slim Papers', 'RAW', '3.40'],
            ['RAW Black King Size Slim Papers', 'RAW', '3.50'],
            ['RAW Connoisseur King Size Papers with Tips', 'RAW', '4.50'],
            ['Elements Ultra Thin Rice King Size Papers', 'Elements', '3.40'],
            ['Smoking Deluxe Brown Papers', 'Smoking', '2.90'],
            ['Gizeh Super Fine King Size Slim Papers', 'Gizeh', '2.90'],
        ]);

        $filters = $this->accessoryItems(['accessories', 'filters-tips'], 'images/catalog/products/ocb-slim-filter-tips.jpg', 'Consistent filtration for roll-your-own.', [
            ['Rizla Ultra Slim Filter Tips 120', 'Rizla', '3.50'],
            ['Rizla Slim Filter Tips 120', 'Rizla', '3.40'],
            ['Swan Extra Slim Filter Tips 102', 'Swan', '3.20'],
            ['Swan Slimline Filter Tips 165', 'Swan', '3.90'],
            ['Swan Charcoal Filter Tips 100', 'Swan', '5.50'],
            ['OCB Extra Slim Filter Tips 120', 'OCB', '3.30'],
            ['RAW Pre-Rolled Tips 21', 'RAW', '3.90'],
            ['RAW Classic Wide Tips', 'RAW', '1.90'],
            ['Zig-Zag Extra Slim Filter Tips 150', 'Zig-Zag', '3.60'],
            ['Gizeh Slim Biodegradable Filters 120', 'Gizeh', '3.80'],
            ['Elements Wide Tips', 'Elements', '1.90'],
        ]);

        return [...$papers, ...$filters];
    }

    /**
     * @return list<CatalogItem>
     */
    private function rollingMachines(): array
    {
        return $this->accessoryItems(['accessories', 'rolling-machines'], 'images/catalog/products/raw-classic-rolling-papers-ks.jpg', 'Rolls firm, even cigarettes in seconds.', [
            ['Rizla Regular Rolling Machine', 'Rizla', '4.90'],
            ['Rizla Automatic Rolling Box', 'Rizla', '14.90'],
            ['RAW 79mm Hemp Plastic Roller', 'RAW', '5.90'],
            ['RAW 110mm King Size Roller', 'RAW', '6.90'],
            ['Zig-Zag Easy Roller', 'Zig-Zag', '4.50'],
            ['OCB Slim Roller', 'OCB', '4.50'],
            ['Gizeh Pocket Roller', 'Gizeh', '4.90'],
            ['Powermatic II Cigarette Injector', 'Powermatic', '59.90'],
        ]);
    }

    /**
     * @return list<CatalogItem>
     */
    private function casesAndStorage(): array
    {
        return $this->accessoryItems(['accessories', 'cases-storage'], 'images/catalog/products/travel-hard-case.jpg', 'Keeps your gear protected on the go.', [
            ['Leather Look Tobacco Pouch Black', 'PuffMeMate', '19.90'],
            ['Canvas Rolling Tobacco Pouch', 'PuffMeMate', '14.90'],
            ['Metal Cigarette Case 20s', 'PuffMeMate', '17.90'],
            ['Slim Cigarette Case 25s', 'PuffMeMate', '18.90'],
            ['Aluminium Pocket Ashtray', 'PuffMeMate', '9.90'],
            ['Round Glass Ashtray', 'PuffMeMate', '12.90'],
            ['Smell Proof Stash Bag', 'PuffMeMate', '24.90'],
            ['Zip Vape Carry Case', 'PuffMeMate', '16.90'],
            ['Pod Storage Case 4 Slot', 'PuffMeMate', '12.90'],
            ['Cigar Travel Tube Case 3', 'PuffMeMate', '29.90'],
            ['Double Blade Cigar Cutter', 'PuffMeMate', '14.90'],
            ['Cedar Cigar Humidor 25', 'PuffMeMate', '129.00'],
            ['Boveda 69% Humidity Pack 2 Pack', 'Boveda', '12.90'],
        ]);
    }

    /**
     * @return list<CatalogItem>
     */
    private function batteriesAndChargers(): array
    {
        return $this->accessoryItems(['accessories', 'batteries-chargers'], 'images/catalog/products/usb-c-charge-cable-1m.jpg', 'Authentic cells and chargers from trusted makers.', [
            ['Samsung 25R 18650 Battery', 'Samsung', '14.90'],
            ['Samsung 30Q 18650 Battery', 'Samsung', '15.90'],
            ['Samsung 40T 21700 Battery', 'Samsung', '19.90'],
            ['Molicel P28A 18650 Battery', 'Molicel', '16.90'],
            ['Molicel P45B 21700 Battery', 'Molicel', '19.90'],
            ['Sony VTC6 18650 Battery', 'Sony', '16.90'],
            ['Nitecore UM2 Battery Charger', 'Nitecore', '29.90'],
            ['Nitecore SC4 Battery Charger', 'Nitecore', '69.90'],
            ['XTAR VC4SL Battery Charger', 'XTAR', '59.90'],
            ['Golisi S2 Battery Charger', 'Golisi', '29.90'],
            ['USB-C Braided Charge Cable 2m', 'PuffMeMate', '14.90'],
            ['USB-C 20W Fast Wall Charger', 'PuffMeMate', '24.90'],
            ['Dual USB Car Charger', 'PuffMeMate', '19.90'],
            ['Silicone Battery Case 2 Slot', 'PuffMeMate', '3.90'],
        ]);
    }

    /**
     * @param  list<string>  $categories
     * @param  list<array{0: string, 1: string, 2: string}>  $rows
     * @return list<CatalogItem>
     */
    private function accessoryItems(array $categories, string $image, string $note, array $rows): array
    {
        return array_map(fn (array $row): array => $this->item(
            name: $row[0],
            brand: $row[1],
            categories: $categories,
            price: $row[2],
            image: $image,
            description: "{$row[0]} by {$row[1]}. {$note} Stocked across PuffMeMate stores Australia-wide.",
            specifications: [
                ['label' => 'Type', 'value' => 'Accessory'],
            ],
            packLabel: null,
            piecesPerPack: null,
            packUnit: null,
        ), $rows);
    }

    /**
     * @param  list<string>  $categories
     * @param  list<array{label: string, value: string}>  $specifications
     * @return CatalogItem
     */
    private function item(
        string $name,
        string $brand,
        array $categories,
        string $price,
        string $image,
        string $description,
        array $specifications,
        ?string $packLabel,
        ?int $piecesPerPack,
        ?string $packUnit,
    ): array {
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => 'AU-'.Str::upper(Str::slug($name)),
            'brand' => $brand,
            'categories' => $categories,
            'description' => $description,
            'specifications' => [
                ['label' => 'Brand', 'value' => $brand],
                ...$specifications,
                ['label' => 'Market', 'value' => 'Australia'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'image' => $image,
            'selling_price' => $price,
            'pack_label' => $packLabel,
            'pieces_per_pack' => $piecesPerPack,
            'pack_unit' => $packUnit,
        ];
    }

    /**
     * Shelf-style price: .45 / .95 endings above $10, nearest 10c below.
     */
    private function retailPrice(float $amount): string
    {
        $price = $amount < 10 ? round($amount, 1) : (round($amount * 2) / 2) - 0.05;

        return number_format(max($price, 0.5), 2, '.', '');
    }

    /**
     * @return list<string>
     */
    private function tagsFor(string $slug): array
    {
        $hash = crc32($slug);

        return array_values(array_filter([
            $hash % 7 === 0 ? 'best_selling' : null,
            $hash % 9 === 0 ? 'new_arrival' : null,
            $hash % 11 === 0 ? 'on_offer' : null,
            $hash % 5 === 0 ? 'popular' : null,
        ]));
    }

    /**
     * @param  list<string>  $pool
     */
    private function pick(array $pool, string $key): string
    {
        return $pool[crc32($key) % count($pool)];
    }

    private function image(string $publicRelative): ?string
    {
        return $this->resolvedImages[$publicRelative] ??= $this->publicWebPath($publicRelative);
    }
}
