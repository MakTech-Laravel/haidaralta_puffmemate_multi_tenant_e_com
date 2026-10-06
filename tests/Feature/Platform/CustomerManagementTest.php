<?php

use App\Enums\AustralianState;
use App\Enums\CustomerAddressType;
use App\Enums\CustomerRegistrationStatus;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
});

test('platform admin with manage-users can open customers index', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $customer = User::factory()->create([
        'name' => 'List Customer',
        'user_type' => UserType::CUSTOMER,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.index', [
            'filter' => ['search' => 'List Customer'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/customers/index')
            ->has('table.data', 1)
            ->where('table.data.0.id', $customer->id)
            ->where('table.data.0.name', 'List Customer'));
});

test('customers index excludes non-customer users', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $platformUser = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('table.data', fn ($rows) => collect($rows)->every(
                fn (array $row): bool => $row['id'] !== $platformUser->id,
            )));
});

test('guest cannot access customers routes', function () {
    $customer = User::factory()->create([
        'user_type' => UserType::CUSTOMER,
    ]);

    $this->get(route('platform.customers.index'))->assertRedirect();
    $this->get(route('platform.customers.create'))->assertRedirect();
    $this->post(route('platform.customers.store'), [])->assertRedirect();
    $this->get(route('platform.customers.show', $customer))->assertRedirect();
    $this->get(route('platform.customers.edit', $customer))->assertRedirect();
    $this->put(route('platform.customers.update', $customer), [])->assertRedirect();
});

test('platform user without manage-users cannot access customers', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
        'registration_status' => null,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $customer = User::factory()->create([
        'user_type' => UserType::CUSTOMER,
    ]);

    $this->actingAs($user, 'platform')
        ->get(route('platform.customers.index'))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->get(route('platform.customers.create'))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->post(route('platform.customers.store'), [
            'name' => 'Nope',
            'phone' => '0412000111',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->get(route('platform.customers.show', $customer))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->get(route('platform.customers.edit', $customer))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->put(route('platform.customers.update', $customer), [
            'name' => 'Nope',
            'phone' => '0412000111',
            'is_active' => false,
        ])
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->post(route('platform.customers.toggle-status', $customer))
        ->assertForbidden();
});

test('customers index search matches name phone and email', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    User::factory()->create([
        'name' => 'Searchable Shopper',
        'phone' => '+61488880001',
        'email' => 'searchable-shopper@test.com',
        'user_type' => UserType::CUSTOMER,
    ]);
    User::factory()->create([
        'name' => 'Other Shopper',
        'user_type' => UserType::CUSTOMER,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.index', [
            'filter' => ['search' => 'Searchable Shopper'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.name', 'Searchable Shopper'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.index', [
            'filter' => ['search' => 'searchable-shopper@test.com'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.name', 'Searchable Shopper'));
});

test('customers index filters by registration status phone verified and active status', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $registered = User::factory()->create([
        'name' => 'Registered Filter',
        'user_type' => UserType::CUSTOMER,
        'registration_status' => CustomerRegistrationStatus::Registered,
        'phone_verified_at' => now(),
        'is_active' => true,
    ]);
    User::factory()->provisional()->create([
        'name' => 'Provisional Filter',
    ]);
    User::factory()->unverified()->create([
        'name' => 'Unverified Phone Filter',
        'user_type' => UserType::CUSTOMER,
        'registration_status' => CustomerRegistrationStatus::Registered,
    ]);
    User::factory()->create([
        'name' => 'Inactive Filter',
        'user_type' => UserType::CUSTOMER,
        'is_active' => false,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.index', [
            'filter' => ['registration_status' => 'provisional'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('table.data', fn ($rows) => collect($rows)->every(
                fn (array $row): bool => $row['registration_status'] === 'provisional',
            )));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.index', [
            'filter' => [
                'registration_status' => 'registered',
                'phone_verified' => 'verified',
                'search' => 'Registered Filter',
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.id', $registered->id)
            ->where('table.data.0.phone_verified', true)
            ->where('table.data.0.is_active', true));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.index', [
            'filter' => ['phone_verified' => 'unverified'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('table.data', fn ($rows) => collect($rows)->every(
                fn (array $row): bool => $row['phone_verified'] === false,
            )));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.index', [
            'filter' => [
                'is_active' => 'inactive',
                'search' => 'Inactive Filter',
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.name', 'Inactive Filter')
            ->where('table.data.0.is_active', false));
});

test('customers index shows shipping address', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $customer = User::factory()->create([
        'name' => 'Address Customer',
        'user_type' => UserType::CUSTOMER,
    ]);

    CustomerAddress::factory()->shipping()->create([
        'user_id' => $customer->id,
        'address' => '12 Harbour St',
        'city' => 'Sydney',
        'region' => AustralianState::NewSouthWales,
        'zip' => '2000',
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.index', [
            'filter' => ['search' => 'Address Customer'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.address', '12 Harbour St, Sydney, New South Wales, 2000'));
});

test('platform admin can open create customer form', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/customers/create'));
});

test('platform admin can create a registered customer', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $response = $this->actingAs($admin, 'platform')
        ->post(route('platform.customers.store'), [
            'name' => 'New Platform Customer',
            'phone' => '0412888777',
            'email' => 'new-platform-customer@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_active' => true,
        ]);

    $customer = User::query()->where('phone', '+61412888777')->firstOrFail();

    $response
        ->assertRedirect(route('platform.customers.show', $customer))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Customer created.',
        ]);

    expect($customer->user_type)->toBe(UserType::CUSTOMER)
        ->and($customer->registration_status)->toBe(CustomerRegistrationStatus::Registered)
        ->and($customer->branch_id)->toBeNull()
        ->and($customer->is_active)->toBeTrue()
        ->and($customer->phone_verified_at)->not->toBeNull()
        ->and($customer->email_verified_at)->not->toBeNull()
        ->and($customer->email)->toBe('new-platform-customer@test.com');
});

test('creating a customer validates required fields and unique phone', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $existing = User::factory()->create([
        'phone' => '+61412999988',
        'user_type' => UserType::CUSTOMER,
    ]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.customers.create'))
        ->post(route('platform.customers.store'), [])
        ->assertRedirect(route('platform.customers.create'))
        ->assertSessionHasErrors(['name', 'phone', 'password']);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.customers.create'))
        ->post(route('platform.customers.store'), [
            'name' => 'Dup Phone',
            'phone' => '0412999988',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('platform.customers.create'))
        ->assertSessionHasErrors(['phone']);

    expect(User::query()->where('name', 'Dup Phone')->exists())->toBeFalse()
        ->and($existing->fresh()->phone)->toBe('+61412999988');
});

test('platform admin can view customer details', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $customer = User::factory()->create([
        'name' => 'Show Customer',
        'user_type' => UserType::CUSTOMER,
        'is_active' => true,
    ]);

    CustomerAddress::factory()->shipping()->create([
        'user_id' => $customer->id,
        'address' => '9 Bridge Rd',
        'city' => 'Melbourne',
        'region' => AustralianState::Victoria,
        'zip' => '3000',
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/customers/show')
            ->where('customer.id', $customer->id)
            ->where('customer.name', 'Show Customer')
            ->where('customer.is_active', true)
            ->where('customer.address', '9 Bridge Rd, Melbourne, Victoria, 3000')
            ->has('recentOrders', 0)
            ->where('canViewOrders', true));
});

test('customer show includes recent orders', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $customer = User::factory()->create([
        'name' => 'Orders Customer',
        'user_type' => UserType::CUSTOMER,
    ]);

    $order = Order::factory()->for($customer, 'customer')->create([
        'order_number' => 'PMM-SHOW-0001',
        'total_amount' => 42.5,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/customers/show')
            ->has('recentOrders', 1)
            ->where('recentOrders.0.id', $order->id)
            ->where('recentOrders.0.order_number', 'PMM-SHOW-0001')
            ->where('recentOrders.0.total_label', '$42.50'));
});

test('customer show returns 404 for non-customer users', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $platformUser = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.show', $platformUser))
        ->assertNotFound();
});

test('platform admin can open edit customer form', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $customer = User::factory()->create([
        'name' => 'Edit Form Customer',
        'user_type' => UserType::CUSTOMER,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.customers.edit', $customer))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/customers/edit')
            ->where('customer.id', $customer->id)
            ->where('customer.name', 'Edit Form Customer'));
});

test('platform admin can update and disable a customer', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $customer = User::factory()->create([
        'name' => 'Before Update',
        'phone' => '+61413000001',
        'email' => 'before-update@test.com',
        'user_type' => UserType::CUSTOMER,
        'is_active' => true,
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.customers.update', $customer), [
            'name' => 'After Update',
            'phone' => '0413000002',
            'email' => 'after-update@test.com',
            'is_active' => false,
        ])
        ->assertRedirect(route('platform.customers.show', $customer))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Customer updated.',
        ]);

    $customer->refresh();

    expect($customer->name)->toBe('After Update')
        ->and($customer->phone)->toBe('+61413000002')
        ->and($customer->email)->toBe('after-update@test.com')
        ->and($customer->is_active)->toBeFalse()
        ->and($customer->email_verified_at)->not->toBeNull();
});

test('platform admin can toggle customer active status', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $customer = User::factory()->create([
        'name' => 'Toggle Customer',
        'user_type' => UserType::CUSTOMER,
        'is_active' => true,
    ]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.customers.index'))
        ->post(route('platform.customers.toggle-status', $customer))
        ->assertRedirect(route('platform.customers.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Customer deactivated.',
        ]);

    expect($customer->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.customers.index'))
        ->post(route('platform.customers.toggle-status', $customer))
        ->assertRedirect(route('platform.customers.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Customer activated.',
        ]);

    expect($customer->fresh()->is_active)->toBeTrue();
});

test('customer toggle status returns 404 for non-customer users', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $platformUser = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.customers.toggle-status', $platformUser))
        ->assertNotFound();
});

test('platform admin can create a customer with shipping and billing addresses', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.customers.store'), [
            'name' => 'Addressed Customer',
            'phone' => '0412888666',
            'email' => 'addressed-customer@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_active' => true,
            'shipping' => [
                'address' => '10 George St',
                'city' => 'Sydney',
                'region' => 'nsw',
                'zip' => '2000',
            ],
            'billing' => [
                'name' => 'Addressed Customer',
                'address' => '20 Queen St',
                'city' => 'Brisbane',
                'region' => 'qld',
                'zip' => '4000',
                'email' => 'billing@test.com',
                'phone' => '0412888667',
            ],
        ])
        ->assertRedirect();

    $customer = User::query()->where('phone', '+61412888666')->firstOrFail();

    expect($customer->shippingAddress)->not->toBeNull()
        ->and($customer->shippingAddress->address)->toBe('10 George St')
        ->and($customer->shippingAddress->region)->toBe(AustralianState::NewSouthWales)
        ->and($customer->billingAddress)->not->toBeNull()
        ->and($customer->billingAddress->city)->toBe('Brisbane')
        ->and($customer->billingAddress->region)->toBe(AustralianState::Queensland)
        ->and($customer->billingAddress->phone)->toBe('+61412888667');
});

test('platform admin can update customer shipping and billing addresses', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $customer = User::factory()->create([
        'name' => 'Update Address Customer',
        'phone' => '+61413000010',
        'user_type' => UserType::CUSTOMER,
        'is_active' => true,
    ]);

    CustomerAddress::factory()->shipping()->create([
        'user_id' => $customer->id,
        'address' => 'Old Street',
        'city' => 'Perth',
        'region' => AustralianState::WesternAustralia,
        'zip' => '6000',
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.customers.update', $customer), [
            'name' => 'Update Address Customer',
            'phone' => '0413000010',
            'is_active' => true,
            'shipping' => [
                'address' => '99 Collins St',
                'city' => 'Melbourne',
                'region' => 'vic',
                'zip' => '3000',
            ],
            'billing' => [
                'name' => 'Bill To',
                'address' => '1 King St',
                'city' => 'Adelaide',
                'region' => 'sa',
                'zip' => '5000',
                'email' => null,
                'phone' => '0413000011',
            ],
        ])
        ->assertRedirect(route('platform.customers.show', $customer));

    $customer->refresh()->load(['shippingAddress', 'billingAddress']);

    expect($customer->shippingAddress->address)->toBe('99 Collins St')
        ->and($customer->shippingAddress->region)->toBe(AustralianState::Victoria)
        ->and($customer->billingAddress)->not->toBeNull()
        ->and($customer->billingAddress->type)->toBe(CustomerAddressType::Billing)
        ->and($customer->billingAddress->city)->toBe('Adelaide')
        ->and($customer->billingAddress->phone)->toBe('+61413000011');
});
