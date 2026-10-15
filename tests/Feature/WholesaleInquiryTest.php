<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Enums\WholesaleInquiryStatus;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WholesaleInquiry;
use App\Models\WholesaleInquiryItem;
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

test('platform admin inquiry search matches company names', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $match = WholesaleInquiry::factory()->create(['company' => 'Harbour Traders Pty Ltd']);
    WholesaleInquiry::factory()->create(['company' => 'Other Co']);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.wholesale-inquiries.index', ['filter' => ['search' => 'Harbour']]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.id', $match->id));
});

test('platform admin with manage-wholesale can index and show marks inquiry read', function () {
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
    $product = Product::factory()->create([
        'status' => true,
        'selling_price' => 19.5,
        'wholesale_price' => 14.25,
        'min_wholesale_qty' => 24,
    ]);
    WholesaleInquiryItem::factory()->create([
        'wholesale_inquiry_id' => $inquiry->id,
        'product_id' => $product->id,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.wholesale-inquiries.place-order', $inquiry))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/wholesale-inquiries/place-order')
            ->where('inquiry.id', $inquiry->id)
            ->where('inquiry.full_name', 'Jordan Wholesale')
            ->where('inquiry.phone', '0499888777')
            ->where('inquiry.items.0.unit_price', 14.25)
            ->where('inquiry.items.0.min_quantity', 24)
            ->where('branch', null)
            ->where('products', [])
            ->has('branchOptionsUrl'));
});

test('platform admin can open blank wholesale place-order from create route', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.wholesale-inquiries.place-order.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/wholesale-inquiries/place-order')
            ->where('inquiry', null)
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

    $this->get(route('platform.wholesale-inquiries.place-order.create'))
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

    $this->actingAs($user, 'platform')
        ->get(route('platform.wholesale-inquiries.place-order.create'))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->post(route('platform.wholesale-inquiries.reject', $inquiry), ['reason' => 'No'])
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->post(route('platform.wholesale-orders.store'), [])
        ->assertForbidden();

    expect($inquiry->fresh()->status)->toBe(WholesaleInquiryStatus::New);
});

test('wholesale inquiry with products saves item snapshots and message becomes optional', function () {
    $product = Product::factory()->create([
        'status' => true,
        'name' => 'Bulk Pod Pack',
        'sku' => 'BULK-POD-1',
    ]);

    $this->from(route('wholesale'))
        ->post(route('wholesale.store'), wholesaleInquiryPayload([
            'message' => '',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 40],
            ],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('wholesale'));

    $inquiry = WholesaleInquiry::query()->with('items')->firstOrFail();
    $item = $inquiry->items->first();

    expect($inquiry->items)->toHaveCount(1)
        ->and($inquiry->customer_id)->toBeNull()
        ->and($item->product_id)->toBe($product->id)
        ->and($item->quantity)->toBe(40)
        ->and($item->product_name_snapshot)->toBe('Bulk Pod Pack')
        ->and($item->sku_snapshot)->toBe('BULK-POD-1');
});

test('wholesale inquiry rejects quantities below the product minimum', function () {
    $product = Product::factory()->create(['status' => true, 'min_wholesale_qty' => 50]);

    $this->from(route('wholesale'))
        ->post(route('wholesale.store'), wholesaleInquiryPayload([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10],
            ],
        ]))
        ->assertSessionHasErrors(['items.0.quantity' => 'Minimum wholesale quantity is 50.']);

    expect(WholesaleInquiry::query()->count())->toBe(0);
});

test('wholesale inquiry rejects inactive products', function () {
    $inactive = Product::factory()->create(['status' => false]);

    $this->from(route('wholesale'))
        ->post(route('wholesale.store'), wholesaleInquiryPayload([
            'items' => [
                ['product_id' => $inactive->id, 'quantity' => 100],
            ],
        ]))
        ->assertSessionHasErrors('items.0.product_id');

    expect(WholesaleInquiry::query()->count())->toBe(0)
        ->and(WholesaleInquiryItem::query()->count())->toBe(0);
});

test('wholesale inquiry keeps one line per product option and snapshots the option', function () {
    $product = Product::factory()->create(['status' => true, 'name' => 'Bulk Pod Pack', 'sku' => 'BULK-POD']);
    $mint = ProductVariant::factory()->create(['product_id' => $product->id, 'name' => 'Mint', 'sku' => 'BULK-MINT', 'status' => true]);

    $this->from(route('wholesale'))
        ->post(route('wholesale.store'), wholesaleInquiryPayload([
            'items' => [
                ['product_id' => $product->id, 'variant_id' => $mint->id, 'quantity' => 20],
                ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 10],
            ],
        ]))
        ->assertSessionHasNoErrors();

    $items = WholesaleInquiryItem::query()->orderBy('id')->get();

    expect($items)->toHaveCount(2)
        ->and($items[0]->variant_id)->toBe($mint->id)
        ->and($items[0]->product_name_snapshot)->toBe('Bulk Pod Pack · Mint')
        ->and($items[0]->sku_snapshot)->toBe('BULK-MINT')
        ->and($items[1]->variant_id)->toBeNull()
        ->and($items[1]->product_name_snapshot)->toBe('Bulk Pod Pack');
});

test('wholesale inquiry rejects duplicate lines and options from another product', function () {
    $product = Product::factory()->create(['status' => true]);
    $foreign = ProductVariant::factory()->create(['status' => true]);

    $this->from(route('wholesale'))
        ->post(route('wholesale.store'), wholesaleInquiryPayload([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10],
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]))
        ->assertSessionHasErrors(['items.1.product_id' => 'This product is already in your list.']);

    $this->from(route('wholesale'))
        ->post(route('wholesale.store'), wholesaleInquiryPayload([
            'items' => [
                ['product_id' => $product->id, 'variant_id' => $foreign->id, 'quantity' => 10],
            ],
        ]))
        ->assertSessionHasErrors('items.0.variant_id');

    expect(WholesaleInquiry::query()->count())->toBe(0);
});

test('signed-in customer inquiry is linked to the customer account', function () {
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();

    $this->actingAs($customer, 'customer')
        ->from(route('wholesale'))
        ->post(route('wholesale.store'), wholesaleInquiryPayload())
        ->assertRedirect(route('wholesale'));

    expect(WholesaleInquiry::query()->firstOrFail()->customer_id)->toBe($customer->id);
});

test('new wholesale inquiry notifies platform staff who manage wholesale', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->post(route('wholesale.store'), wholesaleInquiryPayload());

    $inquiry = WholesaleInquiry::query()->firstOrFail();
    $notification = UserNotification::query()
        ->where('user_id', $admin->id)
        ->with('notification')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->notification->title)->toBe('New wholesale inquiry')
        ->and($notification->notification->data['wholesale_inquiry_id'])->toBe($inquiry->id)
        ->and($notification->notification->data['link_url'])
        ->toBe(route('platform.wholesale-inquiries.show', $inquiry));
});

test('wholesale product picker endpoint lists only active products with wholesale pricing', function () {
    $priced = Product::factory()->create([
        'status' => true,
        'name' => 'Picker Priced',
        'selling_price' => 20,
        'wholesale_price' => 14.5,
        'min_wholesale_qty' => 24,
    ]);
    $retail = Product::factory()->create([
        'status' => true,
        'name' => 'Picker Retail',
        'selling_price' => 18,
    ]);
    Product::factory()->create(['status' => false, 'name' => 'Picker Hidden']);

    $response = $this->getJson(route('wholesale.products', ['search' => 'Picker']))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $rows = collect($response->json('data'))->keyBy('id');

    expect($rows[$priced->id]['wholesale_price'])->toEqual(14.5)
        ->and($rows[$priced->id]['min_wholesale_qty'])->toBe(24)
        ->and($rows[$priced->id]['variants'])->toBe([])
        ->and($rows[$retail->id]['wholesale_price'])->toBeNull();
});

test('wholesale product picker endpoint lists active product options only', function () {
    $product = Product::factory()->create(['status' => true, 'name' => 'Picker Options']);
    $active = ProductVariant::factory()->create(['product_id' => $product->id, 'name' => 'Berry', 'sku' => 'PO-BERRY', 'status' => true]);
    ProductVariant::factory()->create(['product_id' => $product->id, 'name' => 'Retired', 'status' => false]);

    $this->getJson(route('wholesale.products', ['search' => 'Picker Options']))
        ->assertOk()
        ->assertJsonPath('data.0.variants', [
            ['id' => $active->id, 'name' => 'Berry', 'sku' => 'PO-BERRY'],
        ]);
});

test('platform admin can reject an open inquiry and the linked customer is notified', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();
    $inquiry = WholesaleInquiry::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.wholesale-inquiries.reject', $inquiry), [
            'reason' => 'Out of stock for this quarter.',
        ])
        ->assertRedirect();

    $inquiry->refresh();

    expect($inquiry->status)->toBe(WholesaleInquiryStatus::Rejected)
        ->and($inquiry->rejection_reason)->toBe('Out of stock for this quarter.')
        ->and($inquiry->reviewed_by)->toBe($admin->id)
        ->and($inquiry->reviewed_at)->not->toBeNull()
        ->and(UserNotification::query()->where('user_id', $customer->id)->exists())->toBeTrue();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.wholesale-inquiries.reject', $inquiry), ['reason' => 'Again'])
        ->assertSessionHasErrors('reason');
});

test('reject requires a reason', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $inquiry = WholesaleInquiry::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.wholesale-inquiries.reject', $inquiry), ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($inquiry->fresh()->status)->toBe(WholesaleInquiryStatus::New);
});

test('platform admin can save an internal note on an inquiry', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $inquiry = WholesaleInquiry::factory()->create();

    $this->actingAs($admin, 'platform')
        ->patch(route('platform.wholesale-inquiries.note', $inquiry), [
            'admin_note' => 'Called back, wants Friday delivery.',
        ])
        ->assertRedirect();

    expect($inquiry->fresh()->admin_note)->toBe('Called back, wants Friday delivery.');
});

test('closed inquiries cannot open the place-order page', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $inquiry = WholesaleInquiry::factory()->rejected()->create();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.wholesale-inquiries.place-order', $inquiry))
        ->assertRedirect(route('platform.wholesale-inquiries.show', $inquiry));
});

test('customers only see their own wholesale requests', function () {
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();
    $mine = WholesaleInquiry::factory()->read()->create(['customer_id' => $customer->id]);
    WholesaleInquiryItem::factory()->create(['wholesale_inquiry_id' => $mine->id, 'quantity' => 12]);
    $theirs = WholesaleInquiry::factory()->create([
        'customer_id' => User::factory()->create(['user_type' => UserType::CUSTOMER])->id,
    ]);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.wholesale-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customer/wholesale-requests/index')
            ->has('requests', 1)
            ->where('requests.0.id', $mine->id)
            ->where('requests.0.statusLabel', 'Under review')
            ->where('requests.0.itemCount', 1));

    $this->actingAs($customer, 'customer')
        ->get(route('customer.wholesale-requests.show', $mine))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customer/wholesale-requests/show')
            ->where('request.id', $mine->id)
            ->where('request.items.0.quantity', 12));

    $this->actingAs($customer, 'customer')
        ->get(route('customer.wholesale-requests.show', $theirs))
        ->assertNotFound();
});
