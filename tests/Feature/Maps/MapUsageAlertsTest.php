<?php

use App\Enums\MapCapability;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Maps\MapSettingsService;
use App\Services\Maps\MapUsageRecorder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
});

function recordGoogleRequests(int $times): void
{
    $usage = app(MapUsageRecorder::class);

    foreach (range(1, $times) as $ignored) {
        $usage->record('google', MapCapability::Geocoding);
    }
}

test('platform admins are warned once when a provider reaches 80 percent of its daily limit', function () {
    app(MapSettingsService::class)->updateProvider('google', ['server_key' => 'srv', 'daily_limit' => 10]);

    recordGoogleRequests(7);
    expect(Notification::query()->where('title', 'like', '%nearing%')->count())->toBe(0);

    recordGoogleRequests(1);
    recordGoogleRequests(1);

    $warning = Notification::query()->where('title', 'Google Maps is nearing its limit')->get();

    expect($warning)->toHaveCount(1)
        ->and($warning->first()->message)->toContain('8 of its 10 daily requests')
        ->and(UserNotification::query()->where('user_id', $this->admin->id)->count())->toBe(1);
});

test('reaching the limit sends a second alert that mentions the fallback', function () {
    app(MapSettingsService::class)->updateProvider('google', ['server_key' => 'srv', 'daily_limit' => 5]);

    recordGoogleRequests(6);

    $reached = Notification::query()->where('title', 'Google Maps limit reached')->get();

    expect($reached)->toHaveCount(1)
        ->and($reached->first()->message)->toContain('fallback provider');
});

test('a monthly limit alerts independently of the daily one', function () {
    app(MapSettingsService::class)->updateProvider('google', ['server_key' => 'srv', 'monthly_limit' => 4]);

    recordGoogleRequests(4);

    expect(Notification::query()->where('message', 'like', '%monthly%')->count())->toBeGreaterThanOrEqual(1);
});

test('providers without limits never alert', function () {
    app(MapSettingsService::class)->updateProvider('google', ['server_key' => 'srv']);

    recordGoogleRequests(50);

    expect(Notification::query()->count())->toBe(0);
});

test('the dashboard reports usage as a percentage of each limit', function () {
    app(MapSettingsService::class)->updateProvider('google', ['server_key' => 'srv', 'daily_limit' => 10, 'monthly_limit' => 100]);
    recordGoogleRequests(4);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.settings.map-provider.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('providers.2.key', 'google')
            ->where('providers.2.usage.today', 4)
            ->where('providers.2.usage.today_percent', 40)
            ->where('providers.2.usage.month_percent', 4));
});
