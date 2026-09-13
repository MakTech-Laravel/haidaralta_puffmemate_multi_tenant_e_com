<?php

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\BranchProductAvailabilityService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('branch create enables only the creating branch', function () {
    $staff = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $branchA = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $branchB = Branch::query()->where('slug', 'uptown')->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.products.store', $branchA), [
            'category_id' => $category->id,
            'name' => 'Downtown Exclusive',
            'slug' => 'downtown-exclusive',
            'sku' => 'SKU-DT-1',
            'status' => true,
            'selling_price' => 19.99,
            'quantity' => 5,
        ])
        ->assertRedirect(route('branch.products.show', [$branchA, 'downtown-exclusive']));

    $product = Product::query()->where('slug', 'downtown-exclusive')->firstOrFail();

    expect($product->isEnabledForBranch($branchA->id))->toBeTrue()
        ->and($product->isEnabledForBranch($branchB->id))->toBeFalse()
        ->and(BranchProduct::query()->where('product_id', $product->id)->count())->toBe(1);
});

test('disabling a product at branch A does not affect branch B', function () {
    $staffA = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $branchA = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $branchB = Branch::query()->where('slug', 'uptown')->firstOrFail();
    $product = Product::factory()->create();
    $availability = app(BranchProductAvailabilityService::class);

    $availability->setEnabled($branchA, $product, true);
    $availability->setEnabled($branchB, $product, true);

    $this->actingAs($staffA, 'branch')
        ->put(route('branch.products.enable', [$branchA, $product]), [
            'status' => false,
        ])
        ->assertRedirect();

    expect($product->isEnabledForBranch($branchA->id))->toBeFalse()
        ->and($product->isEnabledForBranch($branchB->id))->toBeTrue();
});

test('branch routes do not expose product destroy', function () {
    expect(Route::has('branch.products.destroy'))->toBeFalse();
});
