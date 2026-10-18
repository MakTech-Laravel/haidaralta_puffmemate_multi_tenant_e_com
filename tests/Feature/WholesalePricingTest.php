<?php

use App\Enums\DiscountType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Product;
use App\Models\User;
use App\Support\BranchProductPriceDisplay;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

test('catalog wholesale price and minimum apply when branch pricing is off', function () {
    config(['catalog.show_branch_pricing' => false]);

    $branch = Branch::factory()->create();
    $product = Product::factory()->create([
        'selling_price' => 30,
        'wholesale_price' => 21.5,
        'min_wholesale_qty' => 24,
    ]);
    BranchProductPrice::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'wholesale_price' => 9,
        'min_wholesale_qty' => 2,
    ]);

    expect(BranchProductPriceDisplay::wholesaleAmount($branch, $product))->toBe(21.5)
        ->and(BranchProductPriceDisplay::minWholesaleQty($branch, $product))->toBe(24);
});

test('branch wholesale price and minimum override the catalog when branch pricing is on', function () {
    config(['catalog.show_branch_pricing' => true]);

    $branch = Branch::factory()->create();
    $product = Product::factory()->create([
        'selling_price' => 30,
        'wholesale_price' => 21.5,
        'min_wholesale_qty' => 24,
    ]);
    BranchProductPrice::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'wholesale_price' => 19,
        'min_wholesale_qty' => 12,
    ]);

    $price = BranchProductPriceDisplay::wholesaleForBranchProduct($branch, $product);

    expect($price['amount'])->toBe(19.0)
        ->and($price['min_qty'])->toBe(12)
        ->and($price['is_wholesale'])->toBeTrue();
});

test('branch row without wholesale fields falls back to the catalog wholesale values', function () {
    config(['catalog.show_branch_pricing' => true]);

    $branch = Branch::factory()->create();
    $product = Product::factory()->create([
        'selling_price' => 30,
        'wholesale_price' => 21.5,
        'min_wholesale_qty' => 24,
    ]);
    BranchProductPrice::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'wholesale_price' => null,
        'min_wholesale_qty' => null,
    ]);

    $price = BranchProductPriceDisplay::wholesaleForBranchProduct($branch, $product);

    expect($price['amount'])->toBe(21.5)
        ->and($price['min_qty'])->toBe(24)
        ->and($price['is_wholesale'])->toBeTrue();
});

test('products without any wholesale price fall back to the retail price', function () {
    config(['catalog.show_branch_pricing' => true]);

    $branch = Branch::factory()->create();
    $product = Product::factory()->create(['selling_price' => 30]);
    BranchProductPrice::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'selling_price' => 27,
        'regular_price' => null,
        'discount_type' => DiscountType::None,
        'discount_price' => null,
    ]);

    $price = BranchProductPriceDisplay::wholesaleForBranchProduct($branch, $product);

    expect($price['amount'])->toBe(27.0)
        ->and($price['min_qty'])->toBeNull()
        ->and($price['is_wholesale'])->toBeFalse();
});

test('catalog pricing page saves and clears the wholesale price and minimum', function () {
    config(['catalog.show_branch_pricing' => false]);
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create(['selling_price' => 20, 'discount_type' => DiscountType::None]);
    $base = ['selling_price' => 20, 'discount_type' => DiscountType::None->value];

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.prices', $product), [...$base, 'wholesale_price' => 14.5, 'min_wholesale_qty' => 12])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.catalog.pricing.manage', ['product' => $product->id]))
        ->assertInertia(fn ($page) => $page
            ->where('product.price.wholesale_price', '14.50')
            ->where('product.price.min_wholesale_qty', 12));

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.prices', $product), [...$base, 'wholesale_price' => 0, 'min_wholesale_qty' => 0])
        ->assertSessionHasErrors(['wholesale_price', 'min_wholesale_qty']);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.prices', $product), [...$base, 'wholesale_price' => '', 'min_wholesale_qty' => ''])
        ->assertSessionHasNoErrors();

    $product->refresh();

    expect($product->wholesale_price)->toBeNull()
        ->and($product->min_wholesale_qty)->toBeNull();
});
