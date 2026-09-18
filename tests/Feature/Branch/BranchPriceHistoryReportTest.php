<?php

use App\Enums\PriceChangeSource;
use App\Models\Branch;
use App\Models\BranchProductPriceHistory;
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
    $this->product = Product::factory()->create(['slug' => 'branch-price-history-pack']);
});

test('branch price history only lists this branch ledger', function () {
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $melbourneStaff = User::query()->where('email', UserSeeder::BRANCH_2_SUPER_ADMIN)->firstOrFail();

    BranchProductPriceHistory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'created_by' => $this->staff->id,
        'source' => PriceChangeSource::PriceUpsert,
    ]);

    BranchProductPriceHistory::factory()->create([
        'branch_id' => $melbourne->id,
        'product_id' => $this->product->id,
        'created_by' => $melbourneStaff->id,
        'source' => PriceChangeSource::StockIn,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.price-history', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/reports/price-history')
            ->where('table.meta.total', 1)
            ->where('table.data.0.product_url', route('branch.products.show', [$this->branch, $this->product]))
            ->where('table.data.0.created_by_url', route('branch.staff.show', [$this->branch, $this->staff]))
            ->missing('table.filterOptions.products')
            ->where('table.filterOptions.selected_branch', null)
            ->where('table.data.0.branch_url', null));

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.price-history', [
            $this->branch,
            'filter' => ['search' => $this->branch->name],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.meta.total', 0));
});

test('branch price history filters by the selected product', function () {
    $other = Product::factory()->create();

    BranchProductPriceHistory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'created_by' => $this->staff->id,
    ]);

    BranchProductPriceHistory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $other->id,
        'created_by' => $this->staff->id,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.price-history', [
            $this->branch,
            'product' => $this->product->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.meta.total', 1)
            ->where('table.data.0.product_name', $this->product->name)
            ->where('table.filterOptions.selected_product.id', $this->product->id));
});

test('guests cannot open branch price history', function () {
    $this->get(route('branch.reports.price-history', $this->branch))->assertRedirect(route('login'));
});
