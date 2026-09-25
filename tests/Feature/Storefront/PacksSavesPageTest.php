<?php

use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

test('packs and saves page renders offer products', function () {
    $offer = Product::factory()->create([
        'name' => 'Offer Multipack',
        'slug' => 'offer-multipack',
        'status' => true,
        'selling_price' => 20,
        'regular_price' => 28,
    ]);

    Product::factory()->create([
        'name' => 'Plain Product',
        'slug' => 'plain-product',
        'status' => true,
        'selling_price' => 12,
        'regular_price' => 12,
    ]);

    $this->get(route('packs-saves'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/packs-saves')
            ->has('products')
            ->missing('branches')
            ->where('products', fn ($products) => collect($products)->every(
                fn ($product) => filled(data_get($product, 'compareAtPrice')),
            ))
            ->where('products', fn ($products) => collect($products)->contains(
                fn ($product): bool => $product['id'] === $offer->slug,
            ))
            ->where('products', fn ($products) => collect($products)->doesntContain(
                fn ($product): bool => $product['id'] === 'plain-product',
            )));
});
