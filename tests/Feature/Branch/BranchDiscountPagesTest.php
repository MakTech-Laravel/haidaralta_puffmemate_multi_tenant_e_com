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

    $this->staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->product = Product::factory()->create(['slug' => 'branch-discount-pack']);
});

test('branch discount page locks the branch even when unlocked in the query', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.discounts', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/discounts/index')
            ->where('context.locked', 'branch')
            ->where('context.branch.id', $this->branch->id)
            ->where('context.product', null));
});

test('legacy branch stock discount url redirects to discounts', function () {
    $this->actingAs($this->staff, 'branch')
        ->get('/branch/'.$this->branch->slug.'/stock/discount')
        ->assertRedirect('/branch/'.$this->branch->slug.'/discounts');
});

test('branch applies the same discount to every selected product on this branch', function () {
    $other = Product::factory()->create();

    foreach ([$this->product, $other] as $product) {
        BranchProductPrice::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'selling_price' => 30,
            'discount_type' => DiscountType::None,
            'discount_price' => null,
        ]);
    }

    $this->actingAs($this->staff, 'branch')
        ->put(route('branch.discounts.update', $this->branch), [
            'product_ids' => [$this->product->id, $other->id],
            'discount_type' => 'percent',
            'discount_price' => 15,
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-30',
        ])
        ->assertRedirect();

    expect(BranchProductPrice::query()
        ->where('branch_id', $this->branch->id)
        ->where('discount_type', DiscountType::Percent)
        ->where('discount_price', 15)
        ->count())->toBe(2);
});

test('branch discount replaces an existing percent offer including dates', function () {
    BranchProductPrice::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'selling_price' => 40,
        'discount_type' => DiscountType::Percent,
        'discount_price' => 25,
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ]);

    $this->actingAs($this->staff, 'branch')
        ->put(route('branch.discounts.update', $this->branch), [
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

test('branch discount rejects an empty product list', function () {
    $this->actingAs($this->staff, 'branch')
        ->put(route('branch.discounts.update', $this->branch), [
            'product_ids' => [],
            'discount_type' => 'percent',
            'discount_price' => 10,
        ])
        ->assertSessionHasErrors('product_ids');
});

test('branch discount rejects a pair with no selling price', function () {
    $this->actingAs($this->staff, 'branch')
        ->put(route('branch.discounts.update', $this->branch), [
            'product_ids' => [$this->product->id],
            'discount_type' => 'percent',
            'discount_price' => 10,
        ])
        ->assertSessionHasErrors([
            'product_ids' => '1 selected pair has no selling price. Set a price first.',
        ]);
});

test('branch discount rejects a fixed value above the selling price', function () {
    BranchProductPrice::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'selling_price' => 20,
        'discount_type' => DiscountType::None,
        'discount_price' => null,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->put(route('branch.discounts.update', $this->branch), [
            'product_ids' => [$this->product->id],
            'discount_type' => 'fixed',
            'discount_price' => 25,
        ])
        ->assertSessionHasErrors([
            'discount_price' => 'The fixed discount must be less than or equal to each selected selling price.',
        ]);
});

test('branch discount is rejected for another branch', function () {
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    BranchProductPrice::factory()->create([
        'branch_id' => $melbourne->id,
        'product_id' => $this->product->id,
        'selling_price' => 20,
        'discount_type' => DiscountType::None,
        'discount_price' => null,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->put(route('branch.discounts.update', $this->branch), [
            'branch_ids' => [$melbourne->id],
            'product_ids' => [$this->product->id],
            'discount_type' => 'percent',
            'discount_price' => 10,
        ])
        ->assertNotFound();
});
