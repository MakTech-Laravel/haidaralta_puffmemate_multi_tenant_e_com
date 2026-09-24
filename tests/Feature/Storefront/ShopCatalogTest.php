<?php

use App\Enums\ProductTag;
use App\Models\Category;
use App\Models\PriceRange;
use App\Models\Product;
use Database\Seeders\ApplicationSettingsSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\CmsPageSeeder;
use Database\Seeders\HomeBannerSeeder;
use Database\Seeders\PriceRangeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(ApplicationSettingsSeeder::class);
    $this->seed(CmsPageSeeder::class);
    $this->seed(HomeBannerSeeder::class);
    $this->seed(CatalogSeeder::class);
    $this->seed(PriceRangeSeeder::class);
});

test('storefront shop serves catalog products without branch select props', function () {
    $this->get(route('shop'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/shop')
            ->has('products')
            ->has('filterOptions.categories')
            ->has('filterOptions.tags', 4)
            ->has('filterOptions.priceRanges')
            ->where('filterOptions.tags.0.value', ProductTag::NewArrival->value)
            ->missing('branches')
            ->where('filters.search', '')
            ->where('filters.sort', null)
            ->where('filters.categories', [])
            ->where('filters.tags', []));
});

test('storefront shop filters products by search', function () {
    $this->get(route('shop', ['search' => 'Marlboro']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/shop')
            ->where('filters.search', 'Marlboro')
            ->where('products.0.id', 'marlboro-gold-soft-pack-20s')
            ->has('products', 1));
});

test('storefront shop filters by category slug tag and price', function () {
    $category = Category::query()->where('slug', 'cigarettes')->firstOrFail();

    Product::query()->get()->each(fn (Product $product) => $product->update([
        'tags' => [],
        'selling_price' => 99,
        'regular_price' => 110,
    ]));

    $match = Product::factory()->create([
        'name' => 'Shop Filter Match',
        'slug' => 'shop-filter-match',
        'status' => true,
        'tags' => [ProductTag::BestSelling->value],
        'selling_price' => 15,
        'regular_price' => 18,
    ]);
    $match->categories()->sync([$category->id]);

    Product::factory()->create([
        'name' => 'Shop Filter Other Tag',
        'slug' => 'shop-filter-other-tag',
        'status' => true,
        'tags' => [ProductTag::NewArrival->value],
        'selling_price' => 15,
    ])->categories()->sync([$category->id]);

    Product::factory()->create([
        'name' => 'Shop Filter Expensive',
        'slug' => 'shop-filter-expensive',
        'status' => true,
        'tags' => [ProductTag::BestSelling->value],
        'selling_price' => 80,
    ])->categories()->sync([$category->id]);

    $this->get(route('shop', [
        'categories' => ['cigarettes'],
        'tags' => [ProductTag::BestSelling->value],
        'min_price' => 10,
        'max_price' => 20,
        'sort' => 'price_asc',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/shop')
            ->where('filters.categories', ['cigarettes'])
            ->where('filters.tags', [ProductTag::BestSelling->value])
            ->where('filters.minPrice', 10)
            ->where('filters.maxPrice', 20)
            ->where('filters.sort', 'price_asc')
            ->has('products', 1)
            ->where('products.0.id', 'shop-filter-match'));
});

test('inactive price ranges are omitted from shop filter options', function () {
    PriceRange::query()->update(['status' => false]);

    PriceRange::factory()->create([
        'label' => 'Active Band',
        'slug' => 'active-band',
        'min_amount' => 5,
        'max_amount' => 25,
        'status' => true,
        'sort_order' => 1,
    ]);

    $this->get(route('shop'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('filterOptions.priceRanges', 1)
            ->where('filterOptions.priceRanges.0.slug', 'active-band'));
});
