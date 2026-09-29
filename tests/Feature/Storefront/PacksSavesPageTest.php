<?php

use App\Models\Branch;
use App\Models\Pack;
use App\Models\PackItem;
use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

test('packs and saves page lists only active in-window packs as cards', function () {
    $branch = Branch::factory()->create(['slug' => 'sydney']);
    $pack = Pack::factory()->for($branch)->create([
        'name' => 'Starter Duo',
        'slug' => 'starter-duo',
        'description' => 'Two devices, one price.',
        'subtotal' => '142.50',
        'pack_price' => '124.00',
        'quantity' => 14,
    ]);
    PackItem::factory()->for($pack)->create(['quantity' => 3]);
    PackItem::factory()->for($pack)->create(['quantity' => 2]);

    Pack::factory()->for($branch)->create(['status' => false]);
    Pack::factory()->for($branch)->create(['quantity' => 0]);
    Pack::factory()->for($branch)->create(['starts_at' => now()->addDay()]);
    Pack::factory()->for($branch)->create(['ends_at' => now()->subDay()]);
    Pack::factory()->for(Branch::factory()->create(['status' => false]))->create();

    $this->get(route('packs-saves'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/packs-saves')
            ->missing('products')
            ->has('packs', 1)
            ->where('packs.0', [
                'id' => 'sydney:starter-duo',
                'name' => 'Starter Duo',
                'eyebrow' => '5-Pack Bundle',
                'description' => 'Two devices, one price.',
                'image' => '',
                'price' => '124.00',
                'currency' => 'AUD',
                'savings' => '18.50',
                'stockLeft' => 14,
            ]));
});

test('featured pack is the active pack with the biggest saving', function () {
    $branch = Branch::factory()->create(['name' => 'Richmond Hub']);
    Pack::factory()->for($branch)->create(['subtotal' => '100.00', 'pack_price' => '90.00']);
    $best = Pack::factory()->for($branch)->create([
        'name' => 'Carton Saver',
        'subtotal' => '200.00',
        'pack_price' => '150.00',
    ]);
    PackItem::factory()->for($best)->for(Product::factory()->create(['name' => 'Gold 20s']))->create(['quantity' => 5]);

    $this->get(route('packs-saves'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('featuredPack.name', 'Carton Saver')
            ->where('featuredPack.savings', '50.00')
            ->where('featuredPack.regularPrice', '200.00')
            ->where('featuredPack.discountPercent', 25)
            ->where('featuredPack.branchName', 'Richmond Hub')
            ->where('featuredPack.items', [['name' => 'Gold 20s', 'quantity' => 5]])
            ->where('packs.0.name', 'Carton Saver'));
});

test('featured pack is the highlighted pack even when another saves more', function () {
    $branch = Branch::factory()->create();
    Pack::factory()->for($branch)->create(['name' => 'Big Saver', 'subtotal' => '200.00', 'pack_price' => '100.00']);
    Pack::factory()->for($branch)->create([
        'name' => 'Highlighted Pick',
        'subtotal' => '100.00',
        'pack_price' => '95.00',
        'highlighted_at' => now(),
    ]);

    $this->get(route('packs-saves'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('featuredPack.name', 'Highlighted Pick'));
});

test('an inactive highlighted pack falls back to the biggest saving', function () {
    $branch = Branch::factory()->create();
    Pack::factory()->for($branch)->create(['name' => 'Big Saver', 'subtotal' => '200.00', 'pack_price' => '100.00']);
    Pack::factory()->for($branch)->create([
        'name' => 'Highlighted Off',
        'status' => false,
        'highlighted_at' => now(),
    ]);

    $this->get(route('packs-saves'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('featuredPack.name', 'Big Saver'));
});

test('featured pack is null when no pack is active', function () {
    Pack::factory()->create(['status' => false]);

    $this->get(route('packs-saves'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('featuredPack', null)
            ->has('packs', 0));
});

test('packs can be searched by name and sorted by price', function () {
    $branch = Branch::factory()->create();
    Pack::factory()->for($branch)->create(['name' => 'Marlboro Carton', 'pack_price' => '124.00']);
    Pack::factory()->for($branch)->create(['name' => 'Marlboro Duo', 'pack_price' => '48.00']);
    Pack::factory()->for($branch)->create(['name' => 'Vape Starter', 'pack_price' => '30.00']);

    $this->get(route('packs-saves', ['search' => 'marlboro', 'sort' => 'price_asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', ['search' => 'marlboro', 'sort' => 'price_asc'])
            ->has('packs', 2)
            ->where('packs.0.name', 'Marlboro Duo')
            ->where('packs.1.name', 'Marlboro Carton'));
});

test('a selected branch only shows that branch packs', function () {
    $sydney = Branch::factory()->create(['slug' => 'sydney']);
    Pack::factory()->for($sydney)->create(['name' => 'Sydney Pack']);
    Pack::factory()->for(Branch::factory()->create())->create(['name' => 'Melbourne Pack']);

    $this->get(route('packs-saves', ['branch' => 'sydney']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('packs', 1)
            ->where('packs.0.name', 'Sydney Pack'));
});
