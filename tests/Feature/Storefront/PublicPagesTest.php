<?php

use App\Models\Category;
use App\Models\CmsPage;
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

test('storefront shop page serves DemoCatalog products', function () {
    $this->get(route('shop'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/shop')
            ->where('products.0.id', '1')
            ->where('products.0.name', 'Marlboro Gold')
            ->where('products.0.price', '$14.00')
            ->where('products.0.compareAtPrice', '$20.00')
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
            ->where('products.0.id', '1')
            ->where('products.0.name', 'Marlboro Gold')
            ->has('products', 1));
});

test('storefront category page renders products for a valid slug', function () {
    $this->get(route('shop.category', ['category' => 'cigarettes']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/category')
            ->where('category.slug', 'cigarettes')
            ->where('category.name', 'Cigarettes')
            ->where('products.0.id', '1')
            ->where('products.0.name', 'Marlboro Gold')
            ->where('products.0.price', '$14.00')
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
    $this->get(route('products.show', ['product' => '1']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/product')
            ->where('product.id', '1')
            ->where('product.name', 'Marlboro Gold')
            ->has('product.specifications')
            ->has('similarProducts')
            ->has('branches')
            ->has('branchId')
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
