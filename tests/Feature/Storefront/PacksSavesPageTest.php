<?php

use Inertia\Testing\AssertableInertia as Assert;

test('packs and saves page renders offer products', function () {
    $this->get(route('packs-saves'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/packs-saves')
            ->has('products')
            ->has('branches')
            ->where('products', fn ($products) => collect($products)->every(
                fn ($product) => filled(data_get($product, 'compareAtPrice')),
            )));
});
