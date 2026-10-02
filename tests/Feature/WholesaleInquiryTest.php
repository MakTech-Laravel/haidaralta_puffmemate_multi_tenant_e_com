<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Enums\WholesaleInquiryStatus;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Models\WholesaleInquiry;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function wholesaleInquiryPayload(array $overrides = []): array
{
    return [
        'full_name' => 'Alex Wholesale',
        'phone' => '0412345678',
        'company' => 'Alex Co',
        'email' => 'alex@example.com',
        'address' => '12 Example St, Sydney NSW',
        'latitude' => '-33.8688',
        'longitude' => '151.2093',
        'message' => 'Looking for wholesale supply.',
        ...$overrides,
    ];
}

test('guest can submit a valid wholesale inquiry', function () {
    $this->from(route('wholesale'))
        ->post(route('wholesale.store'), wholesaleInquiryPayload())
        ->assertRedirect(route('wholesale'));

    $inquiry = WholesaleInquiry::query()->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->full_name)->toBe('Alex Wholesale')
        ->and($inquiry->phone)->toBe('0412345678')
        ->and($inquiry->email)->toBe('alex@example.com')
        ->and($inquiry->address)->toBe('12 Example St, Sydney NSW')
        ->and($inquiry->latitude)->toBe('-33.8688')
        ->and($inquiry->longitude)->toBe('151.2093')
        ->and($inquiry->message)->toBe('Looking for wholesale supply.')
        ->and($inquiry->status)->toBe(WholesaleInquiryStatus::New)
        ->and($inquiry->read_at)->toBeNull();
});

test('wholesale inquiry validation requires name phone address pin and message', function () {
    $this->from(route('wholesale'))
        ->post(route('wholesale.store'), [])
        ->assertRedirect(route('wholesale'))
        ->assertSessionHasErrors(['full_name', 'phone', 'address', 'latitude', 'longitude', 'message']);

    expect(WholesaleInquiry::query()->count())->toBe(0);
});

test('platform admin with manage-users can index and show marks inquiry read', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $inquiry = WholesaleInquiry::factory()->create();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.wholesale-inquiries.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/wholesale-inquiries/index')
            ->has('table.data', 1));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.wholesale-inquiries.show', $inquiry))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/wholesale-inquiries/show')
            ->where('inquiry.id', $inquiry->id)
            ->where('inquiry.status', WholesaleInquiryStatus::Read->value)
            ->where('inquiry.map.google', fn ($url) => is_string($url) && str_contains($url, 'google.com/maps'))
            ->where('inquiry.map.open_street_map', fn ($url) => is_string($url) && str_contains($url, 'openstreetmap.org'))
            ->has('inquiry.map.embed'));

    expect($inquiry->fresh()->status)->toBe(WholesaleInquiryStatus::Read)
        ->and($inquiry->fresh()->read_at)->not->toBeNull();
});

test('platform admin can open wholesale place-order without branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $inquiry = WholesaleInquiry::factory()->create([
        'full_name' => 'Jordan Wholesale',
        'phone' => '0499888777',
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.wholesale-inquiries.place-order', $inquiry))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/wholesale-inquiries/place-order')
            ->where('inquiry.id', $inquiry->id)
            ->where('inquiry.full_name', 'Jordan Wholesale')
            ->where('inquiry.phone', '0499888777')
            ->where('branch', null)
            ->where('products', [])
            ->has('branchOptionsUrl'));
});

test('platform admin place-order loads stock for selected branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $inquiry = WholesaleInquiry::factory()->create();
    $branch = Branch::query()->firstOrFail();
    $otherBranch = Branch::query()->whereKeyNot($branch->id)->firstOrFail();

    $product = Product::factory()->create([
        'status' => true,
        'name' => 'Wholesale Branch Pack',
        'selling_price' => 19.5,
    ]);
    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 12,
        'reserved_quantity' => 2,
        'status' => true,
    ]);

    $otherProduct = Product::factory()->create([
        'status' => true,
        'name' => 'Other Branch Pack',
    ]);
    Inventory::factory()->create([
        'branch_id' => $otherBranch->id,
        'product_id' => $otherProduct->id,
        'quantity' => 20,
        'status' => true,
    ]);

    $response = $this->actingAs($admin, 'platform')
        ->get(route('platform.wholesale-inquiries.place-order', [
            'wholesaleInquiry' => $inquiry,
            'branch' => $branch->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/wholesale-inquiries/place-order')
            ->where('branch.id', $branch->id)
            ->where('products.0.id', (string) $product->id)
            ->where('products.0.stock', 10)
            ->where('products.0.branch_id', $branch->id));

    $ids = collect($response->inertiaProps('products'))->pluck('id');

    expect($ids)->toContain((string) $product->id)
        ->and($ids)->not->toContain((string) $otherProduct->id);
});

test('guest cannot access wholesale inquiry platform routes', function () {
    $inquiry = WholesaleInquiry::factory()->create();

    $this->get(route('platform.wholesale-inquiries.index'))
        ->assertRedirect();

    $this->get(route('platform.wholesale-inquiries.show', $inquiry))
        ->assertRedirect();

    $this->get(route('platform.wholesale-inquiries.place-order', $inquiry))
        ->assertRedirect();
});

test('platform user without manage-users cannot access wholesale inquiries', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $inquiry = WholesaleInquiry::factory()->create();

    $this->actingAs($user, 'platform')
        ->get(route('platform.wholesale-inquiries.index'))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->get(route('platform.wholesale-inquiries.show', $inquiry))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->get(route('platform.wholesale-inquiries.place-order', $inquiry))
        ->assertForbidden();
});
