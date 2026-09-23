<?php

use App\Enums\DiscountType;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Support\BranchProductPriceDisplay;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform manage pricing page and upsert succeed when branch pricing is hidden', function () {
    config(['catalog.show_branch_pricing' => false]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create([
        'selling_price' => 20,
        'regular_price' => 25,
        'discount_type' => DiscountType::None,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.catalog.pricing.manage', ['product' => $product->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/catalog/pricing/manage')
            ->where('product.id', $product->id)
            ->where('product.price.selling_price', '20.00'));

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.prices', $product), [
            'selling_price' => 18.5,
            'regular_price' => 22,
            'discount_type' => DiscountType::Fixed->value,
            'discount_price' => 3,
            'start_date' => null,
            'end_date' => null,
        ])
        ->assertRedirect(route('platform.catalog.pricing.manage', [
            'product' => $product->id,
        ]));

    $product->refresh();

    expect((float) $product->selling_price)->toBe(18.5)
        ->and((float) $product->regular_price)->toBe(22.0)
        ->and($product->discount_type)->toBe(DiscountType::Fixed)
        ->and((float) $product->discount_price)->toBe(3.0);
});

test('branch manage pricing page and upsert succeed when branch pricing is hidden', function () {
    config([
        'catalog.show_branch_pricing' => false,
        'catalog.branch_can_manage_catalog_pricing' => true,
    ]);

    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create([
        'selling_price' => 30,
        'discount_type' => DiscountType::None,
    ]);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.catalog.pricing.manage', [
            'branch' => $branch,
            'product' => $product->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/catalog/pricing/manage')
            ->where('product.id', $product->id));

    $this->actingAs($staff, 'branch')
        ->put(route('branch.products.catalog-prices', [$branch, $product]), [
            'selling_price' => 28,
            'regular_price' => null,
            'discount_type' => DiscountType::Percent->value,
            'discount_price' => 10,
        ])
        ->assertRedirect(route('branch.catalog.pricing.manage', [
            'branch' => $branch,
            'product' => $product->id,
        ]));

    $product->refresh();

    expect((float) $product->selling_price)->toBe(28.0)
        ->and($product->discount_type)->toBe(DiscountType::Percent)
        ->and((float) $product->discount_price)->toBe(10.0);
});

test('branch manage pricing routes abort when branch cannot manage catalog pricing', function () {
    config([
        'catalog.show_branch_pricing' => false,
        'catalog.branch_can_manage_catalog_pricing' => false,
    ]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create(['selling_price' => 10]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.catalog.pricing.manage', ['product' => $product->id]))
        ->assertOk();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.prices', $product), [
            'selling_price' => 11,
            'discount_type' => DiscountType::None->value,
        ])
        ->assertRedirect();

    expect((float) $product->fresh()->selling_price)->toBe(11.0);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.catalog.pricing.manage', $branch))
        ->assertNotFound();

    $this->actingAs($staff, 'branch')
        ->put(route('branch.products.catalog-prices', [$branch, $product]), [
            'selling_price' => 9,
            'discount_type' => DiscountType::None->value,
        ])
        ->assertNotFound();
});

test('manage pricing routes abort when branch pricing is enabled', function () {
    config(['catalog.show_branch_pricing' => true]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create(['selling_price' => 10]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.catalog.pricing.manage'))
        ->assertNotFound();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.prices', $product), [
            'selling_price' => 9,
            'discount_type' => DiscountType::None->value,
        ])
        ->assertNotFound();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.catalog.pricing.manage', $branch))
        ->assertNotFound();

    $this->actingAs($staff, 'branch')
        ->put(route('branch.products.catalog-prices', [$branch, $product]), [
            'selling_price' => 9,
            'discount_type' => DiscountType::None->value,
        ])
        ->assertNotFound();
});

test('catalog price display applies live fixed and percent discounts', function () {
    config(['catalog.show_branch_pricing' => false]);

    $product = Product::factory()->create([
        'selling_price' => 100,
        'regular_price' => 120,
        'discount_type' => DiscountType::Fixed,
        'discount_price' => 15,
        'discount_start_date' => null,
        'discount_end_date' => null,
    ]);

    $list = BranchProductPriceDisplay::listDisplayFromProduct($product);
    $display = BranchProductPriceDisplay::forCatalogProduct($product);

    expect($list['has_discount'])->toBeTrue()
        ->and($list['effective_price'])->toBe('85.00')
        ->and($list['has_compare_at'])->toBeFalse()
        ->and($display['amount'])->toBe(85.0)
        ->and($display['compare_at'])->toBe(100.0)
        ->and($display['has_discount'])->toBeTrue();

    $product->forceFill([
        'discount_type' => DiscountType::Percent,
        'discount_price' => 25,
    ])->save();

    $list = BranchProductPriceDisplay::listDisplayFromProduct($product->fresh());

    expect($list['effective_price'])->toBe('75.00')
        ->and($list['has_discount'])->toBeTrue();
});

test('catalog price display ignores discounts outside the date window', function () {
    config(['catalog.show_branch_pricing' => false]);

    $product = Product::factory()->create([
        'selling_price' => 50,
        'regular_price' => 60,
        'discount_type' => DiscountType::Fixed,
        'discount_price' => 10,
        'discount_start_date' => now()->addDay()->toDateString(),
        'discount_end_date' => now()->addDays(7)->toDateString(),
    ]);

    $list = BranchProductPriceDisplay::listDisplayFromProduct($product);

    expect($list['has_discount'])->toBeFalse()
        ->and($list['effective_price'])->toBe('50.00')
        ->and($list['has_compare_at'])->toBeTrue();
});
