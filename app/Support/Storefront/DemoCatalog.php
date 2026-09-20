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
        return array_values(array_slice(self::products(), 0, 8));
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice?: string, stock: int, category: string, image: string}>
     */
    public static function newArrivalProducts(): array
    {
        return array_values(array_slice(self::products(), 4, 8));
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
                'branchName' => 'Williamsburg Branch',
                'products' => [
                    'Marlboro Gold (20)',
                    'L&M Blue Label (20)',
                    'Vuse GO Berry (1)',
                ],
                'priceLabel' => '$1,300',
                'status' => 'shipped',
            ],
            [
                'branchName' => 'Williamsburg Branch',
                'products' => [
                    'Premium Vape (1)',
                    'IQOS Iluma (1)',
                    'Clipper Classic (2)',
                ],
                'priceLabel' => '$2,500',
                'status' => 'processing',
            ],
            [
                'branchName' => 'Williamsburg Branch',
                'products' => [
                    'Industrial Grade Material (1)',
                    'Winfield Blue (20)',
                ],
                'priceLabel' => '$5,450',
                'status' => 'delivered',
            ],
            [
                'branchName' => 'Brisbane Branch',
                'products' => [
                    'Industrial Powder (1)',
                    'Camel Blue (20)',
                    'ZYN Cool Mint (5)',
                ],
                'priceLabel' => '$850',
                'status' => 'shipped',
            ],
            [
                'branchName' => 'Williamsburg Branch',
                'products' => [
                    'Winfield Blue (20)',
                    'Horizon Menthol (20)',
                ],
                'priceLabel' => '$980',
                'status' => 'processing',
            ],
            [
                'branchName' => 'Brisbane Branch',
                'products' => [
                    'Vuse GO Berry (1)',
                    'Dunhill Red (20)',
                    'Lucky Strike Original (20)',
                ],
                'priceLabel' => '$420',
                'status' => 'delivered',
            ],
            [
                'branchName' => 'Williamsburg Branch',
                'products' => [
                    'Horizon Menthol (20)',
                    'Peter Stuyvesant Red (20)',
                    'Benson & Hedges Gold (20)',
                ],
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
                'branchName' => $template['branchName'],
                'products' => $template['products'],
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
     *     branchName: string,
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
            'branchName' => $order['branchName'],
            'status' => $status,
            'products' => collect($order['products'])
                ->values()
                ->map(function (string $label, int $index) use ($order): array {
                    $name = $label;
                    $quantity = 1;

                    if (preg_match('/^(.*)\s+\((\d+)\)$/', $label, $matches) === 1) {
                        $name = $matches[1];
                        $quantity = (int) $matches[2];
                    }

                    return [
                        'name' => $name,
                        'quantityLabel' => $quantity === 1 ? '1 Unit' : $quantity.' Packet',
                        'priceLabel' => $index === 0 ? $order['priceLabel'] : '$450',
                    ];
                })
                ->all(),
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

    /**
     * Demo products awaiting a customer review.
     *
     * @return list<array{id: string, name: string, image: string, shippingStatus: string}>
     */
    public static function productsToReview(): array
    {
        $catalog = self::products();
        $names = [
            'Marlboro Gold',
            'L&M Blue Label',
            'Vuse GO Berry',
            'Winfield Blue',
            'Horizon Menthol',
            'IQOS Iluma',
            'Peter Stuyvesant Red',
            'ZYN Cool Mint',
            'Dunhill Red',
            'Benson & Hedges Gold',
            'Lucky Strike Original',
            'Camel Blue',
            'Clipper Classic',
        ];

        $items = [];

        foreach ($names as $index => $name) {
            $source = $catalog[$index % count($catalog)];

            $items[] = [
                'id' => 'review-'.($index + 1),
                'name' => $name,
                'image' => $source['image'],
                'shippingStatus' => 'delivered',
            ];
        }

        return $items;
    }

    /**
     * Resolve a single demo product awaiting review.
     *
     * @return array{id: string, name: string, image: string, shippingStatus: string}|null
     */
    public static function productToReview(string $id): ?array
    {
        $product = collect(self::productsToReview())->firstWhere('id', $id);

        return $product;
    }

    /**
     * Demo orders assigned to the authenticated driver for the active-route UI.
     *
     * @return list<array{
     *     id: string,
     *     orderNumber: string,
     *     status: string,
     *     customerName: string,
     *     areaId: string,
     *     areaLabel: string,
     *     addressSummary: string,
     *     windowLabel: string,
     *     productCount: int,
     *     totalLabel: string,
     *     phone: string,
     *     notes: string|null,
     *     unitLabel: string|null,
     *     productPreview: list<string>,
     *     paymentMethod: string,
     *     etaLabel: string|null,
     *     completedLabel: string|null
     * }>
     */
    public static function driverAssignedOrders(): array
    {
        $codOrderIds = ['drv-ord-1001', 'drv-ord-1003', 'drv-ord-1008', 'drv-ord-1010'];

        return array_map(
            static function (array $order) use ($codOrderIds): array {
                $productPreview = array_values(array_map(
                    static fn (array $product): string => $product['name'],
                    array_slice($order['products'], 0, 2),
                ));

                $etaLabel = match ($order['status']) {
                    'arrived' => 'At door',
                    'delivered' => null,
                    default => $order['id'] === 'drv-ord-1001' ? '6 min' : '12 min',
                };

                return [
                    'id' => $order['id'],
                    'orderNumber' => $order['orderNumber'],
                    'status' => $order['status'],
                    'customerName' => $order['customerName'],
                    'areaId' => $order['areaId'],
                    'areaLabel' => $order['areaLabel'],
                    'addressSummary' => $order['addressSummary'],
                    'windowLabel' => $order['windowLabel'],
                    'productCount' => $order['productCount'],
                    'totalLabel' => $order['totalLabel'],
                    'phone' => $order['phone'],
                    'notes' => $order['notes'],
                    'unitLabel' => $order['shippingAddress']['line2'],
                    'productPreview' => $productPreview,
                    'paymentMethod' => in_array($order['id'], $codOrderIds, true) ? 'cod' : 'paid',
                    'etaLabel' => $etaLabel,
                    'completedLabel' => $order['completedLabel'] ?? null,
                ];
            },
            self::driverOrderCatalog(),
        );
    }

    /**
     * One driver carries every drop in an area. Admins do not split
     * same-neighbourhood orders across riders.
     *
     * @return list<array{
     *     id: string,
     *     label: string,
     *     orderCount: int,
     *     remainingCount: int,
     *     deliveredCount: int,
     *     remaining: list<array<string, mixed>>,
     *     delivered: list<array<string, mixed>>,
     *     orders: list<array<string, mixed>>
     * }>
     */
    public static function driverAssignedAreas(): array
    {
        $buckets = [];

        foreach (self::driverAssignedOrders() as $order) {
            $areaId = $order['areaId'];

            if (! isset($buckets[$areaId])) {
                $buckets[$areaId] = [
                    'id' => $areaId,
                    'label' => $order['areaLabel'],
                    'orders' => [],
                ];
            }

            $buckets[$areaId]['orders'][] = $order;
        }

        return array_values(array_map(static function (array $area): array {
            $orders = $area['orders'];
            $remaining = array_values(array_filter(
                $orders,
                static fn (array $order): bool => $order['status'] !== 'delivered',
            ));
            $delivered = array_values(array_filter(
                $orders,
                static fn (array $order): bool => $order['status'] === 'delivered',
            ));

            return [
                'id' => $area['id'],
                'label' => $area['label'],
                'orderCount' => count($orders),
                'remainingCount' => count($remaining),
                'deliveredCount' => count($delivered),
                'remaining' => $remaining,
                'delivered' => $delivered,
                'orders' => $orders,
            ];
        }, $buckets));
    }

    /**
     * @param  list<array{remaining: list<array<string, mixed>>, delivered: list<array<string, mixed>>}>  $areas
     * @return list<array<string, mixed>>
     */
    public static function flattenDriverAreaOrders(array $areas, string $key): array
    {
        $orders = [];

        foreach ($areas as $area) {
            foreach ($area[$key] as $order) {
                $orders[] = $order;
            }
        }

        return $orders;
    }

    /**
     * Neighbourhood still in progress, then later suburbs on the same run.
     *
     * @return array{current: array<string, mixed>|null, later: list<array<string, mixed>>}
     */
    public static function driverRunAreas(): array
    {
        $current = null;
        $later = [];

        foreach (self::driverAssignedAreas() as $area) {
            if ($area['remainingCount'] === 0) {
                continue;
            }

            if ($current === null) {
                $current = $area;

                continue;
            }

            $later[] = $area;
        }

        return [
            'current' => $current,
            'later' => $later,
        ];
    }

    /**
     * Static field-hub copy for the driver dashboard until earnings,
     * roster, ratings, and vehicle live on real tables.
     *
     * @return array{
     *     online: bool,
     *     etaNext: string,
     *     kmToday: string,
     *     supportPhone: string,
     *     branchPhone: string,
     *     shift: array{label: string, window: string, endsIn: string, branch: string},
     *     earnings: array{todayLabel: string, weekLabel: string, tipsLabel: string, perStopLabel: string},
     *     performance: array{rating: string, ratingCount: int, onTimePercent: int, acceptancePercent: int, completionPercent: int},
     *     vehicle: array{name: string, plate: string, fuel: string, insuranceUntil: string, docsOk: bool},
     *     alerts: list<array{id: string, title: string, body: string}>
     * }
     */
    public static function driverDashboardHub(): array
    {
        return [
            'online' => true,
            'etaNext' => '6 min',
            'kmToday' => '8.1 km',
            'supportPhone' => '+61418001100',
            'branchPhone' => '+61290001200',
            'shift' => [
                'label' => 'Afternoon run',
                'window' => '1:00–7:00 PM',
                'endsIn' => '4h 12m',
                'branch' => 'Sydney CBD',
            ],
            'earnings' => [
                'todayLabel' => '$194.40',
                'weekLabel' => '$612.10',
                'tipsLabel' => '$18.00',
                'perStopLabel' => '$21.60',
            ],
            'performance' => [
                'rating' => '4.92',
                'ratingCount' => 128,
                'onTimePercent' => 97,
                'acceptancePercent' => 94,
                'completionPercent' => 99,
            ],
            'vehicle' => [
                'name' => 'Honda Scooter',
                'plate' => 'NSW · DVR-204',
                'fuel' => 'Range good',
                'insuranceUntil' => '12 Nov 2026',
                'docsOk' => true,
            ],
            'alerts' => [
                [
                    'id' => 'area',
                    'title' => 'Surry Hills is one run',
                    'body' => 'Seven drops in the same suburb are yours — finish Surry Hills before Darlinghurst.',
                ],
                [
                    'id' => 'weather',
                    'title' => 'Light rain after 4 PM',
                    'body' => 'Allow extra time on Oxford St and keep parcels dry.',
                ],
                [
                    'id' => 'branch',
                    'title' => 'CBD desk note',
                    'body' => 'OTP is required for every cash-on-delivery stop today.',
                ],
            ],
        ];
    }

    /**
     * Field-header contact numbers shared with every driver page.
     *
     * @return array{supportPhone: string, branchPhone: string}
     */
    public static function driverSupportContacts(): array
    {
        $hub = self::driverDashboardHub();

        return [
            'supportPhone' => $hub['supportPhone'],
            'branchPhone' => $hub['branchPhone'],
        ];
    }

    /**
     * Resolve one demo driver order for the detail + OTP deliver screen.
     *
     * @return array{
     *     id: string,
     *     orderNumber: string,
     *     status: string,
     *     statusLabel: string,
     *     customerName: string,
     *     areaId: string,
     *     areaLabel: string,
     *     addressSummary: string,
     *     windowLabel: string,
     *     productCount: int,
     *     totalLabel: string,
     *     phone: string,
     *     notes: string|null,
     *     demoOtpHint: string,
     *     shippingAddress: array{line1: string, line2: string|null, city: string, region: string, postalCode: string, country: string},
     *     products: list<array{name: string, quantityLabel: string, priceLabel: string}>
     * }|null
     */
    public static function driverOrderDetail(string $id): ?array
    {
        $assigned = null;

        foreach ([...self::driverAssignedOrders(), ...self::driverPastOrders()] as $order) {
            if ($order['id'] === $id) {
                $assigned = $order;
                break;
            }
        }

        if ($assigned === null) {
            return null;
        }

        foreach ([...self::driverOrderCatalog(), ...self::driverPastOrderCatalog()] as $order) {
            if ($order['id'] === $id) {
                return array_merge($order, $assigned);
            }
        }

        return null;
    }

    /**
     * Demo transcript for the order-scoped driver chat screen.
     *
     * Sender ids: customer = 1, branch = 2, driver = $viewerId so own bubbles
     * match the signed-in driver when they send locally.
     *
     * @return list<array{
     *     id: int,
     *     conversation_id: int,
     *     type: string,
     *     body: string|null,
     *     created_at: string,
     *     sender: array{id: int, name: string, avatar_url: string|null}|null,
     *     attachments: list<array{}>
     * }>|null
     */
    public static function driverOrderChatMessages(string $orderId, int $viewerId): ?array
    {
        $order = self::driverOrderDetail($orderId);

        if ($order === null) {
            return null;
        }

        $now = now();
        $customerName = $order['customerName'];
        $orderNumber = $order['orderNumber'];
        $conversationId = abs(crc32($orderId)) ?: 1;

        return [
            [
                'id' => 1,
                'conversation_id' => $conversationId,
                'type' => 'system',
                'body' => "Thread opened for order {$orderNumber}.",
                'created_at' => $now->copy()->subMinutes(42)->toIso8601String(),
                'sender' => null,
                'attachments' => [],
            ],
            [
                'id' => 2,
                'conversation_id' => $conversationId,
                'type' => 'text',
                'body' => 'Hi — package is packed and ready. Customer asked for a call on arrival.',
                'created_at' => $now->copy()->subMinutes(38)->toIso8601String(),
                'sender' => [
                    'id' => 2,
                    'name' => 'Branch desk',
                    'avatar_url' => null,
                ],
                'attachments' => [],
            ],
            [
                'id' => 3,
                'conversation_id' => $conversationId,
                'type' => 'text',
                'body' => 'Got it. Heading out now — ETA inside the delivery window.',
                'created_at' => $now->copy()->subMinutes(35)->toIso8601String(),
                'sender' => [
                    'id' => $viewerId,
                    'name' => 'You',
                    'avatar_url' => null,
                ],
                'attachments' => [],
            ],
            [
                'id' => 4,
                'conversation_id' => $conversationId,
                'type' => 'text',
                'body' => "Hi, I'm {$customerName}. The intercom is broken — please call when you're downstairs.",
                'created_at' => $now->copy()->subMinutes(12)->toIso8601String(),
                'sender' => [
                    'id' => 1,
                    'name' => $customerName,
                    'avatar_url' => null,
                ],
                'attachments' => [],
            ],
            [
                'id' => 5,
                'conversation_id' => $conversationId,
                'type' => 'text',
                'body' => 'Will do. About 10 minutes out.',
                'created_at' => $now->copy()->subMinutes(10)->toIso8601String(),
                'sender' => [
                    'id' => $viewerId,
                    'name' => 'You',
                    'avatar_url' => null,
                ],
                'attachments' => [],
            ],
            [
                'id' => 6,
                'conversation_id' => $conversationId,
                'type' => 'system',
                'body' => $order['notes'] !== null
                    ? "Delivery note: {$order['notes']}"
                    : 'No special delivery notes on this stop.',
                'created_at' => $now->copy()->subMinutes(8)->toIso8601String(),
                'sender' => null,
                'attachments' => [],
            ],
        ];
    }

    /**
     * @return list<array{
     *     id: string,
     *     orderNumber: string,
     *     status: string,
     *     statusLabel: string,
     *     customerName: string,
     *     areaId: string,
     *     areaLabel: string,
     *     addressSummary: string,
     *     windowLabel: string,
     *     productCount: int,
     *     totalLabel: string,
     *     phone: string,
     *     notes: string|null,
     *     demoOtpHint: string,
     *     shippingAddress: array{line1: string, line2: string|null, city: string, region: string, postalCode: string, country: string},
     *     products: list<array{name: string, quantityLabel: string, priceLabel: string}>
     * }>
     */
    private static function driverOrderCatalog(): array
    {
        $surryHills = 'area-surry-hills';
        $darlinghurst = 'area-darlinghurst';

        return [
            [
                'id' => 'drv-ord-1001',
                'orderNumber' => 'POM-DRV-1001',
                'status' => 'out_for_delivery',
                'statusLabel' => 'Out for delivery',
                'customerName' => 'Elena Vargas',
                'areaId' => $surryHills,
                'areaLabel' => 'Surry Hills',
                'addressSummary' => '418 Crown St, Surry Hills',
                'windowLabel' => 'Today · 2:00–3:00 PM',
                'productCount' => 3,
                'totalLabel' => '$24.50',
                'phone' => '+61411100200',
                'notes' => 'Call on arrival; leave with concierge if no answer.',
                'demoOtpHint' => '482913',
                'shippingAddress' => [
                    'line1' => '418 Crown Street',
                    'line2' => 'Apartment 4B',
                    'city' => 'Surry Hills',
                    'region' => 'NSW',
                    'postalCode' => '2010',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Marlboro Gold', 'quantityLabel' => '×2', 'priceLabel' => '$12.00'],
                    ['name' => 'Dunhill Red', 'quantityLabel' => '×1', 'priceLabel' => '$7.50'],
                    ['name' => 'Clipper Classic', 'quantityLabel' => '×1', 'priceLabel' => '$5.00'],
                ],
            ],
            [
                'id' => 'drv-ord-1002',
                'orderNumber' => 'POM-DRV-1002',
                'status' => 'arrived',
                'statusLabel' => 'Arrived',
                'customerName' => 'Chris Thompson',
                'areaId' => $surryHills,
                'areaLabel' => 'Surry Hills',
                'addressSummary' => '90 Foveaux St, Surry Hills',
                'windowLabel' => 'Today · 2:15–3:00 PM',
                'productCount' => 2,
                'totalLabel' => '$16.80',
                'phone' => '+61418123344',
                'notes' => null,
                'demoOtpHint' => '719204',
                'shippingAddress' => [
                    'line1' => '90 Foveaux Street',
                    'line2' => null,
                    'city' => 'Surry Hills',
                    'region' => 'NSW',
                    'postalCode' => '2010',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Camel Blue', 'quantityLabel' => '×2', 'priceLabel' => '$9.80'],
                    ['name' => 'Lucky Strike Original', 'quantityLabel' => '×1', 'priceLabel' => '$7.00'],
                ],
            ],
            [
                'id' => 'drv-ord-1003',
                'orderNumber' => 'POM-DRV-1003',
                'status' => 'out_for_delivery',
                'statusLabel' => 'Out for delivery',
                'customerName' => 'Priya Nair',
                'areaId' => $surryHills,
                'areaLabel' => 'Surry Hills',
                'addressSummary' => '12 Market Row, Surry Hills',
                'windowLabel' => 'Today · 2:30–3:30 PM',
                'productCount' => 4,
                'totalLabel' => '$31.20',
                'phone' => '+61419112233',
                'notes' => 'Building access code 2048.',
                'demoOtpHint' => '305871',
                'shippingAddress' => [
                    'line1' => '12 Market Row',
                    'line2' => 'Unit 17',
                    'city' => 'Surry Hills',
                    'region' => 'NSW',
                    'postalCode' => '2010',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Benson & Hedges Gold', 'quantityLabel' => '×2', 'priceLabel' => '$14.00'],
                    ['name' => 'Marlboro Gold', 'quantityLabel' => '×1', 'priceLabel' => '$6.00'],
                    ['name' => 'Dunhill Red', 'quantityLabel' => '×1', 'priceLabel' => '$7.50'],
                    ['name' => 'Clipper Classic', 'quantityLabel' => '×1', 'priceLabel' => '$3.70'],
                ],
            ],
            [
                'id' => 'drv-ord-1004',
                'orderNumber' => 'POM-DRV-1004',
                'status' => 'delivered',
                'statusLabel' => 'Delivered',
                'customerName' => 'James Okonkwo',
                'areaId' => $surryHills,
                'areaLabel' => 'Surry Hills',
                'addressSummary' => '18 Fitzroy St, Surry Hills',
                'windowLabel' => 'Today · Completed',
                'productCount' => 1,
                'totalLabel' => '$8.90',
                'phone' => '+61416115566',
                'notes' => null,
                'demoOtpHint' => '664021',
                'shippingAddress' => [
                    'line1' => '18 Fitzroy Street',
                    'line2' => 'Level 3',
                    'city' => 'Surry Hills',
                    'region' => 'NSW',
                    'postalCode' => '2010',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Camel Blue', 'quantityLabel' => '×1', 'priceLabel' => '$8.90'],
                ],
            ],
            [
                'id' => 'drv-ord-1006',
                'orderNumber' => 'POM-DRV-1006',
                'status' => 'out_for_delivery',
                'statusLabel' => 'Out for delivery',
                'customerName' => 'Liam Nguyen',
                'areaId' => $surryHills,
                'areaLabel' => 'Surry Hills',
                'addressSummary' => '24 Albion St, Surry Hills',
                'windowLabel' => 'Today · 3:00–4:00 PM',
                'productCount' => 2,
                'totalLabel' => '$18.20',
                'phone' => '+61413224455',
                'notes' => 'Leave with the cafe if the shop is closed.',
                'demoOtpHint' => '551902',
                'shippingAddress' => [
                    'line1' => '24 Albion Street',
                    'line2' => 'Unit 8',
                    'city' => 'Surry Hills',
                    'region' => 'NSW',
                    'postalCode' => '2010',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Marlboro Gold', 'quantityLabel' => '×2', 'priceLabel' => '$12.00'],
                    ['name' => 'Clipper Classic', 'quantityLabel' => '×1', 'priceLabel' => '$6.20'],
                ],
            ],
            [
                'id' => 'drv-ord-1007',
                'orderNumber' => 'POM-DRV-1007',
                'status' => 'out_for_delivery',
                'statusLabel' => 'Out for delivery',
                'customerName' => 'Sophie Bennett',
                'areaId' => $surryHills,
                'areaLabel' => 'Surry Hills',
                'addressSummary' => '55 Devonshire St, Surry Hills',
                'windowLabel' => 'Today · 3:30–4:30 PM',
                'productCount' => 2,
                'totalLabel' => '$22.40',
                'phone' => '+61417667788',
                'notes' => null,
                'demoOtpHint' => '774310',
                'shippingAddress' => [
                    'line1' => '55 Devonshire Street',
                    'line2' => null,
                    'city' => 'Surry Hills',
                    'region' => 'NSW',
                    'postalCode' => '2010',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Dunhill Red', 'quantityLabel' => '×2', 'priceLabel' => '$15.00'],
                    ['name' => 'Lucky Strike Original', 'quantityLabel' => '×1', 'priceLabel' => '$7.40'],
                ],
            ],
            [
                'id' => 'drv-ord-1008',
                'orderNumber' => 'POM-DRV-1008',
                'status' => 'arrived',
                'statusLabel' => 'Arrived',
                'customerName' => 'Tom Richter',
                'areaId' => $surryHills,
                'areaLabel' => 'Surry Hills',
                'addressSummary' => '7 Davies St, Surry Hills',
                'windowLabel' => 'Today · 4:00–4:45 PM',
                'productCount' => 1,
                'totalLabel' => '$9.50',
                'phone' => '+61419880011',
                'notes' => 'Buzzer labelled Richter.',
                'demoOtpHint' => '229184',
                'shippingAddress' => [
                    'line1' => '7 Davies Street',
                    'line2' => null,
                    'city' => 'Surry Hills',
                    'region' => 'NSW',
                    'postalCode' => '2010',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Camel Blue', 'quantityLabel' => '×1', 'priceLabel' => '$9.50'],
                ],
            ],
            [
                'id' => 'drv-ord-1005',
                'orderNumber' => 'POM-DRV-1005',
                'status' => 'out_for_delivery',
                'statusLabel' => 'Out for delivery',
                'customerName' => 'Sabrina Walsh',
                'areaId' => $darlinghurst,
                'areaLabel' => 'Darlinghurst',
                'addressSummary' => '41 Oxford St, Darlinghurst',
                'windowLabel' => 'Today · 6:00–7:00 PM',
                'productCount' => 2,
                'totalLabel' => '$15.40',
                'phone' => '+61415556677',
                'notes' => 'Ring the second doorbell.',
                'demoOtpHint' => '918273',
                'shippingAddress' => [
                    'line1' => '41 Oxford Street',
                    'line2' => null,
                    'city' => 'Darlinghurst',
                    'region' => 'NSW',
                    'postalCode' => '2010',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Lucky Strike Original', 'quantityLabel' => '×1', 'priceLabel' => '$7.00'],
                    ['name' => 'Marlboro Gold', 'quantityLabel' => '×1', 'priceLabel' => '$8.40'],
                ],
            ],
            [
                'id' => 'drv-ord-1009',
                'orderNumber' => 'POM-DRV-1009',
                'status' => 'out_for_delivery',
                'statusLabel' => 'Out for delivery',
                'customerName' => 'Daniel Rossi',
                'areaId' => $darlinghurst,
                'areaLabel' => 'Darlinghurst',
                'addressSummary' => '28 Flinders St, Darlinghurst',
                'windowLabel' => 'Today · 6:15–7:00 PM',
                'productCount' => 2,
                'totalLabel' => '$14.80',
                'phone' => '+61414443322',
                'notes' => null,
                'demoOtpHint' => '640821',
                'shippingAddress' => [
                    'line1' => '28 Flinders Street',
                    'line2' => null,
                    'city' => 'Darlinghurst',
                    'region' => 'NSW',
                    'postalCode' => '2010',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Benson & Hedges Gold', 'quantityLabel' => '×1', 'priceLabel' => '$7.00'],
                    ['name' => 'Clipper Classic', 'quantityLabel' => '×1', 'priceLabel' => '$7.80'],
                ],
            ],
            [
                'id' => 'drv-ord-1010',
                'orderNumber' => 'POM-DRV-1010',
                'status' => 'out_for_delivery',
                'statusLabel' => 'Out for delivery',
                'customerName' => 'Hannah Clarke',
                'areaId' => $darlinghurst,
                'areaLabel' => 'Darlinghurst',
                'addressSummary' => '6 Burton St, Darlinghurst',
                'windowLabel' => 'Today · 6:30–7:15 PM',
                'productCount' => 3,
                'totalLabel' => '$19.60',
                'phone' => '+61412229900',
                'notes' => 'Park on Burton; loading zone after 6.',
                'demoOtpHint' => '337560',
                'shippingAddress' => [
                    'line1' => '6 Burton Street',
                    'line2' => 'Apartment 2',
                    'city' => 'Darlinghurst',
                    'region' => 'NSW',
                    'postalCode' => '2010',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Marlboro Gold', 'quantityLabel' => '×1', 'priceLabel' => '$6.00'],
                    ['name' => 'Dunhill Red', 'quantityLabel' => '×1', 'priceLabel' => '$7.50'],
                    ['name' => 'Lucky Strike Original', 'quantityLabel' => '×1', 'priceLabel' => '$6.10'],
                ],
            ],
        ];
    }

    /**
     * Earlier delivered stops. Shown on Old orders, not on today's run.
     *
     * @return list<array<string, mixed>>
     */
    public static function driverPastOrders(): array
    {
        return array_map(
            static function (array $order): array {
                $productPreview = array_values(array_map(
                    static fn (array $product): string => $product['name'],
                    array_slice($order['products'], 0, 2),
                ));

                return [
                    'id' => $order['id'],
                    'orderNumber' => $order['orderNumber'],
                    'status' => 'delivered',
                    'customerName' => $order['customerName'],
                    'areaId' => $order['areaId'],
                    'areaLabel' => $order['areaLabel'],
                    'addressSummary' => $order['addressSummary'],
                    'windowLabel' => $order['windowLabel'],
                    'productCount' => $order['productCount'],
                    'totalLabel' => $order['totalLabel'],
                    'phone' => $order['phone'],
                    'notes' => $order['notes'],
                    'unitLabel' => $order['shippingAddress']['line2'],
                    'productPreview' => $productPreview,
                    'paymentMethod' => $order['id'] === 'drv-ord-0901' ? 'cod' : 'paid',
                    'etaLabel' => null,
                    'completedLabel' => $order['completedLabel'],
                ];
            },
            self::driverPastOrderCatalog(),
        );
    }

    /**
     * Pay, tips, and completed-drop totals for the Earnings tab.
     *
     * @return array{
     *     todayLabel: string,
     *     weekLabel: string,
     *     tipsLabel: string,
     *     perStopLabel: string,
     *     payoutLabel: string,
     *     payoutStatus: string,
     *     codHeldLabel: string,
     *     dropsToday: int,
     *     dropsWeek: int,
     *     days: list<array{
     *         label: string,
     *         totalLabel: string,
     *         entries: list<array{
     *             id: string,
     *             customerName: string,
     *             orderNumber: string,
     *             areaLabel: string,
     *             amountLabel: string,
     *             tipLabel: string,
     *             paymentMethod: string
     *         }>
     *     }>
     * }
     */
    public static function driverEarnings(): array
    {
        $hub = self::driverDashboardHub()['earnings'];
        $todayDelivered = array_values(array_filter(
            self::driverAssignedOrders(),
            static fn (array $order): bool => $order['status'] === 'delivered',
        ));
        $past = self::driverPastOrders();

        $days = [
            [
                'label' => 'Today',
                'totalLabel' => $hub['todayLabel'],
                'entries' => array_map(
                    self::driverEarningEntry(...),
                    $todayDelivered,
                ),
            ],
        ];

        foreach ($past as $order) {
            $label = is_string($order['completedLabel'] ?? null)
                ? $order['completedLabel']
                : 'Earlier';
            $last = $days[array_key_last($days)] ?? null;

            if ($last === null || $last['label'] !== $label) {
                $days[] = [
                    'label' => $label,
                    'totalLabel' => '$0.00',
                    'entries' => [],
                ];
            }

            $days[array_key_last($days)]['entries'][] = self::driverEarningEntry($order);
        }

        $days = array_map(static function (array $day): array {
            $cents = array_sum(array_map(
                static fn (array $entry): int => self::driverMoneyCents($entry['amountLabel']),
                $day['entries'],
            ));

            $day['totalLabel'] = $day['label'] === 'Today'
                ? $day['totalLabel']
                : self::driverMoneyLabel($cents);

            return $day;
        }, $days);

        $codCents = array_sum(array_map(
            static function (array $day): int {
                return array_sum(array_map(
                    static fn (array $entry): int => $entry['paymentMethod'] === 'cod'
                        ? self::driverMoneyCents($entry['amountLabel'])
                        : 0,
                    $day['entries'],
                ));
            },
            $days,
        ));

        return [
            'todayLabel' => $hub['todayLabel'],
            'weekLabel' => $hub['weekLabel'],
            'tipsLabel' => $hub['tipsLabel'],
            'perStopLabel' => $hub['perStopLabel'],
            'payoutLabel' => $hub['todayLabel'],
            'payoutStatus' => 'Clears tonight',
            'codHeldLabel' => self::driverMoneyLabel($codCents),
            'dropsToday' => count($todayDelivered),
            'dropsWeek' => count($todayDelivered) + count($past),
            'days' => $days,
        ];
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array{
     *     id: string,
     *     customerName: string,
     *     orderNumber: string,
     *     areaLabel: string,
     *     amountLabel: string,
     *     tipLabel: string,
     *     paymentMethod: string
     * }
     */
    private static function driverEarningEntry(array $order): array
    {
        return [
            'id' => $order['id'],
            'customerName' => $order['customerName'],
            'orderNumber' => $order['orderNumber'],
            'areaLabel' => $order['areaLabel'],
            'amountLabel' => $order['totalLabel'],
            'tipLabel' => ($order['paymentMethod'] ?? 'paid') === 'cod' ? '$2.00' : '$1.50',
            'paymentMethod' => $order['paymentMethod'],
        ];
    }

    private static function driverMoneyCents(string $label): int
    {
        $digits = preg_replace('/[^\d]/', '', $label) ?? '0';

        return (int) $digits;
    }

    private static function driverMoneyLabel(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }

    /**
     * @return list<array{
     *     id: string,
     *     orderNumber: string,
     *     status: string,
     *     statusLabel: string,
     *     customerName: string,
     *     areaId: string,
     *     areaLabel: string,
     *     addressSummary: string,
     *     windowLabel: string,
     *     productCount: int,
     *     totalLabel: string,
     *     phone: string,
     *     notes: string|null,
     *     demoOtpHint: string,
     *     completedLabel: string,
     *     shippingAddress: array{line1: string, line2: string|null, city: string, region: string, postalCode: string, country: string},
     *     products: list<array{name: string, quantityLabel: string, priceLabel: string}>
     * }>
     */
    private static function driverPastOrderCatalog(): array
    {
        return [
            [
                'id' => 'drv-ord-0901',
                'orderNumber' => 'POM-DRV-0901',
                'status' => 'delivered',
                'statusLabel' => 'Delivered',
                'customerName' => 'Maya Patel',
                'areaId' => 'area-paddington',
                'areaLabel' => 'Paddington',
                'addressSummary' => '22 Oxford St, Paddington',
                'windowLabel' => 'Yesterday · 5:10 PM',
                'completedLabel' => 'Yesterday',
                'productCount' => 2,
                'totalLabel' => '$17.40',
                'phone' => '+61413001122',
                'notes' => null,
                'demoOtpHint' => '551001',
                'shippingAddress' => [
                    'line1' => '22 Oxford Street',
                    'line2' => 'Shop 3',
                    'city' => 'Paddington',
                    'region' => 'NSW',
                    'postalCode' => '2021',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Marlboro Gold', 'quantityLabel' => '×1', 'priceLabel' => '$6.00'],
                    ['name' => 'Camel Blue', 'quantityLabel' => '×2', 'priceLabel' => '$11.40'],
                ],
            ],
            [
                'id' => 'drv-ord-0902',
                'orderNumber' => 'POM-DRV-0902',
                'status' => 'delivered',
                'statusLabel' => 'Delivered',
                'customerName' => 'Jack Brennan',
                'areaId' => 'area-paddington',
                'areaLabel' => 'Paddington',
                'addressSummary' => '8 Glenmore Rd, Paddington',
                'windowLabel' => 'Yesterday · 6:02 PM',
                'completedLabel' => 'Yesterday',
                'productCount' => 1,
                'totalLabel' => '$9.50',
                'phone' => '+61419880011',
                'notes' => 'Left with reception.',
                'demoOtpHint' => '551002',
                'shippingAddress' => [
                    'line1' => '8 Glenmore Road',
                    'line2' => null,
                    'city' => 'Paddington',
                    'region' => 'NSW',
                    'postalCode' => '2021',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Dunhill Red', 'quantityLabel' => '×1', 'priceLabel' => '$9.50'],
                ],
            ],
            [
                'id' => 'drv-ord-0903',
                'orderNumber' => 'POM-DRV-0903',
                'status' => 'delivered',
                'statusLabel' => 'Delivered',
                'customerName' => 'Olivia Hart',
                'areaId' => 'area-potts-point',
                'areaLabel' => 'Potts Point',
                'addressSummary' => '14 Macleay St, Potts Point',
                'windowLabel' => 'Yesterday · 7:21 PM',
                'completedLabel' => 'Yesterday',
                'productCount' => 2,
                'totalLabel' => '$15.80',
                'phone' => '+61417665500',
                'notes' => null,
                'demoOtpHint' => '551003',
                'shippingAddress' => [
                    'line1' => '14 Macleay Street',
                    'line2' => 'Level 6',
                    'city' => 'Potts Point',
                    'region' => 'NSW',
                    'postalCode' => '2011',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Lucky Strike Original', 'quantityLabel' => '×1', 'priceLabel' => '$7.00'],
                    ['name' => 'Clipper Classic', 'quantityLabel' => '×1', 'priceLabel' => '$8.80'],
                ],
            ],
            [
                'id' => 'drv-ord-0881',
                'orderNumber' => 'POM-DRV-0881',
                'status' => 'delivered',
                'statusLabel' => 'Delivered',
                'customerName' => 'Noah Ellis',
                'areaId' => 'area-redfern',
                'areaLabel' => 'Redfern',
                'addressSummary' => '31 Redfern St, Redfern',
                'windowLabel' => 'Fri · 4:40 PM',
                'completedLabel' => 'Friday',
                'productCount' => 3,
                'totalLabel' => '$22.10',
                'phone' => '+61415550990',
                'notes' => null,
                'demoOtpHint' => '448801',
                'shippingAddress' => [
                    'line1' => '31 Redfern Street',
                    'line2' => null,
                    'city' => 'Redfern',
                    'region' => 'NSW',
                    'postalCode' => '2016',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Marlboro Gold', 'quantityLabel' => '×2', 'priceLabel' => '$12.00'],
                    ['name' => 'Benson & Hedges Gold', 'quantityLabel' => '×1', 'priceLabel' => '$10.10'],
                ],
            ],
            [
                'id' => 'drv-ord-0882',
                'orderNumber' => 'POM-DRV-0882',
                'status' => 'delivered',
                'statusLabel' => 'Delivered',
                'customerName' => 'Grace Liu',
                'areaId' => 'area-redfern',
                'areaLabel' => 'Redfern',
                'addressSummary' => '102 Chalmers St, Redfern',
                'windowLabel' => 'Fri · 5:18 PM',
                'completedLabel' => 'Friday',
                'productCount' => 1,
                'totalLabel' => '$8.90',
                'phone' => '+61416667788',
                'notes' => null,
                'demoOtpHint' => '448802',
                'shippingAddress' => [
                    'line1' => '102 Chalmers Street',
                    'line2' => 'Unit 12',
                    'city' => 'Redfern',
                    'region' => 'NSW',
                    'postalCode' => '2016',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Camel Blue', 'quantityLabel' => '×1', 'priceLabel' => '$8.90'],
                ],
            ],
            [
                'id' => 'drv-ord-0870',
                'orderNumber' => 'POM-DRV-0870',
                'status' => 'delivered',
                'statusLabel' => 'Delivered',
                'customerName' => 'Leo Walsh',
                'areaId' => 'area-newtown',
                'areaLabel' => 'Newtown',
                'addressSummary' => '255 King St, Newtown',
                'windowLabel' => 'Thu · 3:55 PM',
                'completedLabel' => 'Thursday',
                'productCount' => 2,
                'totalLabel' => '$16.20',
                'phone' => '+61419990001',
                'notes' => 'Buzzer 14.',
                'demoOtpHint' => '337001',
                'shippingAddress' => [
                    'line1' => '255 King Street',
                    'line2' => null,
                    'city' => 'Newtown',
                    'region' => 'NSW',
                    'postalCode' => '2042',
                    'country' => 'Australia',
                ],
                'products' => [
                    ['name' => 'Dunhill Red', 'quantityLabel' => '×1', 'priceLabel' => '$7.50'],
                    ['name' => 'Lucky Strike Original', 'quantityLabel' => '×1', 'priceLabel' => '$8.70'],
                ],
            ],
        ];
    }

    /**
     * Demo customer notifications, newest first.
     *
     * @return list<array{id: string, title: string, description: string, createdAt: string, read: bool, type: string, body: string}>
     */
    public static function customerNotifications(): array
    {
        return [
            [
                'id' => 'notif-1',
                'title' => 'Order shipped',
                'description' => 'Your order #ORD-CP2026-001 is on the way from Williamsburg Branch.',
                'createdAt' => '2 hours ago',
                'read' => false,
                'type' => 'order',
                'body' => "Your order #ORD-CP2026-001 has left Williamsburg Branch and is heading your way.\n\nYou can track delivery progress from your Orders page. Keep your phone nearby for driver updates.",
            ],
            [
                'id' => 'notif-2',
                'title' => 'Payment received',
                'description' => 'We confirmed payment for order #ORD-CP2026-002.',
                'createdAt' => 'Yesterday',
                'read' => true,
                'type' => 'payment',
                'body' => "Thanks — payment for order #ORD-CP2026-002 was received successfully.\n\nThe branch is preparing your items. You will get another update when a driver is assigned.",
            ],
            [
                'id' => 'notif-3',
                'title' => 'Driver assigned',
                'description' => 'A driver has been assigned to your delivery window today.',
                'createdAt' => '2 days ago',
                'read' => false,
                'type' => 'delivery',
                'body' => "A driver is assigned for your upcoming delivery.\n\nEstimated window: today between 4:00–6:00 PM. You can message or call from the order tracking page once the delivery is out for delivery.",
            ],
            [
                'id' => 'notif-4',
                'title' => 'Order delivered',
                'description' => 'Order #ORD-CP2026-003 was delivered. Leave a product review?',
                'createdAt' => '3 days ago',
                'read' => true,
                'type' => 'delivery',
                'body' => "Order #ORD-CP2026-003 was marked delivered.\n\nIf everything looks good, you can leave a product review from Product to review in your account menu.",
            ],
            [
                'id' => 'notif-5',
                'title' => 'Promo: packs & saves',
                'description' => 'Limited packs are back in stock near your branch this week.',
                'createdAt' => '5 days ago',
                'read' => false,
                'type' => 'promo',
                'body' => "Selected packs & saves are available again at nearby branches.\n\nOpen Shop → Packs & Saves to browse current offers. Age verification still applies at delivery.",
            ],
            [
                'id' => 'notif-6',
                'title' => 'Account security',
                'description' => 'A new sign-in was recorded on your customer account.',
                'createdAt' => '1 week ago',
                'read' => true,
                'type' => 'security',
                'body' => "We noticed a new sign-in on your customer account.\n\nIf this was you, no action is needed. If you do not recognize it, reset your password from Account settings.",
            ],
        ];
    }

    /**
     * @return array{id: string, title: string, description: string, createdAt: string, read: bool, type: string, body: string}|null
     */
    public static function customerNotificationDetail(string $id): ?array
    {
        $notification = collect(self::customerNotifications())->firstWhere('id', $id);

        return $notification;
    }
}
