<?php

use App\Enums\DriverVehicleType;
use App\Enums\UserType;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot open the driver profile editor', function () {
    $this->get(route('driver.profile.edit'))
        ->assertRedirect(route('login'));
});

test('drivers open a field editor instead of the default settings profile page', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
    ]);

    DriverProfile::factory()->create([
        'user_id' => $driver->id,
        'license_number' => 'DL-EDIT-001',
        'vehicle_type' => DriverVehicleType::Motorcycle,
        'address' => '12 Crown St, Surry Hills',
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/profile/edit')
            ->where('driver.name', $driver->name)
            ->where('driver.phone', $driver->phone)
            ->where('driver.license_number', 'DL-EDIT-001')
            ->where('driver.vehicle_type', DriverVehicleType::Motorcycle->value)
            ->where('driver.address', '12 Crown St, Surry Hills')
            ->has('vehicleTypes')
            ->where('mustVerifyPhone', false));
});

test('drivers opening settings profile are sent to the field editor', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('profile.edit'))
        ->assertRedirect(route('driver.profile.edit'));
});

test('customers still see the default profile settings page', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer, 'customer')
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/profile'));
});

test('drivers can update contact license address and vehicle details', function () {
    Storage::fake('public');

    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
        'phone_verified_at' => now(),
    ]);

    DriverProfile::factory()->create([
        'user_id' => $driver->id,
        'license_number' => 'DL-OLD-001',
        'vehicle_type' => DriverVehicleType::Bicycle,
        'address' => null,
        'notes' => 'Desk only',
    ]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.profile.edit'))
        ->patch(route('driver.profile.update'), [
            'name' => 'Daniel Field',
            'phone' => '0412987654',
            'email' => 'daniel.field@puffmemate.dev',
            'license_number' => 'DL-NEW-204',
            'vehicle_type' => DriverVehicleType::Motorcycle->value,
            'address' => '418 Crown St, Surry Hills',
            'is_active' => false,
            'notes' => 'Driver should not overwrite desk notes',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'vehicle_image' => UploadedFile::fake()->image('vehicle.jpg'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('driver.profile.index'));

    $driver->refresh();
    $profile = $driver->driverProfile;

    expect($driver->name)->toBe('Daniel Field')
        ->and($driver->phone)->toBe('+61412987654')
        ->and($driver->email)->toBe('daniel.field@puffmemate.dev')
        ->and($driver->is_active)->toBeTrue()
        ->and($driver->phone_verified_at)->toBeNull()
        ->and($driver->avatar)->not->toBeNull()
        ->and($profile?->license_number)->toBe('DL-NEW-204')
        ->and($profile?->vehicle_type)->toBe(DriverVehicleType::Motorcycle)
        ->and($profile?->address)->toBe('418 Crown St, Surry Hills')
        ->and($profile?->notes)->toBe('Desk only')
        ->and($profile?->vehicle_image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($driver->avatar);
    Storage::disk('public')->assertExists($profile?->vehicle_image_path);
});

test('drivers cannot save a profile without a name or vehicle type', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
    ]);

    DriverProfile::factory()->create([
        'user_id' => $driver->id,
        'vehicle_type' => DriverVehicleType::Car,
    ]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.profile.edit'))
        ->patch(route('driver.profile.update'), [
            'name' => '',
            'phone' => $driver->phone,
            'vehicle_type' => '',
        ])
        ->assertRedirect(route('driver.profile.edit'))
        ->assertSessionHasErrors(['name', 'vehicle_type']);
});

test('drivers cannot take another account phone or license number', function () {
    $other = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'phone' => '+61413000001',
    ]);

    DriverProfile::factory()->create([
        'user_id' => $other->id,
        'license_number' => 'DL-TAKEN-9',
    ]);

    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'phone' => '+61413000002',
    ]);

    DriverProfile::factory()->create([
        'user_id' => $driver->id,
        'license_number' => 'DL-MINE-1',
        'vehicle_type' => DriverVehicleType::Car,
    ]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.profile.edit'))
        ->patch(route('driver.profile.update'), [
            'name' => $driver->name,
            'phone' => '0413000001',
            'license_number' => 'DL-TAKEN-9',
            'vehicle_type' => DriverVehicleType::Car->value,
        ])
        ->assertRedirect(route('driver.profile.edit'))
        ->assertSessionHasErrors(['phone', 'license_number']);
});
