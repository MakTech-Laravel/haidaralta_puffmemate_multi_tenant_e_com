<?php

namespace Database\Seeders;

use App\Models\DeliveryShift;
use App\Models\User;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;

class DeliveryShiftSeeder extends Seeder
{
    use ReportsSeedProgress;

    public function run(): void
    {
        $actorId = User::query()
            ->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)
            ->value('id');

        $windows = [
            [
                'name' => 'Morning Delivery',
                'start_time' => '09:00',
                'end_time' => '13:00',
            ],
            [
                'name' => 'Afternoon Delivery',
                'start_time' => '13:00',
                'end_time' => '17:00',
            ],
            [
                'name' => 'Evening Delivery',
                'start_time' => '17:00',
                'end_time' => '21:00',
            ],
        ];

        $this->withSeedProgress(
            'Delivery shifts',
            $windows,
            function (array $window) use ($actorId): void {
                DeliveryShift::query()->updateOrCreate(
                    ['name' => $window['name']],
                    [
                        'start_time' => $window['start_time'],
                        'end_time' => $window['end_time'],
                        'is_active' => true,
                        'created_by' => $actorId,
                    ],
                );
            },
            '3 delivery shifts ready.',
        );
    }
}
