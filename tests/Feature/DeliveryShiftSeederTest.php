<?php

use App\Models\DeliveryShift;
use Database\Seeders\BranchSeeder;
use Database\Seeders\DeliveryShiftSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('the delivery shift seeder writes consecutive two-hour shifts from 07:00 to 21:00', function () {
    $this->seed(DeliveryShiftSeeder::class);

    $shifts = DeliveryShift::query()->orderBy('start_time')->get();

    expect($shifts)->toHaveCount(7)
        ->and($shifts->every(fn (DeliveryShift $shift): bool => $shift->is_active))->toBeTrue()
        ->and($shifts->every(fn (DeliveryShift $shift): bool => (int) $shift->start_time->diffInMinutes($shift->end_time) === 120))->toBeTrue()
        ->and($shifts->first()->start_time->format('H:i'))->toBe('07:00')
        ->and($shifts->last()->end_time->format('H:i'))->toBe('21:00');

    $shifts->skip(1)->values()->each(function (DeliveryShift $shift, int $index) use ($shifts): void {
        expect($shift->start_time->format('H:i'))->toBe($shifts[$index]->end_time->format('H:i'));
    });
});

test('the delivery shift seeder is safe to run twice', function () {
    $this->seed(DeliveryShiftSeeder::class);
    $this->seed(DeliveryShiftSeeder::class);

    expect(DeliveryShift::query()->count())->toBe(7)
        ->and(DeliveryShift::query()->where('name', 'Early Morning Delivery (07:00 AM - 09:00 AM)')->count())->toBe(1);
});
