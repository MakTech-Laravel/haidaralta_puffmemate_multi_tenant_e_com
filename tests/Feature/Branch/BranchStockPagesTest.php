<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
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
    $this->product = Product::factory()->create(['slug' => 'branch-stock-pack']);
});

test('branch manage stock page locks the branch even when unlocked in the query', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.manage', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/stock/manage')
            ->where('context.locked', 'branch')
            ->where('context.branch.id', $this->branch->id));
});

test('branch manage stock page 404s when the locked product is missing', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.manage', [
            'branch' => $this->branch,
            'product' => 'no-such-product',
            'locked' => 'product',
        ]))
        ->assertNotFound();
});

test('branch stock adjust is rejected for another branch', function () {
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.stock.adjust', $this->branch), [
            'branch_id' => $melbourne->id,
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 5,
        ])
        ->assertNotFound();
});

test('branch import stock only fills this branch', function () {
    $other = Product::factory()->create();

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.stock.import.store', $this->branch), [
            'product_ids' => [$this->product->id, $other->id],
            'quantity' => 6,
        ])
        ->assertRedirect();

    expect(Inventory::query()->where('quantity', 6)->pluck('branch_id')->unique()->all())
        ->toBe([$this->branch->id]);
});

test('branch stock transactions only list this branch ledger', function () {
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $melbourneStaff = User::query()->where('email', UserSeeder::BRANCH_2_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.stock.adjust', $this->branch), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 4,
        ])
        ->assertRedirect();

    $this->actingAs($melbourneStaff, 'branch')
        ->post(route('branch.stock.adjust', $melbourne), [
            'branch_id' => $melbourne->id,
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 9,
        ])
        ->assertRedirect();

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.transactions', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/stock/transactions')
            ->where('table.meta.total', 1)
            ->where('table.data.0.quantity', 4));
});

test('creating a product from a branch stock page returns to it with the product selected', function () {
    $returnTo = '/branch/'.$this->branch->slug.'/stock/manage';

    $response = $this->actingAs($this->staff, 'branch')
        ->post(route('branch.products.store', $this->branch), [
            'category_ids' => [Category::factory()->create()->id],
            'name' => 'Branch Returned Pack',
            'slug' => 'branch-returned-pack',
            'status' => true,
            'return_to' => $returnTo,
        ]);

    $created = Product::query()->where('slug', 'branch-returned-pack')->firstOrFail();

    $response->assertRedirect($returnTo.'?product='.$created->id);
});

test('guests cannot open branch stock pages', function () {
    $this->get(route('branch.stock.manage', $this->branch))->assertRedirect(route('login'));
});
