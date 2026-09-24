<?php

use App\Enums\ProductTag;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Product;
use App\Models\User;
use App\Services\Cms\CmsPageService;
use App\Support\Storefront\StorefrontCatalog;
use Database\Seeders\ApplicationSettingsSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\CmsPageSeeder;
use Database\Seeders\HomeBannerSeeder;
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
});

test('public storefront pages render', function (string $route, string $component) {
    $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'home' => ['home', 'storefront/home'],
    'shop' => ['shop', 'storefront/shop'],
    'about' => ['about', 'storefront/about'],
    'faqs' => ['faqs', 'storefront/faq'],
    'contact' => ['contact', 'storefront/contact'],
    'wholesale' => ['wholesale', 'storefront/wholesale'],
    'privacy' => ['privacy', 'storefront/privacy'],
    'terms' => ['terms', 'storefront/terms'],
]);

test('unpublished cms page returns 404', function () {
    CmsPage::query()->where('slug', 'about')->update(['is_published' => false]);
    app(CmsPageService::class)->refresh('about');

    $this->get(route('about'))->assertNotFound();
});

test('storefront home shares portal dashboard url for signed-in staff', function () {
    $user = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($user, 'platform')
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.dashboardUrl', '/platform/dashboard'));
});

test('storefront home shares featured categories and tagged products', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/home')
            ->has('heroSlides', 2)
            ->has('featuredCategories')
            ->has('bestSellingProducts')
            ->has('newArrivalProducts')
            ->has('storefront.navCategories'));
});

test('storefront home featured categories come from the catalog database', function () {
    Category::query()->update(['featured' => false]);

    Category::factory()->create([
        'name' => 'Featured Alpha',
        'slug' => 'featured-alpha',
        'status' => true,
        'featured' => true,
        'slogan' => 'Alpha slogan',
        'image' => '/images/storefront/category-cigarettes.png',
    ]);
    Category::factory()->create([
        'name' => 'Featured Beta',
        'slug' => 'featured-beta',
        'status' => true,
        'featured' => true,
        'slogan' => 'Beta slogan',
        'image' => '/images/storefront/category-vapes.png',
    ]);
    Category::factory()->create([
        'name' => 'Not Featured',
        'slug' => 'not-featured',
        'status' => true,
        'featured' => false,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/home')
            ->has('featuredCategories', 2)
            ->where('featuredCategories.0.id', 'featured-alpha')
            ->where('featuredCategories.0.name', 'Featured Alpha')
            ->where('featuredCategories.0.description', 'Alpha slogan')
            ->where('featuredCategories.0.href', '/shop/featured-alpha')
            ->where('featuredCategories.0.wide', false)
            ->where('featuredCategories.1.id', 'featured-beta')
            ->where('featuredCategories.1.wide', false));
});

test('storefront home best selling and new arrival products come from catalog tags', function () {
    Product::query()->get()->each(fn (Product $product) => $product->update(['tags' => []]));

    Product::factory()->create([
        'name' => 'Tagged Best Seller',
        'slug' => 'tagged-best-seller',
        'status' => true,
        'tags' => [ProductTag::BestSelling->value],
        'selling_price' => 14.00,
        'pack_label' => 'Gold Pack',
        'pieces_per_pack' => 20,
        'pack_unit' => 'Cigarettes',
    ]);
    Product::factory()->create([
        'name' => 'Tagged New Arrival',
        'slug' => 'tagged-new-arrival',
        'status' => true,
        'tags' => [ProductTag::NewArrival->value],
        'selling_price' => 9.90,
        'pack_label' => 'Cool Mint',
        'pieces_per_pack' => 15,
        'pack_unit' => 'pcs',
    ]);
    Product::factory()->create([
        'name' => 'Untagged Product',
        'slug' => 'untagged-product',
        'status' => true,
        'tags' => [],
        'selling_price' => 5.00,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/home')
            ->has('bestSellingProducts', 1)
            ->where('bestSellingProducts.0.id', 'tagged-best-seller')
            ->where('bestSellingProducts.0.name', 'Tagged Best Seller')
            ->where('bestSellingProducts.0.description', 'Gold Pack • 20 Cigarettes')
            ->where('bestSellingProducts.0.price', '$14.00')
            ->where('bestSellingProducts.0', fn ($card): bool => ! array_key_exists('stock', collect($card)->all()))
            ->has('newArrivalProducts', 1)
            ->where('newArrivalProducts.0.id', 'tagged-new-arrival')
            ->where('newArrivalProducts.0.description', 'Cool Mint • 15 pcs')
            ->where('newArrivalProducts.0.price', '$9.90')
            ->where('newArrivalProducts.0', fn ($card): bool => ! array_key_exists('stock', collect($card)->all())));
});

test('storefront home product rails respect home_products_max per tag', function () {
    config(['catalog.home_products_max' => 3]);

    Product::query()->get()->each(fn (Product $product) => $product->update(['tags' => []]));

    foreach (range(1, 5) as $index) {
        Product::factory()->create([
            'name' => "Best Seller {$index}",
            'slug' => "best-seller-{$index}",
            'status' => true,
            'tags' => [ProductTag::BestSelling->value],
            'selling_price' => 10 + $index,
        ]);
        Product::factory()->create([
            'name' => "New Arrival {$index}",
            'slug' => "new-arrival-{$index}",
            'status' => true,
            'tags' => [ProductTag::NewArrival->value],
            'selling_price' => 20 + $index,
        ]);
    }

    Product::factory()->create([
        'name' => 'Inactive Best Seller',
        'slug' => 'inactive-best-seller',
        'status' => false,
        'tags' => [ProductTag::BestSelling->value],
        'selling_price' => 99,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/home')
            ->has('bestSellingProducts', 3)
            ->has('newArrivalProducts', 3)
            ->where('bestSellingProducts', fn ($products): bool => collect($products)->every(
                fn ($product): bool => str_starts_with((string) $product['id'], 'best-seller-'),
            ))
            ->where('newArrivalProducts', fn ($products): bool => collect($products)->every(
                fn ($product): bool => str_starts_with((string) $product['id'], 'new-arrival-'),
            )));
});

test('storefront product cards omit stock and use catalog price without a branch', function () {
    $product = Product::factory()->create([
        'name' => 'Card Mapper Pack',
        'slug' => 'card-mapper-pack',
        'status' => true,
        'selling_price' => 12.50,
        'regular_price' => 15.00,
        'pack_label' => 'Menthol Pack',
        'pieces_per_pack' => 20,
        'pack_unit' => 'Cigarettes',
        'brand' => 'Should Not Appear',
        'description' => 'Fallback description should not win over pack summary.',
    ]);

    $card = StorefrontCatalog::mapProductCard($product);

    expect($card)->not->toHaveKey('stock')
        ->and($card['id'])->toBe('card-mapper-pack')
        ->and($card['description'])->toBe('Menthol Pack • 20 Cigarettes')
        ->and($card['price'])->toBe('$12.50')
        ->and($card['compareAtPrice'])->toBe('$15.00');
});

test('storefront home marks the last two featured categories as wide when more than three', function () {
    Category::query()->update(['featured' => false]);

    foreach (['one', 'two', 'three', 'four', 'five'] as $index => $slug) {
        Category::factory()->create([
            'name' => 'Featured '.ucfirst($slug),
            'slug' => 'featured-'.$slug,
            'status' => true,
            'featured' => true,
            'slogan' => 'Slogan '.$slug,
        ]);
    }

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('featuredCategories', 5)
            ->where('featuredCategories.0.wide', false)
            ->where('featuredCategories.1.wide', false)
            ->where('featuredCategories.2.wide', false)
            ->where('featuredCategories.3.wide', true)
            ->where('featuredCategories.4.wide', true));
});

test('storefront shop page serves catalog products from the database', function () {
    $this->get(route('shop'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/shop')
            ->has('products')
            ->where('products', fn ($products): bool => collect($products)->contains(
                fn ($product): bool => $product['id'] === 'marlboro-gold-soft-pack-20s',
            ))
            ->where('products.0', fn ($card): bool => ! array_key_exists('stock', collect($card)->all()))
            ->has('branches')
            ->where('filters.search', '')
            ->where('filters.branchId', null)
            ->where('filters.sort', null));
});

test('storefront shop page filters products by search', function () {
    $this->get(route('shop', ['search' => 'Marlboro']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/shop')
            ->where('filters.search', 'Marlboro')
            ->where('products.0.id', 'marlboro-gold-soft-pack-20s')
            ->where('products.0.name', 'Marlboro Gold Soft Pack 20s')
            ->has('products', 1));
});

test('storefront category page renders products for a valid slug', function () {
    $this->get(route('shop.category', ['category' => 'cigarettes']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/category')
            ->where('category.slug', 'cigarettes')
            ->where('category.name', 'Cigarettes')
            ->has('products')
            ->where('products', fn ($products): bool => collect($products)->contains(
                fn ($product): bool => $product['id'] === 'marlboro-gold-soft-pack-20s',
            ))
            ->has('branches')
            ->where('filters.search', '')
            ->where('filters.branchId', null)
            ->where('filters.sort', null));
});

test('storefront category page returns not found for unknown slug', function () {
    $this->get(route('shop.category', ['category' => 'unknown-category']))
        ->assertNotFound();
});

test('storefront product details page renders for a valid product', function () {
    $this->get(route('products.show', ['product' => 'marlboro-gold-soft-pack-20s']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/product')
            ->where('product.id', 'marlboro-gold-soft-pack-20s')
            ->where('product.name', 'Marlboro Gold Soft Pack 20s')
            ->has('product.specifications')
            ->has('similarProducts')
            ->missing('branches')
            ->missing('branchId')
            ->has('backHref'));
});

test('storefront product details page returns not found for unknown product', function () {
    $this->get(route('products.show', ['product' => 'missing-product']))
        ->assertNotFound();
});

test('storefront remembers selected branch in session from query string', function () {
    $this->get(route('shop', ['branch' => BranchSeeder::SYDNEY_CBD]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.branchId', BranchSeeder::SYDNEY_CBD));

    $this->get(route('shop'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.branchId', BranchSeeder::SYDNEY_CBD));
});

test('storefront cart page renders', function () {
    $this->get(route('cart'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/cart')
            ->has('groups')
            ->has('summary')
            ->has('branches')
            ->where('stockReserved', true));
});

test('storefront checkout page renders', function () {
    $this->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout')
            ->has('items')
            ->has('summary')
            ->has('deliveryMethods')
            ->has('paymentMethods')
            ->has('timeSlots')
            ->where('itemCount', 2));
});

test('nav categories respect show_in_nav and nav_sort order', function () {
    StorefrontCatalog::forgetNavCategories();

    Category::query()->update(['show_in_nav' => false, 'nav_sort' => null]);

    Category::factory()->create([
        'name' => 'Zulu Nav',
        'slug' => 'zulu-nav',
        'status' => true,
        'show_in_nav' => true,
        'nav_sort' => 2,
    ]);
    Category::factory()->create([
        'name' => 'Alpha Nav',
        'slug' => 'alpha-nav',
        'status' => true,
        'show_in_nav' => true,
        'nav_sort' => 1,
    ]);
    Category::factory()->create([
        'name' => 'Hidden Nav',
        'slug' => 'hidden-nav',
        'status' => true,
        'show_in_nav' => false,
        'nav_sort' => 1,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('storefront.navCategories', 2)
            ->where('storefront.navCategories.0.categorySlug', 'alpha-nav')
            ->where('storefront.navCategories.0.label', 'Alpha Nav')
            ->where('storefront.navCategories.1.categorySlug', 'zulu-nav')
            ->where('storefront.navCategories.1.label', 'Zulu Nav'));
});

test('nav categories are served from cache after the first resolve', function () {
    StorefrontCatalog::forgetNavCategories();

    Category::query()->update(['show_in_nav' => false]);
    Category::factory()->create([
        'name' => 'Cached Nav',
        'slug' => 'cached-nav',
        'status' => true,
        'show_in_nav' => true,
        'nav_sort' => 1,
    ]);

    expect(StorefrontCatalog::navCategories())->toEqual([
        ['label' => 'Cached Nav', 'categorySlug' => 'cached-nav'],
    ]);

    Category::query()->where('slug', 'cached-nav')->update(['name' => 'Renamed Without Forget']);

    expect(StorefrontCatalog::navCategories())->toEqual([
        ['label' => 'Cached Nav', 'categorySlug' => 'cached-nav'],
    ]);

    StorefrontCatalog::forgetNavCategories();

    expect(StorefrontCatalog::navCategories())->toEqual([
        ['label' => 'Renamed Without Forget', 'categorySlug' => 'cached-nav'],
    ]);
});
