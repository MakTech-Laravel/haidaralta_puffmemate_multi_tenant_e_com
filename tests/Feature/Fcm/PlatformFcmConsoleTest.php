<?php

use App\Enums\PermissionEnum;
use App\Enums\UserType;
use App\Models\FcmDeviceToken;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Kreait\Firebase\Contract\Messaging;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->messaging = Mockery::mock(Messaging::class);
    $this->app->instance(Messaging::class, $this->messaging);
});

function platformUserWithDashboard(): User
{
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->givePermissionTo(PermissionEnum::VIEW_DASHBOARD->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

test('guests are redirected from the fcm console', function () {
    $this->get(route('platform.fcm.index'))->assertRedirect(route('login'));
});

test('platform users can view the fcm console', function () {
    $user = platformUserWithDashboard();

    $this->actingAs($user, 'platform')
        ->get(route('platform.fcm.index'))
        ->assertOk();
});

test('platform users without view-dashboard cannot open the fcm console', function () {
    $user = User::factory()->create(['user_type' => UserType::PLATFORM]);

    $this->actingAs($user, 'platform')
        ->get(route('platform.fcm.index'))
        ->assertForbidden();
});

test('platform user can register and unregister an fcm token', function () {
    $user = platformUserWithDashboard();
    $token = 'register-token-abcdefghijklmnopqrstuvwxyz0123456789';

    $this->actingAs($user, 'platform')
        ->postJson(route('fcm.register'), [
            'token' => $token,
            'platform' => 'web',
        ])
        ->assertOk()
        ->assertJsonPath('status', true);

    expect(FcmDeviceToken::query()->where('token', $token)->where('user_id', $user->id)->exists())->toBeTrue();

    $this->actingAs($user, 'platform')
        ->deleteJson(route('fcm.unregister'), ['token' => $token])
        ->assertOk();

    expect(FcmDeviceToken::query()->where('token', $token)->exists())->toBeFalse();
});

test('register upsert reassigns token to a new user', function () {
    $first = platformUserWithDashboard();
    $second = platformUserWithDashboard();
    $token = 'reassign-token-abcdefghijklmnopqrstuvwxyz0123456789';

    FcmDeviceToken::factory()->create(['user_id' => $first->id, 'token' => $token]);

    $this->actingAs($second, 'platform')
        ->postJson(route('fcm.register'), ['token' => $token])
        ->assertOk();

    expect(FcmDeviceToken::query()->where('token', $token)->value('user_id'))->toBe($second->id);
});

test('fcm test by token validates and sends', function () {
    $user = platformUserWithDashboard();
    $token = 'send-token-abcdefghijklmnopqrstuvwxyz0123456789';

    $this->messaging->shouldReceive('send')->once()->andReturn(['name' => 'ok']);

    $this->actingAs($user, 'platform')
        ->postJson(route('platform.fcm.test'), [
            'title' => 'Hello',
            'body' => 'World',
            'mode' => 'token',
            'token' => $token,
        ])
        ->assertOk()
        ->assertJsonPath('data.sent', 1);
});

test('fcm test by users sends to registered tokens', function () {
    $admin = platformUserWithDashboard();
    $target = platformUserWithDashboard();
    FcmDeviceToken::factory()->create([
        'user_id' => $target->id,
        'token' => 'user-send-token-abcdefghijklmnopqrstuvwxyz',
    ]);

    $this->messaging->shouldReceive('send')->once()->andReturn(['name' => 'ok']);

    $this->actingAs($admin, 'platform')
        ->postJson(route('platform.fcm.test'), [
            'title' => 'Hello',
            'body' => 'World',
            'mode' => 'user',
            'user_ids' => [$target->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.sent', 1);
});

test('fcm test requires recipients for the selected mode', function () {
    $user = platformUserWithDashboard();

    $this->actingAs($user, 'platform')
        ->postJson(route('platform.fcm.test'), [
            'title' => 'Hello',
            'body' => 'World',
            'mode' => 'token',
        ])
        ->assertStatus(422);
});
