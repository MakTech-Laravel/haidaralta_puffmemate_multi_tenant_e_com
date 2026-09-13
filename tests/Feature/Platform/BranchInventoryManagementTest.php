<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\ProductService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform can enable a platform-created product for a specific branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $product = app(ProductService::class)->createFromPlatform([
        'category_id' => Category::factory()->create()->id,
        'name' => 'Platform Pack',
        'slug' => 'platform-pack',
        'status' => true,
    ]);

    expect($product->isEnabledForBranch($branch->id))->toBeFalse();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.enable', [$branch, $product]), [
            'status' => true,
        ])
        ->assertRedirect();

    expect($product->fresh()->isEnabledForBranch($branch->id))->toBeTrue();
});

test('platform can set stock and price for any branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', 'uptown')->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 12,
            'minimum_stock' => 3,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 29.5,
            'discount_price' => 24.0,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.show', [$branch, $product]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/products/show')
            ->where('product.inventory.quantity', 12)
            ->where('product.price.selling_price', '29.50'));
});

test('branch user still cannot soft-delete catalog products after platform inventory ops', function () {
    $product = Product::factory()->create(['slug' => 'still-protected']);
    $staff = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->delete(route('platform.products.destroy', $product))
        ->assertRedirect(route('login'));

    expect(Product::query()->where('slug', 'still-protected')->exists())->toBeTrue();
});
