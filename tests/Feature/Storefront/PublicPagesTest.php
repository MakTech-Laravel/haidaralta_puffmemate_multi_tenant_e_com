<?php

use App\Models\Category;
use Database\Seeders\BranchSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
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
]);

test('storefront home shares featured categories and tagged products', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/home')
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

test('nav categories respect show_in_nav and configured max', function () {
    expect(Category::query()->where('show_in_nav', true)->count())
        ->toBeLessThanOrEqual((int) config('catalog.nav_categories_max'));

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('storefront.navCategories'));
});
