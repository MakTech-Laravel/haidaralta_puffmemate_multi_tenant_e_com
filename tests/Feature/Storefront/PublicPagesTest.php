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
