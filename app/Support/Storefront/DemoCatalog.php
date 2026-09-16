<?php

namespace App\Support\Storefront;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DemoCatalog
{
    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'cigarettes' => 'Cigarettes',
            'tobacco' => 'Tobacco',
            'vapes' => 'Vapes',
            'nicotine-pouches' => 'Nicotine Pouches',
            'packs-saves' => 'Packs & Saves',
            'wholesale' => 'Wholesale',
            'accessories' => 'Accessories',
            'nicotine' => 'Nicotine Pouches',
        ];
    }

    /**
     * @return array{slug: string, name: string}
     */
    public static function resolveCategory(string $slug): array
    {
        $categories = self::categories();

        if (! array_key_exists($slug, $categories)) {
            throw new NotFoundHttpException;
        }

        return [
            'slug' => $slug,
            'name' => $categories[$slug],
        ];
    }

    public static function categorySlugForName(string $name): ?string
    {
        foreach (self::categories() as $slug => $label) {
            if ($label === $name) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public static function branches(): array
    {
        return [
            ['id' => 'sydney-cbd', 'name' => 'PuffOnMate Sydney CBD'],
            ['id' => 'melbourne-collins', 'name' => 'PuffOnMate Melbourne Collins'],
            ['id' => 'brisbane-queen', 'name' => 'PuffOnMate Brisbane Queen St'],
            ['id' => 'perth-hay', 'name' => 'PuffOnMate Perth Hay St'],
        ];
    }

    /**
     * @return list<array{id: string, name: string, description: string, image: string, href: string, wide?: bool}>
     */
    public static function featuredCategories(): array
    {
        return [
            [
                'id' => 'cigarettes',
                'name' => 'Cigarettes',
                'description' => 'Top brands & everyday packs',
                'image' => '/images/storefront/category-cigarettes.png',
                'href' => route('shop.category', ['category' => 'cigarettes'], false),
            ],
            [
                'id' => 'vapes',
                'name' => 'Vapes',
                'description' => 'Devices, pods & disposables',
                'image' => '/images/storefront/category-vapes.png',
                'href' => route('shop.category', ['category' => 'vapes'], false),
            ],
            [
                'id' => 'tobacco',
                'name' => 'Tobacco',
                'description' => 'Rolling & pipe tobacco',
                'image' => '/images/storefront/category-tobacco.png',
                'href' => route('shop.category', ['category' => 'tobacco'], false),
            ],
            [
                'id' => 'nicotine-pouches',
                'name' => 'Nicotine Pouches',
                'description' => 'Smoke-free nicotine options',
                'image' => '/images/storefront/category-pouches.png',
                'href' => route('shop.category', ['category' => 'nicotine-pouches'], false),
                'wide' => true,
            ],
            [
                'id' => 'accessories',
                'name' => 'Accessories',
                'description' => 'Lighters, cases & more',
                'image' => '/images/storefront/category-accessories.png',
                'href' => route('shop.category', ['category' => 'accessories'], false),
                'wide' => true,
            ],
        ];
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice?: string, stock: int, category: string, image: string}>
     */
    public static function products(): array
    {
        return [
            [
                'id' => '1',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => '$14.00',
                'compareAtPrice' => '$20.00',
                'stock' => 40,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '2',
                'name' => 'Winfield Blue',
                'description' => 'Blue Pack • 20 Cigarettes',
                'price' => '$13.50',
                'stock' => 28,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-winfield-blue.jpg',
            ],
            [
                'id' => '3',
                'name' => 'IQOS Iluma',
                'description' => 'Device Kit • Heat-not-burn',
                'price' => '$89.00',
                'compareAtPrice' => '$99.00',
                'stock' => 12,
                'category' => 'Vapes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '4',
                'name' => 'Vuse Go',
                'description' => 'Disposable • Mixed Berry',
                'price' => '$18.00',
                'stock' => 56,
                'category' => 'Vapes',
                'image' => '/images/storefront/product-winfield-blue.jpg',
            ],
            [
                'id' => '5',
                'name' => 'Peter Stuyvesant Red',
                'description' => 'Red Pack • 20 Cigarettes',
                'price' => '$13.00',
                'stock' => 31,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/b4343f447a376e4db4da6328e10eddefc632ddeb.jpg',
            ],
            [
                'id' => '6',
                'name' => 'Horizon Menthol',
                'description' => 'Menthol Pack • 20 Cigarettes',
                'price' => '$12.50',
                'stock' => 19,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/907b72737ecd36dbee5ac6c27fa0b02b906a44ee.jpg',
            ],
            [
                'id' => '7',
                'name' => 'ZYN Cool Mint',
                'description' => 'Nicotine Pouches • 15 pcs',
                'price' => '$9.90',
                'compareAtPrice' => '$12.00',
                'stock' => 64,
                'category' => 'Nicotine Pouches',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '8',
                'name' => 'Clipper Classic',
                'description' => 'Refillable Lighter',
                'price' => '$4.00',
                'stock' => 120,
                'category' => 'Accessories',
                'image' => '/images/storefront/product-winfield-blue.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Dunhill Red',
                'description' => 'Red Pack • 20 Cigarettes',
                'price' => '$15.00',
                'stock' => 22,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '10',
                'name' => 'Benson & Hedges Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => '$14.50',
                'stock' => 35,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-winfield-blue.jpg',
            ],
            [
                'id' => '11',
                'name' => 'Lucky Strike Original',
                'description' => 'Original Pack • 20 Cigarettes',
                'price' => '$13.80',
                'stock' => 27,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/b4343f447a376e4db4da6328e10eddefc632ddeb.jpg',
            ],
            [
                'id' => '12',
                'name' => 'Camel Blue',
                'description' => 'Blue Pack • 20 Cigarettes',
                'price' => '$13.20',
                'stock' => 30,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/907b72737ecd36dbee5ac6c27fa0b02b906a44ee.jpg',
            ],
        ];
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice?: string, stock: int, category: string, image: string}>
     */
    public static function bestSellingProducts(): array
    {
        return array_values(array_slice(self::products(), 0, 4));
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice?: string, stock: int, category: string, image: string}>
     */
    public static function newArrivalProducts(): array
    {
        return array_values(array_slice(self::products(), 4, 4));
    }

    /**
     * @return array{id: string, name: string, description: string, price: string, compareAtPrice?: string, stock: int, category: string, image: string}
     */
    public static function findProduct(string $id): array
    {
        foreach (self::products() as $product) {
            if ($product['id'] === $id) {
                return $product;
            }
        }

        throw new NotFoundHttpException;
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice?: string, stock: int, category: string, image: string}>
     */
    public static function productsForCategory(string $slug): array
    {
        $category = self::resolveCategory($slug);
        $name = $category['name'];

        $matched = array_values(array_filter(
            self::products(),
            fn (array $product): bool => $product['category'] === $name,
        ));

        if ($matched === []) {
            return array_map(
                function (array $product) use ($name): array {
                    $product['category'] = $name;

                    return $product;
                },
                array_slice(self::products(), 0, 8),
            );
        }

        return $matched;
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice?: string, stock: int, category: string, image: string}>
     */
    public static function similarProducts(string $id, int $limit = 4): array
    {
        $product = self::findProduct($id);

        $similar = array_values(array_filter(
            self::products(),
            fn (array $candidate): bool => $candidate['category'] === $product['category']
                && $candidate['id'] !== $id,
        ));

        if ($similar === []) {
            $similar = array_values(array_filter(
                self::products(),
                fn (array $candidate): bool => $candidate['id'] !== $id,
            ));
        }

        return array_slice($similar, 0, $limit);
    }

    public static function productDetail(string $id): array
    {
        $product = self::findProduct($id);
        $image = $product['image'];

        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'category' => $product['category'],
            'categorySlug' => self::categorySlugForName($product['category']),
            'badges' => [$product['category'], 'Popular'],
            'images' => [
                $image,
                '/images/storefront/product-marlboro-gold.jpg',
                '/images/storefront/product-winfield-blue.jpg',
                '/images/storefront/b4343f447a376e4db4da6328e10eddefc632ddeb.jpg',
                '/images/storefront/907b72737ecd36dbee5ac6c27fa0b02b906a44ee.jpg',
                '/images/storefront/category-vapes.png',
            ],
            'price' => '$14.50',
            'compareAtPrice' => '$15.50',
            'availability' => 'Available',
            'rating' => 4.9,
            'reviewCount' => 123,
            'about' => 'Premium everyday pack with a smoother draw and consistent taste. Branch stock and pricing may vary — select your nearest branch for accurate availability.',
            'branch' => 'West Village Branch',
            'stock' => $product['stock'],
            'cardPrice' => $product['price'],
            'image' => $image,
            'specifications' => [
                ['label' => 'Brand', 'value' => explode(' ', $product['name'])[0] ?? $product['name']],
                ['label' => 'Variant', 'value' => trim(str_replace(explode(' ', $product['name'])[0] ?? '', '', $product['name'])) ?: 'Standard'],
                ['label' => 'Product Type', 'value' => $product['category'] === 'Cigarettes' ? 'Cigarette' : $product['category']],
                ['label' => 'Pack Format', 'value' => 'Hard Pack'],
                ['label' => 'Quantity', 'value' => '20 cigarettes'],
                ['label' => 'Filter', 'value' => 'Firm Filter'],
                ['label' => 'Market', 'value' => 'Australia'],
                ['label' => 'Packaging', 'value' => 'Australian compliant plain packaging'],
                ['label' => 'Age Requirement', 'value' => '18+'],
            ],
            'shippingPoints' => [
                'Same-day delivery available in selected local zones when ordered before cut-off.',
                'Stock is confirmed against your selected branch before dispatch.',
                'Cash on delivery and EFTPOS on delivery are supported where offered.',
                'Photo ID may be required on delivery for age-restricted products.',
                'Delivery windows depend on branch workload and zone coverage.',
                'If an item is out of stock at your branch, we will notify you before shipping.',
                'Contact support for wholesale or bulk delivery arrangements.',
            ],
            'responsiblePoints' => [
                'Tobacco and nicotine products are for adults 18+ only.',
                'We do not sell to minors and may request government-issued ID.',
                'Products carry mandatory health warnings as required by law.',
                'Advertising and promotions comply with Australian retail rules.',
                'Please store products securely and away from children.',
                'If you are pregnant or have health concerns, seek medical advice before use.',
                'Dispose of packaging and waste responsibly.',
            ],
            'reviews' => [
                [
                    'id' => 'r1',
                    'author' => 'Hormat Felsal',
                    'date' => '12 Mar 2026',
                    'rating' => 5,
                    'body' => 'Consistent pack quality and fast local delivery. Will order again from this branch.',
                ],
            ],
        ];
    }

    public static function cart(): array
    {
        $products = self::products();
        $marlboro = $products[0];
        $winfield = $products[1];
        $iqos = $products[2];

        $groups = [
            [
                'id' => 'g1',
                'branchName' => 'Williamsburg Branch',
                'selected' => true,
                'items' => [
                    [
                        'id' => 'ci1',
                        'productId' => $marlboro['id'],
                        'name' => 'L&M Blue Label',
                        'category' => 'Cigarettes',
                        'description' => 'King size · 20 cigarettes',
                        'image' => $marlboro['image'],
                        'unitPrice' => 12.00,
                        'quantity' => 2,
                        'available' => 6,
                        'unavailable' => false,
                        'branchId' => 'williamsburg',
                        'branchName' => 'Williamsburg Branch',
                        'selected' => true,
                    ],
                    [
                        'id' => 'ci2',
                        'productId' => $winfield['id'],
                        'name' => $winfield['name'],
                        'category' => $winfield['category'],
                        'description' => $winfield['description'],
                        'image' => $winfield['image'],
                        'unitPrice' => 13.50,
                        'quantity' => 1,
                        'available' => null,
                        'unavailable' => true,
                        'branchId' => 'williamsburg',
                        'branchName' => 'Williamsburg Branch',
                        'selected' => true,
                    ],
                ],
            ],
            [
                'id' => 'g2',
                'branchName' => 'Pacific Coast Tobacco – Brisbane Branch',
                'selected' => true,
                'items' => [
                    [
                        'id' => 'ci3',
                        'productId' => $iqos['id'],
                        'name' => $iqos['name'],
                        'category' => $iqos['category'],
                        'description' => $iqos['description'],
                        'image' => $iqos['image'],
                        'unitPrice' => 20.00,
                        'quantity' => 1,
                        'available' => 4,
                        'unavailable' => false,
                        'branchId' => 'brisbane',
                        'branchName' => 'Pacific Coast Tobacco – Brisbane Branch',
                        'selected' => true,
                    ],
                ],
            ],
        ];

        $itemCount = 0;

        foreach ($groups as $group) {
            $itemCount += count($group['items']);
        }

        return [
            'itemCount' => $itemCount,
            'stockReserved' => true,
            'groups' => $groups,
            'summary' => [
                'branches' => [
                    [
                        'branchName' => 'Williamsburg Branch',
                        'lines' => [
                            ['label' => 'Subtotal', 'amount' => 24.00, 'tone' => 'default'],
                            ['label' => 'Delivery fee', 'amount' => 3.50, 'tone' => 'default'],
                            ['label' => 'First delivery discount', 'amount' => -3.50, 'tone' => 'discount'],
                        ],
                    ],
                    [
                        'branchName' => 'Pacific Coast Tobacco – Brisbane Branch',
                        'lines' => [
                            ['label' => 'Subtotal', 'amount' => 20.00, 'tone' => 'default'],
                            ['label' => 'Delivery fee', 'amount' => 3.50, 'tone' => 'default'],
                            ['label' => 'First delivery discount', 'amount' => -3.50, 'tone' => 'discount'],
                        ],
                    ],
                ],
                'total' => 44.00,
            ],
            'branches' => [
                ['id' => 'sydney-cbd', 'name' => 'PuffOnMate Sydney CBD'],
                ['id' => 'melbourne-collins', 'name' => 'PuffOnMate Melbourne Collins'],
                ['id' => 'brisbane-queen', 'name' => 'PuffOnMate Brisbane Queen St'],
                ['id' => 'perth-hay', 'name' => 'PuffOnMate Perth Hay St'],
                ['id' => 'williamsburg', 'name' => 'Williamsburg Branch'],
                ['id' => 'brisbane', 'name' => 'Pacific Coast Tobacco – Brisbane Branch'],
            ],
        ];
    }

    public static function checkout(): array
    {
        $products = self::products();
        $first = $products[0];
        $vape = $products[3];

        return [
            'itemCount' => 2,
            'items' => [
                [
                    'id' => 'co1',
                    'name' => 'L&M Blue Label',
                    'image' => $first['image'],
                    'quantity' => 1,
                    'unitPrice' => 29.95,
                    'branchName' => 'Williamsburg Branch',
                ],
                [
                    'id' => 'co2',
                    'name' => 'Vuse GO Berry',
                    'image' => $vape['image'],
                    'quantity' => 1,
                    'unitPrice' => 39.95,
                    'branchName' => 'Pacific Coast Tobacco – Brisbane Branch',
                ],
            ],
            'summary' => [
                'subtotal' => 69.90,
                'deliveryLabel' => 'FREE',
                'deliveryAmount' => null,
                'total' => 69.90,
            ],
            'deliveryMethods' => [
                [
                    'id' => 'same-day',
                    'title' => 'Same-day local delivery',
                    'description' => 'Fast local delivery in selected areas.',
                    'meta' => 'Usually within hours',
                ],
                [
                    'id' => 'australia-wide',
                    'title' => 'Australia-wide shipping',
                    'description' => 'Shipping available for eligible orders.',
                    'meta' => '2–5 business days',
                ],
            ],
            'paymentMethods' => [
                [
                    'id' => 'cash',
                    'title' => 'Cash on delivery',
                    'description' => 'Pay the driver in cash',
                    'badge' => '$',
                ],
                [
                    'id' => 'card',
                    'title' => 'Card on delivery',
                    'description' => 'Driver carries an EFTPOS machine',
                    'badge' => 'EFT',
                ],
            ],
            'timeSlots' => [
                ['id' => 'morning', 'label' => '9:00 AM – 12:00 PM'],
                ['id' => 'afternoon', 'label' => '12:00 PM – 3:00 PM'],
                ['id' => 'evening', 'label' => '3:00 PM – 6:00 PM'],
            ],
            'trustBadges' => [
                'Private packaging',
                'Discreet delivery',
                'EFTPOS available',
                'WhatsApp support',
            ],
            'deliveryAlert' => 'Local delivery covers selected metro zones near your branch. Outside those areas, choose Australia-wide shipping.',
            'legalNote' => 'Age-restricted products. You must be 18+ with valid photo ID. Orders may be refused if ID cannot be verified on delivery.',
        ];
    }

    public static function customerOrders(): array
    {
        $products = self::products();
        $images = [
            $products[0]['image'],
            $products[1]['image'],
            $products[2]['image'],
            $products[3]['image'],
            $products[4]['image'] ?? $products[0]['image'],
        ];

        $templates = [
            [
                'title' => 'Marlboro Gold',
                'description' => 'Selected pack with branch-checked stock before dispatch. Smooth gold blend for everyday preference.',
                'priceLabel' => '$1,300',
                'status' => 'shipped',
            ],
            [
                'title' => 'Premium Vape',
                'description' => 'Heat-not-burn kit reserved at your selected branch. Includes starter accessories for first use.',
                'priceLabel' => '$2,500',
                'status' => 'processing',
            ],
            [
                'title' => 'Industrial Grade Material',
                'description' => 'Wholesale-style carton selection delivered after ID verification at handover.',
                'priceLabel' => '$5,450',
                'status' => 'delivered',
            ],
            [
                'title' => 'Industrial Powder',
                'description' => 'Compact pouch assortment with discreet packaging and branch-accurate pricing.',
                'priceLabel' => '$850',
                'status' => 'shipped',
            ],
            [
                'title' => 'Winfield Blue',
                'description' => 'Classic blue pack line with courier ETA updates once the driver is assigned.',
                'priceLabel' => '$980',
                'status' => 'processing',
            ],
            [
                'title' => 'Vuse GO Berry',
                'description' => 'Disposable berry flavour unit from the Brisbane branch assortment.',
                'priceLabel' => '$420',
                'status' => 'delivered',
            ],
            [
                'title' => 'Horizon Menthol',
                'description' => 'Menthol carton option with stock reserved at checkout confirmation.',
                'priceLabel' => '$1,150',
                'status' => 'shipped',
            ],
        ];

        $orders = [];

        for ($index = 0; $index < 13; $index++) {
            $template = $templates[$index % count($templates)];
            $number = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);

            $orders[] = [
                'id' => 'ord-'.($index + 1),
                'orderNumber' => '#ORD-CP2026-'.$number,
                'title' => $template['title'],
                'description' => $template['description'],
                'priceLabel' => $template['priceLabel'],
                'image' => $images[$index % count($images)],
                'status' => $template['status'],
            ];
        }

        return $orders;
    }

    /**
     * Demo order tracking detail for the customer account track page.
     *
     * @return array{
     *     id: string,
     *     orderNumber: string,
     *     status: string,
     *     products: list<array{name: string, quantityLabel: string, priceLabel: string}>,
     *     totalLabel: string,
     *     shippingAddress: array{name: string, lines: list<string>, mobile: string},
     *     driver: array{name: string, truckNumber: string, phone: string},
     *     timeline: list<array{key: string, label: string, state: string, detail: string}>
     * }|null
     */
    public static function customerOrderDetail(string $id): ?array
    {
        $order = collect(self::customerOrders())->firstWhere('id', $id);

        if ($order === null) {
            return null;
        }

        $status = $order['status'];

        $timeline = match ($status) {
            'delivered' => [
                ['key' => 'submitted', 'label' => 'Submitted', 'state' => 'completed', 'detail' => 'Completed'],
                ['key' => 'processing', 'label' => 'Processing', 'state' => 'completed', 'detail' => 'Completed'],
                ['key' => 'driver_assigned', 'label' => 'Driver Assigned', 'state' => 'completed', 'detail' => 'Completed'],
                ['key' => 'on_the_way', 'label' => 'On The Way', 'state' => 'completed', 'detail' => 'Completed'],
                ['key' => 'delivered', 'label' => 'Delivered', 'state' => 'completed', 'detail' => 'Completed'],
            ],
            'processing' => [
                ['key' => 'submitted', 'label' => 'Submitted', 'state' => 'completed', 'detail' => 'Completed'],
                ['key' => 'processing', 'label' => 'Processing', 'state' => 'current', 'detail' => 'in progress right now'],
                ['key' => 'driver_assigned', 'label' => 'Driver Assigned', 'state' => 'upcoming', 'detail' => 'Up next'],
                ['key' => 'on_the_way', 'label' => 'On The Way', 'state' => 'upcoming', 'detail' => 'Up next'],
                ['key' => 'delivered', 'label' => 'Delivered', 'state' => 'upcoming', 'detail' => 'Up next'],
            ],
            default => [
                ['key' => 'submitted', 'label' => 'Submitted', 'state' => 'completed', 'detail' => 'Completed'],
                ['key' => 'processing', 'label' => 'Processing', 'state' => 'completed', 'detail' => 'Completed'],
                ['key' => 'driver_assigned', 'label' => 'Driver Assigned', 'state' => 'completed', 'detail' => 'Completed'],
                ['key' => 'on_the_way', 'label' => 'On The Way', 'state' => 'current', 'detail' => 'in progress right now'],
                ['key' => 'delivered', 'label' => 'Delivered', 'state' => 'upcoming', 'detail' => 'Up next'],
            ],
        };

        return [
            'id' => $order['id'],
            'orderNumber' => $order['orderNumber'],
            'status' => $status,
            'products' => [
                [
                    'name' => $order['title'],
                    'quantityLabel' => '1 Unit',
                    'priceLabel' => $order['priceLabel'],
                ],
                [
                    'name' => 'Vuse GO Berry',
                    'quantityLabel' => '1 Unit',
                    'priceLabel' => '$450',
                ],
            ],
            'totalLabel' => $order['priceLabel'],
            'shippingAddress' => [
                'name' => 'John Davidson',
                'lines' => [
                    '3 Boucaut Street',
                    'Australia',
                ],
                'mobile' => 'Mobile: (+61) 4555-666-666',
            ],
            'driver' => [
                'name' => 'John Smith',
                'truckNumber' => 'Truck #TR-4523',
                'phone' => '(+61) 4555-666-666',
            ],
            'timeline' => $timeline,
        ];
    }
}
