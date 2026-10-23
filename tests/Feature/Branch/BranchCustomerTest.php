<?php

use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

function branchCustomerWithFulfillment(Branch $branch, array $customerOverrides = []): User
{
    $customer = User::factory()->create(array_merge([
        'user_type' => UserType::CUSTOMER,
        'branch_id' => null,
        'name' => 'Branch Shopper',
        'phone' => '+61488880001',
    ], $customerOverrides));

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
    ]);
    OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $branch->id,
        'status' => FulfillmentStatus::Delivered,
    ]);
    Payment::factory()->create(['order_id' => $order->id]);

    return $customer;
}

test('guests are redirected from branch customers', function () {
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->get(route('branch.customers.index', $sydney))
        ->assertRedirect(route('login'));
});

test('branch staff see only customers with fulfillments at their branch', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $other = Branch::factory()->create();

    $mine = branchCustomerWithFulfillment($sydney, [
        'name' => 'Sydney Buyer',
        'phone' => '+61488880011',
    ]);
    branchCustomerWithFulfillment($other, [
        'name' => 'Other Buyer',
        'phone' => '+61488880022',
    ]);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.customers.index', $sydney))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/customers/index')
            ->has('table.data', 1)
            ->where('table.data.0.id', $mine->id)
            ->where('table.data.0.name', 'Sydney Buyer')
            ->where('table.data.0.orders_at_branch_count', 1));
});

test('branch staff can view a customer who ordered at their branch', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $customer = branchCustomerWithFulfillment($sydney);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.customers.show', [$sydney, $customer]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/customers/show')
            ->where('customer.id', $customer->id)
            ->where('customer.name', $customer->name)
            ->has('recentOrders', 1)
            ->has('recentOrders.0.fulfillmentId'));
});

test('branch staff cannot view a customer who only ordered at another branch', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $other = Branch::factory()->create();
    $customer = branchCustomerWithFulfillment($other);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.customers.show', [$sydney, $customer]))
        ->assertNotFound();
});
