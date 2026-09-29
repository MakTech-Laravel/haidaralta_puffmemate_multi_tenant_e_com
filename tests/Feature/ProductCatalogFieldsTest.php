<?php

use App\Enums\DiscountType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Inventory\BranchPricingService;
use App\Support\BranchProductPriceDisplay;
use App\Support\Storefront\StorefrontCatalog;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    config(['catalog.show_branch_pricing' => true]);
});

test('platform can store brand tags and specifications on a product', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Tagged Product',
            'slug' => 'tagged-product',
            'sku' => 'TAG-001',
            'brand' => 'Marlboro',
            'tags' => ['new_arrival', 'best_selling'],
            'specifications' => json_encode([
                ['label' => 'Pack Format', 'value' => 'Soft Pack'],
                ['label' => 'Quantity', 'value' => '20'],
            ]),
            'status' => true,
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'tagged-product')->firstOrFail();

    expect($product->brand)->toBe('Marlboro')
        ->and($product->tags)->toBe(['new_arrival', 'best_selling'])
        ->and($product->specifications)->toBe([
            ['label' => 'Pack Format', 'value' => 'Soft Pack'],
            ['label' => 'Quantity', 'value' => '20'],
        ]);
});

test('platform can store optional pack label and pieces per pack', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Marlboro Gold Soft Pack',
            'slug' => 'marlboro-gold-pack-meta',
            'sku' => 'CIG-PACK-META',
            'brand' => 'Marlboro',
            'pack_label' => 'Gold Pack',
            'pieces_per_pack' => 20,
            'pack_unit' => 'Cigarettes',
            'status' => true,
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'marlboro-gold-pack-meta')->firstOrFail();

    expect($product->pack_label)->toBe('Gold Pack')
        ->and($product->pieces_per_pack)->toBe(20)
        ->and($product->pack_unit)->toBe('Cigarettes')
        ->and($product->packSummary())->toBe('Gold Pack • 20 Cigarettes');
});

test('platform can store a pack label without pieces for single-item products', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Clipper Classic Lighter',
            'slug' => 'clipper-classic-pack-meta',
            'sku' => 'ACC-CLIPPER-META',
            'pack_label' => 'Refillable Lighter',
            'pieces_per_pack' => null,
            'pack_unit' => null,
            'status' => true,
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'clipper-classic-pack-meta')->firstOrFail();

    expect($product->pack_label)->toBe('Refillable Lighter')
        ->and($product->pieces_per_pack)->toBeNull()
        ->and($product->packSummary())->toBe('Refillable Lighter');
});

test('platform rejects invalid product tags', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Bad Tags',
            'slug' => 'bad-tags',
            'tags' => ['not_a_real_tag'],
            'status' => true,
        ])
        ->assertSessionHasErrors('tags.0');
});

test('branch product price display uses start from when multiple active prices exist', function () {
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create(['name' => 'Marlboro Gold Soft Pack 20s']);
    $pack = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => 'Single pack',
        'sku' => 'CIG-MB-GOLD-20-PK-TEST',
    ]);
    $carton = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => 'Carton',
        'sku' => 'CIG-MB-GOLD-20-CTN-TEST',
    ]);

    $pricing = app(BranchPricingService::class);

    $pricing->upsert($branch, $product, [
        'selling_price' => '48.95',
        'discount_type' => 'none',
        'status' => true,
    ], $pack);

    $pricing->upsert($branch, $product, [
        'selling_price' => '475.00',
        'discount_type' => 'none',
        'status' => true,
    ], $carton);

    $display = BranchProductPriceDisplay::forBranchProduct($branch, $product);

    expect($display['is_from'])->toBeTrue()
        ->and($display['amount'])->toBe(48.95)
        ->and($display['label'])->toBe('Start from A$48.95')
        ->and($display['count'])->toBe(2);
});

test('branch product price display uses a single price label when only one active price exists', function () {
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create();

    app(BranchPricingService::class)->upsert($branch, $product, [
        'selling_price' => '18.50',
        'discount_type' => 'none',
        'status' => true,
    ]);

    $display = BranchProductPriceDisplay::forBranchProduct($branch, $product);

    expect($display['is_from'])->toBeFalse()
        ->and($display['amount'])->toBe(18.5)
        ->and($display['label'])->toBe('A$18.50')
        ->and($display['count'])->toBe(1);
});

test('expired product discounts are not applied to the effective price', function () {
    $this->travelTo('2026-10-15');

    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create();
    $price = BranchProductPrice::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'selling_price' => 20,
        'regular_price' => 25,
        'discount_type' => DiscountType::Percent,
        'discount_price' => 50,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'status' => true,
    ]);

    $display = BranchProductPriceDisplay::listDisplay($price);

    expect($display['has_discount'])->toBeFalse()
        ->and($display['has_compare_at'])->toBeTrue()
        ->and($display['effective_price'])->toBe('20.00')
        ->and($display['regular_price'])->toBe('25.00');
});

test('storefront compare-at uses selling when a live discount is on', function () {
    $this->travelTo('2026-09-15');

    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create();

    app(BranchPricingService::class)->upsert($branch, $product, [
        'selling_price' => '20.00',
        'regular_price' => '30.00',
        'discount_type' => 'percent',
        'discount_price' => 25,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
    ]);

    $detail = StorefrontCatalog::productDetail($product->slug, $branch);

    expect($detail['price'])->toBe('A$15.00')
        ->and($detail['compareAtPrice'])->toBe('A$20.00');
});

test('fixed discount subtracts the value from selling price', function () {
    $this->travelTo('2026-09-15');

    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create();
    $price = BranchProductPrice::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'selling_price' => 19.99,
        'regular_price' => 22.99,
        'discount_type' => DiscountType::Fixed,
        'discount_price' => 2,
        'start_date' => null,
        'end_date' => null,
        'status' => true,
    ]);

    $display = BranchProductPriceDisplay::listDisplay($price);

    expect($display['has_discount'])->toBeTrue()
        ->and($display['effective_price'])->toBe('17.99')
        ->and($display['selling_price'])->toBe('19.99');
});

test('storefront compare-at uses selling when a live fixed discount is on', function () {
    $this->travelTo('2026-09-15');

    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create();

    app(BranchPricingService::class)->upsert($branch, $product, [
        'selling_price' => '19.99',
        'regular_price' => '22.99',
        'discount_type' => 'fixed',
        'discount_price' => 2,
    ]);

    $detail = StorefrontCatalog::productDetail($product->slug, $branch);

    expect($detail['price'])->toBe('A$17.99')
        ->and($detail['compareAtPrice'])->toBe('A$19.99');
});
