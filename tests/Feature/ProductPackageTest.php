<?php

use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\OrderItem;
use App\Models\PaymentGatewaySetting;
use App\Models\Product;
use App\Models\ProductPackage;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Catalog\DeliverySettingsService;
use App\Support\Geo\Polygon;
use App\Support\Storefront\StorefrontCatalog;
use Database\Seeders\ApplicationSettingsSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(ApplicationSettingsSeeder::class);
    app(PaymentGatewaySettingsService::class)->ensureDefaults();
    app(DeliverySettingsService::class)->ensureDefaults();
    seedCheckoutDeliveryShifts();
    config(['catalog.show_product_packages' => true]);
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-11-09 10:00:00', config('app.timezone')));
});

afterEach(function () {
    Carbon\Carbon::setTestNow();
});

function productPackageCheckoutBranchWithStock(Product $product, int $quantity = 10): Branch
{
    $branch = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => 50,
        'status' => true,
    ]);

    BranchArea::factory()->forBranch($branch)->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => $quantity,
        'status' => true,
    ]);

    return $branch;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function productPackageCheckoutPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ada Lovelace',
        'mobile' => '0410123456',
        'address' => '1 Test Street',
        'city' => 'Sydney',
        'postal_code' => '2000',
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_method' => 'regular',
        'time_slot_id' => checkoutDeliveryShiftId(),
        'payment_method' => PaymentGatewaySetting::KEY_COD,
        'terms' => true,
    ], $overrides);
}

test('platform admin can create multiple packages for a product', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.packages.store', $product), [
            'label' => 'Pack of 2',
            'quantity' => 2,
            'price' => 160,
            'status' => true,
            'sort_order' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.packages.store', $product), [
            'label' => 'Pack of 5',
            'quantity' => 5,
            'price' => 350,
            'status' => true,
            'sort_order' => 2,
        ])
        ->assertRedirect();

    expect($product->packages()->count())->toBe(2);
});

test('platform product edit page includes packages for management', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();
    ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Edit Page Pack',
        'status' => true,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.edit', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/catalog/products/edit')
            ->where('product.packages_enabled', true)
            ->has('product.packages', 1)
            ->where('product.packages.0.label', 'Edit Page Pack')
            ->etc());
});

test('disabling one package leaves the other purchasable', function () {
    $product = Product::factory()->create([
        'slug' => 'pkg-independent-status',
        'selling_price' => 100,
        'status' => true,
    ]);
    $enabled = ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Pack of 2',
        'quantity' => 2,
        'price' => 160,
        'status' => true,
    ]);
    ProductPackage::factory()->disabled()->create([
        'product_id' => $product->id,
        'label' => 'Pack of 10',
        'quantity' => 10,
        'price' => 600,
    ]);

    $detail = StorefrontCatalog::productDetail($product);

    expect($detail['packages'])->toHaveCount(1)
        ->and($detail['packages'][0]['id'])->toBe($enabled->id);

    $this->post(route('cart.store'), [
        'product' => $product->slug,
        'quantity' => 1,
        'product_package_id' => $enabled->id,
    ])->assertRedirect();

    expect(CartItem::query()->sole()->product_package_id)->toBe($enabled->id);
});

test('platform admin can edit and soft delete a package', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();
    $package = ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Pack of 3',
        'quantity' => 3,
        'price' => 225,
        'status' => true,
    ]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.products.edit', $product))
        ->put(route('platform.products.packages.update', [$product, $package]), [
            'label' => 'Pack of 3 (updated)',
            'quantity' => 3,
            'price' => 210,
            'status' => false,
            'sort_order' => 0,
        ])
        ->assertRedirect(route('platform.products.edit', $product));

    expect($package->fresh())
        ->label->toBe('Pack of 3 (updated)')
        ->price->toBe('210.00')
        ->status->toBeFalse();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.products.edit', $product))
        ->delete(route('platform.products.packages.destroy', [$product, $package]))
        ->assertRedirect(route('platform.products.edit', $product));

    expect(ProductPackage::withTrashed()->find($package->id)?->trashed())->toBeTrue();
});

test('platform product show lists enabled and disabled packages', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();
    ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Enabled Pack',
        'status' => true,
    ]);
    ProductPackage::factory()->disabled()->create([
        'product_id' => $product->id,
        'label' => 'Disabled Pack',
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.show', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/catalog/products/show')
            ->has('product.packages', 2)
            ->where('product.packages.0.label', fn ($label) => in_array($label, ['Enabled Pack', 'Disabled Pack'], true))
            ->etc());
});

test('storefront product detail returns only enabled packages', function () {
    $product = Product::factory()->create([
        'status' => true,
        'slug' => 'pkg-detail',
        'selling_price' => 100,
        'discount_type' => 'none',
        'discount_price' => null,
    ]);
    $enabled = ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Pack of 2',
        'quantity' => 2,
        'price' => 160,
        'status' => true,
        'sort_order' => 1,
    ]);
    ProductPackage::factory()->disabled()->create([
        'product_id' => $product->id,
        'label' => 'Pack of 10',
        'quantity' => 10,
        'price' => 600,
        'sort_order' => 2,
    ]);

    $detail = StorefrontCatalog::productDetail($product);

    expect($detail['packages'])->toHaveCount(1)
        ->and($detail['packages'][0]['id'])->toBe($enabled->id)
        ->and($detail['packages'][0]['label'])->toBe('Pack of 2')
        ->and($detail['unitPriceAmount'])->toBe(100.0)
        ->and($detail['packages'][0]['unitTotal'])->toBe(200.0)
        ->and($detail['packages'][0]['formattedUnitTotal'])->toBe('A$200.00')
        ->and($detail['packages'][0]['savings'])->toBe(40.0)
        ->and($detail['packages'][0]['formattedSavings'])->toBe('A$40.00');

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/product')
            ->has('product.packages', 1)
            ->where('product.packages.0.id', $enabled->id)
            ->where('product.unitPriceAmount', 100)
            ->where('product.packages.0.unitTotal', 200)
            ->where('product.packages.0.savings', 40)
            ->etc());
});

test('cart add requires an enabled package when packages exist', function () {
    $product = Product::factory()->create([
        'slug' => 'pkg-required',
        'selling_price' => 100,
        'status' => true,
    ]);
    ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Pack of 2',
        'quantity' => 2,
        'price' => 160,
        'status' => true,
    ]);

    $this->post(route('cart.store'), [
        'product' => $product->slug,
        'quantity' => 1,
    ])->assertSessionHasErrors('product');

    expect(CartItem::query()->count())->toBe(0);
});

test('cart rejects disabled and foreign package ids', function () {
    $product = Product::factory()->create([
        'slug' => 'pkg-disabled-cart',
        'selling_price' => 100,
        'status' => true,
    ]);
    $other = Product::factory()->create(['status' => true]);
    $disabled = ProductPackage::factory()->disabled()->create([
        'product_id' => $product->id,
        'price' => 160,
    ]);
    $foreign = ProductPackage::factory()->create([
        'product_id' => $other->id,
        'price' => 99,
        'status' => true,
    ]);
    ProductPackage::factory()->create([
        'product_id' => $product->id,
        'price' => 100,
        'status' => true,
    ]);

    $this->post(route('cart.store'), [
        'product' => $product->slug,
        'quantity' => 1,
        'product_package_id' => $disabled->id,
    ])->assertSessionHasErrors('product');

    $this->post(route('cart.store'), [
        'product' => $product->slug,
        'quantity' => 1,
        'product_package_id' => $foreign->id,
    ])->assertSessionHasErrors('product');

    expect(CartItem::query()->count())->toBe(0);
});

test('enabled package uses package price in cart and reserves multiplied inventory on checkout', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'slug' => 'pkg-checkout',
        'selling_price' => 100,
        'status' => true,
    ]);
    $package = ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Pack of 5',
        'quantity' => 5,
        'price' => 350,
        'status' => true,
    ]);

    productPackageCheckoutBranchWithStock($product, 20);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 2,
            'product_package_id' => $package->id,
        ])
        ->assertRedirect();

    $item = CartItem::query()->firstOrFail();

    expect($item->product_package_id)->toBe($package->id)
        ->and((float) $item->price_snapshot)->toBe(350.0)
        ->and($item->quantity)->toBe(2);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), productPackageCheckoutPayload())
        ->assertRedirect();

    $orderItem = OrderItem::query()->firstOrFail();
    $inventory = Inventory::query()
        ->where('product_id', $product->id)
        ->whereNull('variant_id')
        ->firstOrFail();

    expect($orderItem->product_package_id)->toBe($package->id)
        ->and($orderItem->package_label_snapshot)->toBe('Pack of 5')
        ->and($orderItem->package_quantity_snapshot)->toBe(5)
        ->and($orderItem->quantity)->toBe(2)
        ->and((float) $orderItem->unit_price)->toBe(350.0)
        ->and((float) $orderItem->subtotal)->toBe(700.0)
        ->and($orderItem->local_reserved_quantity)->toBe(10)
        ->and($inventory->reserved_quantity)->toBe(10);
});

test('disabling a package after cart add blocks checkout', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'slug' => 'pkg-disable-later',
        'selling_price' => 100,
        'status' => true,
    ]);
    $package = ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Pack of 2',
        'quantity' => 2,
        'price' => 160,
        'status' => true,
    ]);

    productPackageCheckoutBranchWithStock($product, 20);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
            'product_package_id' => $package->id,
        ]);

    $package->forceFill(['status' => false])->save();

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), productPackageCheckoutPayload())
        ->assertSessionHasErrors('cart');

    expect(OrderItem::query()->count())->toBe(0);
});

test('regular purchase still works when all packages are disabled', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'slug' => 'pkg-all-disabled',
        'selling_price' => 55,
        'status' => true,
    ]);
    ProductPackage::factory()->disabled()->create([
        'product_id' => $product->id,
        'label' => 'Hidden Pack',
        'quantity' => 5,
        'price' => 200,
    ]);

    productPackageCheckoutBranchWithStock($product, 10);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ])
        ->assertRedirect();

    $item = CartItem::query()->firstOrFail();

    expect($item->product_package_id)->toBeNull()
        ->and((float) $item->price_snapshot)->toBe(55.0);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), productPackageCheckoutPayload())
        ->assertRedirect();

    $orderItem = OrderItem::query()->firstOrFail();

    expect($orderItem->product_package_id)->toBeNull()
        ->and($orderItem->quantity)->toBe(1)
        ->and((float) $orderItem->unit_price)->toBe(55.0)
        ->and($orderItem->local_reserved_quantity)->toBe(1);
});

test('platform product create can include nested packages', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Create With Packages',
            'slug' => 'create-with-packages',
            'sku' => 'SKU-CWP',
            'status' => true,
            'packages_enabled' => true,
            'packages' => [
                [
                    'label' => 'Pack of 2',
                    'quantity' => 2,
                    'price' => 160,
                    'status' => true,
                    'sort_order' => 1,
                ],
                [
                    'label' => 'Pack of 5',
                    'quantity' => 5,
                    'price' => 350,
                    'status' => false,
                    'sort_order' => 2,
                ],
            ],
        ])
        ->assertRedirect(route('platform.products.show', 'create-with-packages'));

    $product = Product::query()->where('slug', 'create-with-packages')->firstOrFail();

    expect($product->packages_enabled)->toBeTrue()
        ->and($product->packages()->count())->toBe(3)
        ->and($product->packages()->where('is_default', true)->count())->toBe(1)
        ->and($product->packages()->where('label', 'Pack of 2')->where('status', true)->exists())->toBeTrue()
        ->and($product->packages()->where('label', 'Pack of 5')->where('status', false)->exists())->toBeTrue();
});

test('platform product create without packages creates the default single item package from the selling price', function () {
    config(['catalog.show_branch_pricing' => false]);
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Create Without Packages',
            'slug' => 'create-without-packages',
            'sku' => 'SKU-CWOP',
            'status' => true,
            'selling_price' => 45.5,
        ])
        ->assertRedirect(route('platform.products.show', 'create-without-packages'));

    $product = Product::query()->where('slug', 'create-without-packages')->firstOrFail();
    $default = $product->packages()->sole();

    expect($product->packages_enabled)->toBeTrue()
        ->and($default->is_default)->toBeTrue()
        ->and($default->label)->toBe('1 Item')
        ->and($default->quantity)->toBe(1)
        ->and($default->price)->toBe('45.50')
        ->and($default->status)->toBeTrue();
});

test('platform product create keeps the edited price, status and position of the default package', function () {
    config(['catalog.show_branch_pricing' => false]);
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Create With Edited Default',
            'slug' => 'create-with-edited-default',
            'sku' => 'SKU-CWED',
            'status' => true,
            'selling_price' => 100,
            'packages' => [
                [
                    'label' => 'Pack of 5',
                    'quantity' => 5,
                    'price' => 400,
                    'status' => true,
                    'sort_order' => 0,
                ],
                [
                    'label' => 'Renamed By Client',
                    'quantity' => 9,
                    'price' => 90,
                    'status' => false,
                    'is_default' => true,
                    'sort_order' => 1,
                ],
            ],
        ])
        ->assertRedirect(route('platform.products.show', 'create-with-edited-default'));

    $product = Product::query()->where('slug', 'create-with-edited-default')->firstOrFail();
    $ordered = $product->packages()->ordered()->get();

    expect($ordered->pluck('label')->all())->toBe(['Pack of 5', 'Renamed By Client'])
        ->and($ordered[1]->is_default)->toBeTrue()
        ->and($ordered[1]->quantity)->toBe(1)
        ->and($ordered[1]->price)->toBe('90.00')
        ->and($ordered[1]->status)->toBeFalse();
});

test('platform product create requires a price for non-default packages', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Create Missing Price',
            'slug' => 'create-missing-price',
            'sku' => 'SKU-CMP',
            'status' => true,
            'packages' => [
                [
                    'label' => 'Pack of 5',
                    'quantity' => 5,
                    'price' => null,
                ],
            ],
        ])
        ->assertSessionHasErrors('packages.0.price');

    expect(Product::query()->where('slug', 'create-missing-price')->exists())->toBeFalse();
});

test('default package cannot be deleted', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();
    $default = ProductPackage::factory()->default()->create(['product_id' => $product->id]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.products.edit', $product))
        ->delete(route('platform.products.packages.destroy', [$product, $default]))
        ->assertRedirect(route('platform.products.edit', $product));

    expect($default->fresh()->trashed())->toBeFalse();
});

test('default package accepts label, price and status but locks quantity at 1', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();
    $default = ProductPackage::factory()->default()->create([
        'product_id' => $product->id,
        'price' => 50,
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.packages.update', [$product, $default]), [
            'label' => 'Renamed',
            'quantity' => 4,
            'price' => 62.5,
            'status' => false,
        ])
        ->assertRedirect();

    expect($default->fresh())
        ->label->toBe('Renamed')
        ->quantity->toBe(1)
        ->price->toBe('62.50')
        ->status->toBeFalse()
        ->is_default->toBeTrue();
});

test('added package is appended after the existing packages', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();
    ProductPackage::factory()->default()->create(['product_id' => $product->id, 'sort_order' => 0]);
    ProductPackage::factory()->create(['product_id' => $product->id, 'label' => 'Pack of 2', 'sort_order' => 4]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.packages.store', $product), [
            'label' => 'Pack of 9',
            'quantity' => 9,
            'price' => 500,
        ])
        ->assertRedirect();

    expect($product->packages()->ordered()->pluck('label')->all())
        ->toBe(['1 Item', 'Pack of 2', 'Pack of 9']);
});

test('platform admin can move packages up and down', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();
    $first = ProductPackage::factory()->default()->create(['product_id' => $product->id, 'sort_order' => 0]);
    $second = ProductPackage::factory()->create(['product_id' => $product->id, 'label' => 'Pack of 2', 'sort_order' => 0]);
    $third = ProductPackage::factory()->create(['product_id' => $product->id, 'label' => 'Pack of 5', 'sort_order' => 7]);

    $orderedIds = fn (): array => $product->packages()->ordered()->pluck('id')->all();

    $this->actingAs($admin, 'platform')
        ->patch(route('platform.products.packages.move', [$product, $third]), ['direction' => 'up'])
        ->assertRedirect();

    expect($orderedIds())->toBe([$first->id, $third->id, $second->id]);

    $this->actingAs($admin, 'platform')
        ->patch(route('platform.products.packages.move', [$product, $first]), ['direction' => 'down'])
        ->assertRedirect();

    expect($orderedIds())->toBe([$third->id, $first->id, $second->id]);

    $this->actingAs($admin, 'platform')
        ->patch(route('platform.products.packages.move', [$product, $third]), ['direction' => 'up'])
        ->assertRedirect();

    expect($orderedIds())->toBe([$third->id, $first->id, $second->id]);
});

test('moving a package rejects an unknown direction and a package of another product', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();
    $package = ProductPackage::factory()->create(['product_id' => $product->id]);
    $foreign = ProductPackage::factory()->create();

    $this->actingAs($admin, 'platform')
        ->patch(route('platform.products.packages.move', [$product, $package]), ['direction' => 'sideways'])
        ->assertSessionHasErrors('direction');

    $this->actingAs($admin, 'platform')
        ->patch(route('platform.products.packages.move', [$product, $foreign]), ['direction' => 'up'])
        ->assertNotFound();
});

test('platform admin can toggle product packages_enabled without changing package statuses', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create([
        'slug' => 'pkg-product-toggle',
        'selling_price' => 100,
        'status' => true,
    ]);
    $enabled = ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Keep Enabled',
        'status' => true,
    ]);
    $disabled = ProductPackage::factory()->disabled()->create([
        'product_id' => $product->id,
        'label' => 'Keep Disabled',
        'status' => false,
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.packages-enabled', $product), [
            'packages_enabled' => false,
        ])
        ->assertRedirect();

    expect($product->fresh()->packages_enabled)->toBeFalse()
        ->and($enabled->fresh()->status)->toBeTrue()
        ->and($disabled->fresh()->status)->toBeFalse();

    expect(StorefrontCatalog::productDetail($product->fresh())['packages'])->toBe([]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.packages-enabled', $product), [
            'packages_enabled' => true,
        ])
        ->assertRedirect();

    expect($product->fresh()->packages_enabled)->toBeTrue()
        ->and($enabled->fresh()->status)->toBeTrue()
        ->and($disabled->fresh()->status)->toBeFalse();

    $restored = StorefrontCatalog::productDetail($product->fresh());

    expect($restored['packages'])->toHaveCount(1)
        ->and($restored['packages'][0]['id'])->toBe($enabled->id);
});

test('product packages_enabled false hides packages and allows regular cart add', function () {
    $user = User::factory()->create();
    $product = Product::factory()->packagesDisabled()->create([
        'slug' => 'pkg-product-off',
        'selling_price' => 75,
        'status' => true,
    ]);
    $package = ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Hidden By Product Flag',
        'quantity' => 2,
        'price' => 120,
        'status' => true,
    ]);

    productPackageCheckoutBranchWithStock($product, 10);

    expect(StorefrontCatalog::productDetail($product->fresh())['packages'])->toBe([]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
            'product_package_id' => $package->id,
        ])
        ->assertSessionHasErrors('product');

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ])
        ->assertRedirect();

    $item = CartItem::query()->firstOrFail();

    expect($item->product_package_id)->toBeNull()
        ->and((float) $item->price_snapshot)->toBe(75.0);
});

test('disabling product packages after cart add blocks checkout', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'slug' => 'pkg-product-disable-later',
        'selling_price' => 100,
        'status' => true,
    ]);
    $package = ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Pack of 2',
        'quantity' => 2,
        'price' => 160,
        'status' => true,
    ]);

    productPackageCheckoutBranchWithStock($product, 20);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
            'product_package_id' => $package->id,
        ]);

    $product->forceFill(['packages_enabled' => false])->save();

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), productPackageCheckoutPayload())
        ->assertSessionHasErrors('cart');

    expect(OrderItem::query()->count())->toBe(0)
        ->and($package->fresh()->status)->toBeTrue();
});
