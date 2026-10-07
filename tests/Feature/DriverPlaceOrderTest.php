<?php

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DriverBranchAssignment;
use App\Models\DriverInventory;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from driver place order', function () {
    $this->get(route('driver.place-order.index'))
        ->assertRedirect(route('login'));
});

test('drivers see empty products when they have no inventory', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.place-order.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/place-order/index')
            ->has('products', 0)
            ->where('canSell', false)
            ->has('paymentMethods')
            ->where('paymentMethods.0.id', 'cod')
            ->missing('customers'));
});

test('drivers see assigned inventory products on place order', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
        'branch_id' => null,
    ]);
    $branch = Branch::factory()->create();
    DriverBranchAssignment::factory()->create([
        'driver_id' => $driver->id,
        'branch_id' => $branch->id,
    ]);
    $category = Category::factory()->create(['name' => 'Menthol']);
    $product = Product::factory()->create([
        'status' => true,
        'name' => 'Assigned Menthol Pack',
        'sku' => 'AMP-100',
        'selling_price' => 14.50,
    ]);
    $product->categories()->sync([$category->id]);

    DriverInventory::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 7,
    ]);

    $otherBranch = Branch::factory()->create();
    DriverInventory::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $otherBranch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 3,
    ]);

    $inactive = Product::factory()->create([
        'status' => false,
        'name' => 'Inactive Stock',
    ]);
    DriverInventory::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $branch->id,
        'product_id' => $inactive->id,
        'quantity' => 5,
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.place-order.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/place-order/index')
            ->has('products', 1)
            ->where('products.0.id', (string) $product->id)
            ->where('products.0.name', 'Assigned Menthol Pack')
            ->where('products.0.sku', 'AMP-100')
            ->where('products.0.category', 'Menthol')
            ->where('products.0.stock', 7)
            ->where('products.0.price', 14.5)
            ->where('canSell', true)
            ->where('paymentMethods.0.id', 'cod'));
});
