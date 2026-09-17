<?php

use App\Models\Branch;
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

test('discounts url with no pair redirects to the products index', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.discounts'))
        ->assertRedirect(route('platform.products.index'));
});

test('discounts url with only a product redirects to manage branches', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.discounts', [
            'product' => $this->product->id,
        ]))
        ->assertRedirect(route('platform.products.branches', $this->product));
});

test('discounts url with a pair redirects to the product manage page', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.discounts', [
            'branch' => $this->branch->id,
            'product' => $this->product->id,
        ]))
        ->assertRedirect(route('platform.stock.manage', [
            'branch' => $this->branch->id,
            'product' => $this->product->id,
        ]));
});

test('discount writes redirect to the same destination as the discounts page', function () {
    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.discounts.update', [
            'branch' => $this->branch->id,
            'product' => $this->product->id,
        ]))
        ->assertRedirect(route('platform.stock.manage', [
            'branch' => $this->branch->id,
            'product' => $this->product->id,
        ]));

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.discounts.preview'))
        ->assertRedirect(route('platform.products.index'));
});

test('legacy stock discount url permanently redirects without chaining through discounts', function () {
    $this->actingAs($this->admin, 'platform')
        ->get('/platform/stock/discount')
        ->assertRedirect(route('platform.products.index'))
        ->assertStatus(301);

    $this->actingAs($this->admin, 'platform')
        ->get('/platform/stock/discount?branch='.$this->branch->id.'&product='.$this->product->id)
        ->assertRedirect(route('platform.stock.manage', [
            'branch' => $this->branch->id,
            'product' => $this->product->id,
        ]))
        ->assertStatus(301);
});
