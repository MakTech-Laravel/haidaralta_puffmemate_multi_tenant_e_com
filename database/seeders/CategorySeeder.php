<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Support\Storefront\StorefrontCatalog;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Database\Seeders\Concerns\SeedsCatalogMedia;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use ReportsSeedProgress;
    use SeedsCatalogMedia;

    /**
     * Seed catalog categories (featured homepage set + vapes children). No Faker.
     * Images live under public/images. DB stores /images/... web paths.
     */
    public function run(): void
    {
        $this->clearObsoleteSeedStorage();

        $roots = [
            [
                'name' => 'Cigarettes',
                'slug' => 'cigarettes',
                'parent_id' => null,
                'featured' => true,
                'show_in_nav' => true,
                'nav_sort' => 1,
                'slogan' => 'Premium International Brands',
                'image' => 'images/storefront/category-cigarettes.png',
                'color_code' => ['mode' => 'solid', 'color' => '#F5E6D3'],
            ],
            [
                'name' => 'Vapes & Disposables',
                'slug' => 'vapes',
                'parent_id' => null,
                'featured' => true,
                'show_in_nav' => true,
                'nav_sort' => 3,
                'slogan' => 'Disposable & Rechargeable',
                'image' => 'images/storefront/category-vapes.png',
                'color_code' => ['mode' => 'solid', 'color' => '#E8F0FE'],
            ],
            [
                'name' => 'Tobacco',
                'slug' => 'tobacco',
                'parent_id' => null,
                'featured' => true,
                'show_in_nav' => true,
                'nav_sort' => 2,
                'slogan' => 'Rolling & Specialty Blends',
                'image' => 'images/storefront/category-tobacco.png',
                'color_code' => [
                    'mode' => 'gradient',
                    'from' => '#1F2937',
                    'to' => '#6B7280',
                    'angle' => 140,
                ],
            ],
            [
                'name' => 'Nicotine Pouches',
                'slug' => 'nicotine-pouches',
                'parent_id' => null,
                'featured' => true,
                'show_in_nav' => true,
                'nav_sort' => 4,
                'slogan' => 'Smoke free alternatives',
                'image' => 'images/storefront/category-pouches.png',
                'color_code' => ['mode' => 'solid', 'color' => '#FEF3C7'],
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'parent_id' => null,
                'featured' => true,
                'show_in_nav' => false,
                'slogan' => 'Everything you need to complete your setup',
                'image' => 'images/storefront/category-accessories.png',
                'color_code' => ['mode' => 'solid', 'color' => '#F1F5F9'],
            ],
        ];

        $vapesChildren = [
            [
                'name' => 'Disposables',
                'slug' => 'disposables',
                'featured' => false,
                'show_in_nav' => false,
                'image' => 'images/storefront/category-vapes.png',
            ],
            [
                'name' => 'Pod Systems',
                'slug' => 'pod-systems',
                'featured' => false,
                'show_in_nav' => false,
                'image' => 'images/storefront/category-vapes.png',
            ],
        ];

        $this->startSeedProgress('Categories', count($roots) + count($vapesChildren));

        $seededRoots = [];

        foreach ($roots as $root) {
            $seededRoots[$root['slug']] = $this->category($root);
            $this->advanceSeedProgress();
        }

        foreach ($vapesChildren as $child) {
            $this->category([...$child, 'parent_id' => $seededRoots['vapes']->id]);
            $this->advanceSeedProgress();
        }

        StorefrontCatalog::forgetNavCategories();

        $this->finishSeedProgress(sprintf('%d core categories ready.', count($roots) + count($vapesChildren)));
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

        $attributes = [
            'name' => $data['name'],
            'parent_id' => $data['parent_id'],
            'slogan' => $data['slogan'] ?? null,
            'color_code' => $data['color_code'] ?? null,
            'status' => true,
            'featured' => $data['featured'] ?? false,
            'show_in_nav' => $data['show_in_nav'] ?? false,
            'nav_sort' => $data['nav_sort'] ?? null,
        ];

        if ($imagePath !== null) {
            $attributes['image'] = $imagePath;
        }

        return Category::query()->updateOrCreate(
            ['slug' => $data['slug']],
            $attributes,
        );
    }
}
