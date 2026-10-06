<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Support\Storefront\StorefrontCatalog;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Database\Seeders\Concerns\SeedsCatalogMedia;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExtendedCategorySeeder extends Seeder
{
    use ReportsSeedProgress;
    use SeedsCatalogMedia;
    use WithoutModelEvents;

    /**
     * Seed browsing sub-categories under the featured roots from CategorySeeder.
     * Children stay out of the featured set and header nav (featured/nav caps live on the roots).
     */
    public function run(): void
    {
        $children = $this->children();
        $roots = Category::query()->whereIn('slug', array_keys($children))->pluck('id', 'slug');

        $this->startSeedProgress('Categories', array_sum(array_map('count', $children)));

        foreach ($children as $rootSlug => $rows) {
            $parentId = $roots[$rootSlug] ?? null;

            foreach ($rows as $row) {
                if ($parentId !== null) {
                    $this->childCategory($parentId, $row);
                }

                $this->advanceSeedProgress();
            }
        }

        StorefrontCatalog::forgetNavCategories();

        $this->finishSeedProgress(sprintf('%d categories in catalog.', Category::query()->count()));
    }

    /**
     * @return array<string, list<array{name: string, slug: string, image: string}>>
     */
    private function children(): array
    {
        return [
            'cigarettes' => [
                ['name' => 'Premium Cigarettes', 'slug' => 'premium-cigarettes', 'image' => 'images/storefront/category-cigarettes.png'],
                ['name' => 'Everyday & Value Cigarettes', 'slug' => 'value-cigarettes', 'image' => 'images/storefront/category-cigarettes.png'],
                ['name' => 'Large Packs (30s–50s)', 'slug' => 'large-packs', 'image' => 'images/storefront/category-cigarettes.png'],
            ],
            'tobacco' => [
                ['name' => 'Rolling Tobacco', 'slug' => 'rolling-tobacco', 'image' => 'images/storefront/category-tobacco.png'],
                ['name' => 'Heated Tobacco', 'slug' => 'heated-tobacco', 'image' => 'images/storefront/product-iqos.png'],
                ['name' => 'Cigars & Cigarillos', 'slug' => 'cigars-cigarillos', 'image' => 'images/storefront/category-tobacco.png'],
                ['name' => 'Pipe Tobacco', 'slug' => 'pipe-tobacco', 'image' => 'images/storefront/category-tobacco.png'],
            ],
            'vapes' => [
                ['name' => 'Pods & Coils', 'slug' => 'pods-coils', 'image' => 'images/catalog/products/core-closed-pods-2-pack.jpg'],
                ['name' => 'E-Liquids', 'slug' => 'e-liquids', 'image' => 'images/catalog/products/harbor-fruit-blend-60ml.jpg'],
            ],
            'nicotine-pouches' => [
                ['name' => 'Mint Pouches', 'slug' => 'mint-pouches', 'image' => 'images/storefront/category-pouches.png'],
                ['name' => 'Flavoured Pouches', 'slug' => 'flavoured-pouches', 'image' => 'images/storefront/category-pouches.png'],
            ],
            'accessories' => [
                ['name' => 'Lighters', 'slug' => 'lighters', 'image' => 'images/catalog/products/clipper-classic-lighter.jpg'],
                ['name' => 'Rolling Papers', 'slug' => 'rolling-papers', 'image' => 'images/catalog/products/raw-classic-rolling-papers-ks.jpg'],
                ['name' => 'Filters & Tips', 'slug' => 'filters-tips', 'image' => 'images/catalog/products/ocb-slim-filter-tips.jpg'],
                ['name' => 'Rolling Machines', 'slug' => 'rolling-machines', 'image' => 'images/catalog/products/raw-classic-rolling-papers-ks.jpg'],
                ['name' => 'Cases & Storage', 'slug' => 'cases-storage', 'image' => 'images/catalog/products/travel-hard-case.jpg'],
                ['name' => 'Batteries & Chargers', 'slug' => 'batteries-chargers', 'image' => 'images/catalog/products/usb-c-charge-cable-1m.jpg'],
            ],
        ];
    }

    /**
     * @param  array{name: string, slug: string, image: string}  $row
     */
    private function childCategory(int $parentId, array $row): Category
    {
        $attributes = [
            'name' => $row['name'],
            'parent_id' => $parentId,
            'status' => true,
            'featured' => false,
            'show_in_nav' => false,
            'nav_sort' => null,
        ];

        $category = Category::query()->updateOrCreate(['slug' => $row['slug']], $attributes);

        $this->attachSeedMedia($category, 'image', $this->publicWebPath($row['image']));

        return $category;
    }
}
