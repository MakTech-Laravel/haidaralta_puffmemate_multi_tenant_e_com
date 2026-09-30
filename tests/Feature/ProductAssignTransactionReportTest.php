<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverStockAssignmentMode;
use App\Enums\InventoryTransactionType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverInventoryTransaction;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
});

test('guests are redirected from product assign transactions', function () {
    $this->get(route('branch.reports.product-assign-transactions', $this->branch))
        ->assertRedirect(route('login'));
});

test('staff without manage-branch-drivers cannot open product assign transactions', function () {
    $staff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $this->branch->id,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $staff->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.reports.product-assign-transactions', $this->branch))
        ->assertForbidden();
});

test('branch staff can view product assign transactions table', function () {
    $product = Product::factory()->create([
        'status' => true,
        'name' => 'Assign Report Pack',
        'sku' => 'ARP-001',
    ]);

    DriverInventoryTransaction::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->driver->id,
        'product_id' => $product->id,
        'type' => InventoryTransactionType::DriverAssign,
        'quantity' => 2,
        'before_quantity' => 1,
        'after_quantity' => 3,
        'created_by' => $this->staff->id,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.product-assign-transactions', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/reports/product-assign-transactions')
            ->has('table.data', 1)
            ->where('table.data.0.driver_id', $this->driver->id)
            ->where('table.data.0.product_name', 'Assign Report Pack')
            ->where('table.data.0.product_sku', 'ARP-001')
            ->where('table.data.0.type', InventoryTransactionType::DriverAssign->value)
            ->where('table.data.0.quantity', 2)
            ->where('table.data.0.before_quantity', 1)
            ->where('table.data.0.after_quantity', 3)
            ->where('table.filterOptions.types.0.value', InventoryTransactionType::DriverAssign->value));
});

test('product assign transactions can be filtered by driver with selected driver hydrated', function () {
    $secondDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
        'name' => 'Filter Target Driver',
        'phone' => '+61400111222',
    ]);

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
        'branch_id' => $this->branch->id,
        'user_id' => $secondDriver->id,
        'product_id' => $product->id,
        'type' => InventoryTransactionType::DriverAssign,
        'quantity' => 5,
        'before_quantity' => 0,
        'after_quantity' => 5,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.product-assign-transactions', [
            'branch' => $this->branch,
            'filter' => [
                'driver_id' => $secondDriver->id,
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/reports/product-assign-transactions')
            ->has('table.data', 1)
            ->where('table.data.0.driver_id', $secondDriver->id)
            ->where('table.data.0.quantity', 5)
            ->where('table.filterOptions.selected_driver.id', $secondDriver->id)
            ->where('table.filterOptions.selected_driver.name', 'Filter Target Driver')
            ->where('table.filterOptions.selected_driver.phone', '+61400111222'));
});

test('product assign transactions can be filtered by product and search', function () {
    $matching = Product::factory()->create([
        'status' => true,
        'name' => 'Menthol Sticks',
        'sku' => 'MNT-100',
    ]);
    $other = Product::factory()->create([
        'status' => true,
        'name' => 'Other Pack',
        'sku' => 'OTH-200',
    ]);

    DriverInventoryTransaction::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->driver->id,
        'product_id' => $matching->id,
        'type' => InventoryTransactionType::DriverAssign,
        'quantity' => 1,
        'before_quantity' => 0,
        'after_quantity' => 1,
    ]);
    DriverInventoryTransaction::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->driver->id,
        'product_id' => $other->id,
        'type' => InventoryTransactionType::DriverAssign,
        'quantity' => 4,
        'before_quantity' => 0,
        'after_quantity' => 4,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.product-assign-transactions', [
            'branch' => $this->branch,
            'filter' => [
                'product_id' => $matching->id,
                'search' => 'Menthol',
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/reports/product-assign-transactions')
            ->has('table.data', 1)
            ->where('table.data.0.product_id', $matching->id)
            ->where('table.data.0.quantity', 1));
});

test('multi driver assign appears as one history row per driver on the report', function () {
    $secondDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
        'name' => 'Second Assign Driver',
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $secondDriver->id,
        'branch_id' => $this->branch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $product = Product::factory()->create(['status' => true, 'name' => 'Shared Assign SKU']);
    Inventory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'status' => true,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.assign-products.store', $this->branch), [
            'mode' => DriverStockAssignmentMode::Multiple->value,
            'driver_ids' => [$this->driver->id, $secondDriver->id],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ])
        ->assertRedirect(route('branch.assign-products.create', $this->branch));

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.product-assign-transactions', [
            'branch' => $this->branch,
            'filter' => ['product_id' => $product->id],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/reports/product-assign-transactions')
            ->has('table.data', 2)
            ->where('table.data.0.quantity', 2)
            ->where('table.data.1.quantity', 2));
});
