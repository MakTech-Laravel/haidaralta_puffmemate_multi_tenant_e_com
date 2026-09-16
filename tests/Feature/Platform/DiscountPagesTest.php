<?php

use App\Enums\DiscountType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->product = Product::factory()->create(['slug' => 'discount-pack']);
});

test('discount page starts unlocked with no selection', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.discounts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/discounts/index')
            ->where('context.locked', null)
            ->where('context.branch', null)
            ->where('context.product', null));
});

test('legacy stock discount url redirects to discounts', function () {
    $this->actingAs($this->admin, 'platform')
        ->get('/platform/stock/discount')
        ->assertRedirect('/platform/discounts');
});

test('platform can set a selling price and then a discount', function () {
    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.stock.price.update'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'selling_price' => 20,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.discounts.update'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'selling_price' => 20,
            'discount_type' => 'percent',
            'discount_price' => 25,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.discounts', [
            'branch' => $this->branch->id,
            'product' => $this->product->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/discounts/index')
            ->where('context.price.selling_price', '20.00')
            ->where('context.price.effective_price', '15.00')
            ->where('context.price.has_discount', true));
});

test('platform applies the same discount to every selected pair', function () {
    $other = Product::factory()->create();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    foreach ([$this->branch, $melbourne] as $branch) {
        foreach ([$this->product, $other] as $product) {
            BranchProductPrice::factory()->create([
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'selling_price' => 40,
                'discount_type' => DiscountType::None,
                'discount_price' => null,
            ]);
        }
    }

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.discounts.update'), [
            'branch_ids' => [$this->branch->id, $melbourne->id],
            'product_ids' => [$this->product->id, $other->id],
            'discount_type' => 'percent',
            'discount_price' => 10,
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-30',
        ])
        ->assertRedirect();

    expect(BranchProductPrice::query()
        ->where('discount_type', DiscountType::Percent)
        ->where('discount_price', 10)
        ->count())->toBe(4);

    $this->assertDatabaseHas('branch_product_prices', [
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'selling_price' => 40,
        'discount_type' => DiscountType::Percent->value,
        'discount_price' => 10,
    ]);
});

test('platform discount replaces an existing percent offer including dates', function () {
    BranchProductPrice::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'selling_price' => 40,
        'discount_type' => DiscountType::Percent,
        'discount_price' => 25,
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.discounts.update'), [
            'branch_ids' => [$this->branch->id],
            'product_ids' => [$this->product->id],
            'discount_type' => 'fixed',
            'discount_price' => 5,
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-30',
        ])
        ->assertRedirect();

    $price = BranchProductPrice::query()
        ->where('branch_id', $this->branch->id)
        ->where('product_id', $this->product->id)
        ->whereNull('variant_id')
        ->first();

    expect($price)->not->toBeNull();
    expect($price->selling_price)->toBe('40.00');
    expect($price->discount_type)->toBe(DiscountType::Fixed);
    expect($price->discount_price)->toBe('5.00');
    expect($price->start_date->toDateString())->toBe('2026-09-20');
    expect($price->end_date->toDateString())->toBe('2026-09-30');
});

test('platform discount rejects an empty product list', function () {
    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.discounts.update'), [
            'branch_ids' => [$this->branch->id],
            'product_ids' => [],
            'discount_type' => 'percent',
            'discount_price' => 10,
        ])
        ->assertSessionHasErrors('product_ids');
});

test('platform discount rejects a pair with no selling price', function () {
    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.discounts.update'), [
            'branch_ids' => [$this->branch->id],
            'product_ids' => [$this->product->id],
            'discount_type' => 'percent',
            'discount_price' => 10,
        ])
        ->assertSessionHasErrors([
            'product_ids' => '1 selected pair has no selling price. Set a price first.',
        ]);
});

test('platform discount rejects a fixed value above the selling price', function () {
    BranchProductPrice::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'selling_price' => 20,
        'discount_type' => DiscountType::None,
        'discount_price' => null,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.discounts.update'), [
            'branch_ids' => [$this->branch->id],
            'product_ids' => [$this->product->id],
            'discount_type' => 'fixed',
            'discount_price' => 25,
        ])
        ->assertSessionHasErrors([
            'discount_price' => 'The fixed discount must be less than or equal to each selected selling price.',
        ]);
});

test('platform discount preview counts existing offers on selected pairs', function () {
    BranchProductPrice::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'selling_price' => 40,
        'discount_type' => DiscountType::Percent,
        'discount_price' => 10,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($this->admin, 'platform')
        ->getJson(route('platform.discounts.preview', [
            'branch_ids' => [$this->branch->id],
            'product_ids' => [$this->product->id],
        ]))
        ->assertOk()
        ->assertJsonPath('pairs', 1)
        ->assertJsonPath('with_offer', 1)
        ->assertJsonPath('active', 1)
        ->assertJsonPath('missing_price', 0);
});
