<?php

use App\Models\Branch;
use App\Models\Pack;
use App\Models\PackItem;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('an active pack renders its detail page', function () {
    Storage::fake('public');

    $branch = Branch::factory()->create(['name' => 'Richmond Hub', 'slug' => 'richmond']);
    $pack = Pack::factory()->for($branch)->create([
        'name' => 'Carton Saver',
        'slug' => 'carton-saver',
        'subtotal' => '200.00',
        'pack_price' => '150.00',
        'quantity' => 6,
        'starts_at' => null,
        'ends_at' => now()->addWeek(),
    ]);
    $logo = public_path('images/storefront/logo.png');
    $cover = $pack->addMedia($logo)->preservingOriginal()->usingFileName('cover.png')->toMediaCollection('image');
    $gallery = $pack->addMedia($logo)->preservingOriginal()->usingFileName('gallery.png')->toMediaCollection('gallery');
    PackItem::factory()->for($pack)->for(Product::factory()->create(['name' => 'Gold 20s']))->create(['quantity' => 5]);
    PackItem::factory()->for($pack)->for(Product::factory()->create(['name' => 'Clipper Lighter']))->create(['quantity' => 1]);
    Pack::factory()->for($branch)->create();

    $this->get(route('packs-saves.show', ['branch' => 'richmond', 'pack' => 'carton-saver']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/pack')
            ->where('pack.id', 'richmond:carton-saver')
            ->where('pack.name', 'Carton Saver')
            ->where('pack.price', '150.00')
            ->where('pack.regularPrice', '200.00')
            ->where('pack.savings', '50.00')
            ->where('pack.discountPercent', 25)
            ->where('pack.branchName', 'Richmond Hub')
            ->where('pack.images', [$cover->getUrl(), $gallery->getUrl()])
            ->where('pack.items.0.name', 'Gold 20s')
            ->where('pack.items.0.quantity', 5)
            ->where('pack.productCount', 2)
            ->where('pack.totalUnits', 6)
            ->where('pack.endsAt', now()->addWeek()->format('j M Y'))
            ->where('backHref', '/packs-saves?branch=richmond')
            ->where('backLabel', 'Back to Richmond Hub Packs & Saves (2 packs)'));
});

test('related packs exclude the current pack and list same branch packs first', function () {
    $branch = Branch::factory()->create(['slug' => 'richmond']);
    $pack = Pack::factory()->for($branch)->create(['slug' => 'current']);
    Pack::factory()->for(Branch::factory()->create())->create([
        'name' => 'Other Branch Big Saver',
        'subtotal' => '500.00',
        'pack_price' => '100.00',
    ]);
    Pack::factory()->for($branch)->create([
        'name' => 'Same Branch Pack',
        'subtotal' => '100.00',
        'pack_price' => '90.00',
    ]);

    $this->get(route('packs-saves.show', ['branch' => 'richmond', 'pack' => 'current']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('relatedPacks', 2)
            ->where('relatedPacks.0.name', 'Same Branch Pack')
            ->where('relatedPacks.1.name', 'Other Branch Big Saver'));
});

test('a pack that is not publicly available is not found', function (array $packAttributes, bool $branchActive) {
    $branch = Branch::factory()->create(['slug' => 'richmond', 'status' => $branchActive]);
    Pack::factory()->for($branch)->create(['slug' => 'hidden', ...$packAttributes]);

    $this->get(route('packs-saves.show', ['branch' => 'richmond', 'pack' => 'hidden']))
        ->assertNotFound();
})->with([
    'inactive pack' => [['status' => false], true],
    'not started yet' => [['starts_at' => now()->addDay()], true],
    'already ended' => [['ends_at' => now()->subDay()], true],
    'sold out' => [['quantity' => 0], true],
    'inactive branch' => [[], false],
]);

test('a pack slug under another branch slug is not found', function () {
    Pack::factory()->for(Branch::factory()->create(['slug' => 'sydney']))->create(['slug' => 'duo']);
    Branch::factory()->create(['slug' => 'richmond']);

    $this->get(route('packs-saves.show', ['branch' => 'richmond', 'pack' => 'duo']))
        ->assertNotFound();
});

test('an unknown pack is not found', function () {
    Branch::factory()->create(['slug' => 'richmond']);

    $this->get(route('packs-saves.show', ['branch' => 'richmond', 'pack' => 'missing']))
        ->assertNotFound();
});
