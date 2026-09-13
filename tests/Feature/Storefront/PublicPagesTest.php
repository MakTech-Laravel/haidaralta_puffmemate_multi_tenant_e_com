<?php

use Inertia\Testing\AssertableInertia as Assert;

test('public storefront pages render', function (string $route, string $component) {
    $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'home' => ['home', 'storefront/home'],
    'shop' => ['shop', 'storefront/shop'],
    'about' => ['about', 'storefront/about'],
    'contact' => ['contact', 'storefront/contact'],
]);

test('storefront category page renders products for a valid slug', function () {
    $this->get(route('shop.category', ['category' => 'cigarettes']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/category')
            ->where('category.slug', 'cigarettes')
            ->where('category.name', 'Cigarettes')
            ->has('products')
            ->has('branches')
            ->where('filters.search', '')
            ->where('filters.branchId', null));
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
