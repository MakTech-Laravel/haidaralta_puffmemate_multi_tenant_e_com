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

    $this->staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->product = Product::factory()->create(['slug' => 'branch-discount-pack']);
});

test('discounts url with no product redirects to this branch products index', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.discounts', $this->branch))
        ->assertRedirect(route('branch.products.index', $this->branch));
});

test('discounts url with a product redirects to this branch product manage page', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.discounts', [
            'branch' => $this->branch,
            'product' => $this->product->id,
        ]))
        ->assertRedirect(route('branch.stock.manage', [
            'branch' => $this->branch,
            'product' => $this->product->id,
        ]));
});

test('discount writes redirect to the same destination as the discounts page', function () {
    $this->actingAs($this->staff, 'branch')
        ->put(route('branch.discounts.update', [
            'branch' => $this->branch,
            'product' => $this->product->id,
        ]))
        ->assertRedirect(route('branch.stock.manage', [
            'branch' => $this->branch,
            'product' => $this->product->id,
        ]));

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.discounts.preview', $this->branch))
        ->assertRedirect(route('branch.products.index', $this->branch));
});

test('legacy branch stock discount url permanently redirects without chaining through discounts', function () {
    $this->actingAs($this->staff, 'branch')
        ->get('/branch/'.$this->branch->slug.'/stock/discount')
        ->assertRedirect(route('branch.products.index', $this->branch))
        ->assertStatus(301);

    $this->actingAs($this->staff, 'branch')
        ->get('/branch/'.$this->branch->slug.'/stock/discount?product='.$this->product->id)
        ->assertRedirect(route('branch.stock.manage', [
            'branch' => $this->branch,
            'product' => $this->product->id,
        ]))
        ->assertStatus(301);
});
