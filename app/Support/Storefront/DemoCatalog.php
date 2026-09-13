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
            ['id' => 'downtown', 'name' => 'Downtown'],
            ['id' => 'airport', 'name' => 'Airport'],
            ['id' => 'harbour', 'name' => 'Harbour'],
            ['id' => 'westside', 'name' => 'West Village Branch'],
        ];
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, stock: int, category: string, image: string}>
     */
    public static function products(): array
    {
        return [
            [
                'id' => '1',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 40,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '2',
                'name' => 'Winfield Blue',
                'description' => 'Blue Pack • 20 Cigarettes',
                'price' => 'Start from $13.50',
                'stock' => 28,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-winfield-blue.jpg',
            ],
            [
                'id' => '3',
                'name' => 'IQOS Iluma',
                'description' => 'Device Kit • Heat-not-burn',
                'price' => 'Start from $89.00',
                'stock' => 12,
                'category' => 'Vapes',
                'image' => '/images/storefront/product-iqos.png',
            ],
            [
                'id' => '4',
                'name' => 'Vuse Go',
                'description' => 'Disposable • Mixed Berry',
                'price' => 'Start from $18.00',
                'stock' => 56,
                'category' => 'Vapes',
                'image' => '/images/storefront/product-vuse.png',
            ],
            [
                'id' => '5',
                'name' => 'Peter Stuyvesant Red',
                'description' => 'Red Pack • 20 Cigarettes',
                'price' => 'Start from $13.00',
                'stock' => 31,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '6',
                'name' => 'Horizon Menthol',
                'description' => 'Menthol Pack • 20 Cigarettes',
                'price' => 'Start from $12.50',
                'stock' => 19,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-winfield-blue.jpg',
            ],
            [
                'id' => '7',
                'name' => 'ZYN Cool Mint',
                'description' => 'Nicotine Pouches • 15 pcs',
                'price' => 'Start from $9.90',
                'stock' => 64,
                'category' => 'Nicotine Pouches',
                'image' => '/images/storefront/product-iqos.png',
            ],
            [
                'id' => '8',
                'name' => 'Clipper Classic',
                'description' => 'Refillable Lighter',
                'price' => 'Start from $4.00',
                'stock' => 120,
                'category' => 'Accessories',
                'image' => '/images/storefront/product-vuse.png',
            ],
            [
                'id' => '9',
                'name' => 'Dunhill Red',
                'description' => 'Red Pack • 20 Cigarettes',
                'price' => 'Start from $15.00',
                'stock' => 22,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '10',
                'name' => 'Benson & Hedges Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.50',
                'stock' => 35,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-winfield-blue.jpg',
            ],
            [
                'id' => '11',
                'name' => 'Lucky Strike Original',
                'description' => 'Original Pack • 20 Cigarettes',
                'price' => 'Start from $13.80',
                'stock' => 27,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '12',
                'name' => 'Camel Blue',
                'description' => 'Blue Pack • 20 Cigarettes',
                'price' => 'Start from $13.20',
                'stock' => 30,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-winfield-blue.jpg',
            ],
        ];
    }

    /**
     * @return array{id: string, name: string, description: string, price: string, stock: int, category: string, image: string}
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
     * @return list<array{id: string, name: string, description: string, price: string, stock: int, category: string, image: string}>
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
     * @return list<array{id: string, name: string, description: string, price: string, stock: int, category: string, image: string}>
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

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     description: string,
     *     category: string,
     *     categorySlug: string|null,
     *     badges: list<string>,
     *     images: list<string>,
     *     price: string,
     *     compareAtPrice: string,
     *     availability: string,
     *     rating: float,
     *     reviewCount: int,
     *     about: string,
     *     branch: string,
     *     stock: int,
     *     cardPrice: string,
     *     image: string,
     *     specifications: list<array{label: string, value: string}>,
     *     shippingPoints: list<string>,
     *     responsiblePoints: list<string>,
     *     reviews: list<array{id: string, author: string, date: string, rating: int, body: string}>
     * }
     */
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
            'images' => [$image, $image, $image],
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
}
