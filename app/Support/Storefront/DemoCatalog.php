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

    /**
     * @return list<array{id: string, name: string}>
     */
    public static function branches(): array
    {
        return [
            ['id' => 'downtown', 'name' => 'Downtown'],
            ['id' => 'airport', 'name' => 'Airport'],
            ['id' => 'harbour', 'name' => 'Harbour'],
            ['id' => 'westside', 'name' => 'Westside'],
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
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
            [
                'id' => '9',
                'name' => 'Marlboro Gold',
                'description' => 'Gold Pack • 20 Cigarettes',
                'price' => 'Start from $14.00',
                'stock' => 43,
                'category' => 'Cigarettes',
                'image' => '/images/storefront/product-marlboro-gold.jpg',
            ],
        ];
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
            $matched = array_map(
                function (array $product) use ($name): array {
                    $product['category'] = $name;

                    return $product;
                },
                array_slice(self::products(), 0, 8),
            );
        }

        $grid = [];

        while (count($grid) < 8) {
            foreach ($matched as $index => $product) {
                $copy = $product;
                $copy['id'] = $product['id'].'-'.(count($grid) + 1).'-'.$index;
                $grid[] = $copy;

                if (count($grid) >= 50) {
                    break;
                }
            }
        }

        return $grid;
    }
}
