<?php

use App\Models\DeliveryShift;
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

test('platform can manage delivery shifts', function () {
    $user = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($user, 'platform')
        ->post(route('platform.delivery-shifts.store'), [
            'name' => 'Morning Delivery',
            'start_time' => '09:00',
            'end_time' => '13:00',
            'is_active' => true,
        ])
        ->assertRedirect(route('platform.delivery-shifts.index'));

    $shift = DeliveryShift::query()->where('name', 'Morning Delivery')->firstOrFail();

    expect($shift->start_time->format('H:i'))->toBe('09:00')
        ->and($shift->end_time->format('H:i'))->toBe('13:00')
        ->and($shift->is_active)->toBeTrue()
        ->and($shift->created_by)->toBe($user->id);

    $this->actingAs($user, 'platform')
        ->get(route('platform.delivery-shifts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/delivery-shifts/index')
            ->has('shifts', 1)
            ->where('shifts.0.name', 'Morning Delivery'));

    $this->actingAs($user, 'platform')
        ->get(route('platform.delivery-shifts.show', $shift))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/delivery-shifts/show')
            ->where('shift.name', 'Morning Delivery')
            ->where('shift.start_time', '09:00')
            ->where('shift.end_time', '13:00'));

    $this->actingAs($user, 'platform')
        ->put(route('platform.delivery-shifts.update', $shift), [
            'name' => 'Afternoon Delivery',
            'start_time' => '13:00',
            'end_time' => '18:00',
            'is_active' => false,
        ])
        ->assertRedirect(route('platform.delivery-shifts.index'));

    $shift->refresh();

    expect($shift->name)->toBe('Afternoon Delivery')
        ->and($shift->start_time->format('H:i'))->toBe('13:00')
        ->and($shift->is_active)->toBeFalse();

    $this->actingAs($user, 'platform')
        ->delete(route('platform.delivery-shifts.destroy', $shift))
        ->assertRedirect(route('platform.delivery-shifts.index'));

    expect(DeliveryShift::query()->whereKey($shift->id)->exists())->toBeFalse();
});

test('platform delivery shift name must be unique and window must be same day', function () {
    $user = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    DeliveryShift::factory()->create([
        'name' => 'Morning Delivery',
        'start_time' => '09:00',
        'end_time' => '12:00',
    ]);

    $this->actingAs($user, 'platform')
        ->post(route('platform.delivery-shifts.store'), [
            'name' => 'Morning Delivery',
            'start_time' => '10:00',
            'end_time' => '14:00',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('name');

    $this->actingAs($user, 'platform')
        ->post(route('platform.delivery-shifts.store'), [
            'name' => 'Overnight Delivery',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('end_time');
});
