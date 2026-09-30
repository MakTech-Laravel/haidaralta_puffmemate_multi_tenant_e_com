<?php

use App\Enums\InventoryTransactionType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverInventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->otherBranch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $this->driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
});

test('guests are redirected from platform product assign transactions', function () {
    $this->get(route('platform.reports.product-assign-transactions'))
        ->assertRedirect(route('login'));
});

test('platform staff without manage-drivers cannot open product assign transactions', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($user, 'platform')
        ->get(route('platform.reports.product-assign-transactions'))
        ->assertForbidden();
});

test('platform admin can view product assign transactions with branch column', function () {
    $product = Product::factory()->create([
        'status' => true,
        'name' => 'Platform Assign Report Pack',
        'sku' => 'PARP-001',
    ]);

    DriverInventoryTransaction::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->driver->id,
        'product_id' => $product->id,
        'type' => InventoryTransactionType::DriverAssign,
        'quantity' => 2,
        'before_quantity' => 1,
        'after_quantity' => 3,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.product-assign-transactions'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/reports/product-assign-transactions')
            ->has('table.data', 1)
            ->where('table.data.0.driver_id', $this->driver->id)
            ->where('table.data.0.product_name', 'Platform Assign Report Pack')
            ->where('table.data.0.product_sku', 'PARP-001')
            ->where('table.data.0.branch_name', $this->branch->name)
            ->where('table.data.0.quantity', 2)
            ->where('table.data.0.before_quantity', 1)
            ->where('table.data.0.after_quantity', 3)
            ->where('table.filterOptions.types.0.value', InventoryTransactionType::DriverAssign->value));
});

test('platform product assign transactions can be filtered by branch_id', function () {
    $product = Product::factory()->create(['status' => true]);

    DriverInventoryTransaction::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->driver->id,
        'product_id' => $product->id,
        'type' => InventoryTransactionType::DriverAssign,
        'quantity' => 2,
        'before_quantity' => 0,
        'after_quantity' => 2,
    ]);
    DriverInventoryTransaction::factory()->create([
        'branch_id' => $this->otherBranch->id,
        'user_id' => $this->driver->id,
        'product_id' => $product->id,
        'type' => InventoryTransactionType::DriverAssign,
        'quantity' => 9,
        'before_quantity' => 0,
        'after_quantity' => 9,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.product-assign-transactions', [
            'filter' => [
                'branch_id' => $this->otherBranch->id,
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/reports/product-assign-transactions')
            ->has('table.data', 1)
            ->where('table.data.0.quantity', 9)
            ->where('table.data.0.branch_name', $this->otherBranch->name)
            ->where('table.filterOptions.selected_branch.id', $this->otherBranch->id)
            ->where('table.filterOptions.selected_branch.name', $this->otherBranch->name));
});

test('platform product assign transactions merge branch query into filter', function () {
    $product = Product::factory()->create(['status' => true]);

    DriverInventoryTransaction::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->driver->id,
        'product_id' => $product->id,
        'type' => InventoryTransactionType::DriverAssign,
        'quantity' => 1,
        'before_quantity' => 0,
        'after_quantity' => 1,
    ]);
    DriverInventoryTransaction::factory()->create([
        'branch_id' => $this->otherBranch->id,
        'user_id' => $this->driver->id,
        'product_id' => $product->id,
        'type' => InventoryTransactionType::DriverAssign,
        'quantity' => 4,
        'before_quantity' => 0,
        'after_quantity' => 4,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.product-assign-transactions', [
            'branch' => $this->branch->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/reports/product-assign-transactions')
            ->has('table.data', 1)
            ->where('table.data.0.quantity', 1)
            ->where('table.data.0.branch_name', $this->branch->name));
});
