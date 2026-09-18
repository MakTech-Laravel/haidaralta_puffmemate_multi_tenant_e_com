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
        ->and($display['label'])->toBe('Start from $48.95')
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
        ->and($display['label'])->toBe('$18.50')
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

    expect($detail['price'])->toBe('$15.00')
        ->and($detail['compareAtPrice'])->toBe('$20.00');
});
