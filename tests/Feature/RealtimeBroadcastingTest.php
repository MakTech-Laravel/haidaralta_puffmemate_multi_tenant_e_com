<?php

use App\Events\RealtimePrivateTestEvent;
use App\Events\ReverbPingEvent;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use App\Support\PortalAuth;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

function createRealtimePlatformAdmin(): User
{
    test()->seed(RolesAndPermissionsSeeder::class);
    test()->seed(BranchSeeder::class);
    test()->seed(UserSeeder::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

    return User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
}

function useReverbBroadcasterForAuth(): void
{
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-id',
        'broadcasting.connections.reverb.options.host' => 'localhost',
        'broadcasting.connections.reverb.options.port' => 443,
        'broadcasting.connections.reverb.options.scheme' => 'https',
        'broadcasting.connections.reverb.options.useTLS' => true,
    ]);

    Broadcast::channel(
        'App.Models.User.{id}',
        function (User $user, int $id): bool {
            return (int) $user->id === $id;
        },
        ['guards' => [...PortalAuth::PORTAL_GUARDS, 'web']],
    );
}

test('guest cannot authorize private user channel', function () {
    useReverbBroadcasterForAuth();

    $user = User::factory()->create();

    $this->postJson('/broadcasting/auth', [
        'channel_name' => 'private-App.Models.User.'.$user->id,
        'socket_id' => '1234.5678',
    ])->assertForbidden();
});

test('platform user can authorize their own private channel', function () {
    useReverbBroadcasterForAuth();

    $admin = createRealtimePlatformAdmin();

    $this->actingAs($admin, 'platform')
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-App.Models.User.'.$admin->id,
            'socket_id' => '1234.5678',
        ])
        ->assertSuccessful();
});

test('platform user cannot authorize another users private channel', function () {
    useReverbBroadcasterForAuth();

    $admin = createRealtimePlatformAdmin();
    $other = User::factory()->create();

    $this->actingAs($admin, 'platform')
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-App.Models.User.'.$other->id,
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});

test('realtime private test event uses private user channel', function () {
    $user = User::factory()->create();
    $event = new RealtimePrivateTestEvent(
        user: $user,
        message: 'hi',
        triggeredAt: now()->toIso8601String(),
    );

    expect($event->broadcastAs())->toBe('realtime.test');
    expect($event->broadcastOn())->toHaveCount(1);
    expect($event->broadcastOn()[0])->toBeInstanceOf(PrivateChannel::class);
    expect($event->broadcastOn()[0]->name)->toBe('private-App.Models.User.'.$user->id);
});

test('cors allows configured origin on broadcasting auth options', function () {
    config([
        'cors.allowed_origins' => ['https://puffmemate.maktechlaravel.cloud'],
        'cors.supports_credentials' => true,
        'cors.paths' => ['broadcasting/auth', 'broadcasting/*', 'platform/realtime/*'],
    ]);

    $response = $this->call(
        'OPTIONS',
        '/broadcasting/auth',
        [],
        [],
        [],
        [
            'HTTP_ORIGIN' => 'https://puffmemate.maktechlaravel.cloud',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ],
    );

    $response->assertSuccessful();
    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('https://puffmemate.maktechlaravel.cloud');
    expect($response->headers->get('Access-Control-Allow-Credentials'))->toBe('true');
});

test('cors rejects unknown origin on broadcasting auth options', function () {
    config([
        'cors.allowed_origins' => ['https://puffmemate.maktechlaravel.cloud'],
        'cors.supports_credentials' => true,
        'cors.paths' => ['broadcasting/auth', 'broadcasting/*', 'platform/realtime/*'],
    ]);

    $response = $this->call(
        'OPTIONS',
        '/broadcasting/auth',
        [],
        [],
        [],
        [
            'HTTP_ORIGIN' => 'https://evil.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ],
    );

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.example');
});

test('guest cannot access realtime diagnostics', function () {
    $this->getJson('/platform/realtime/health')->assertRedirect(route('login'));
    $this->getJson('/platform/realtime/ping')->assertRedirect(route('login'));
    $this->postJson('/platform/realtime/test-notification')->assertRedirect(route('login'));
});

test('platform admin can fetch realtime health without secrets', function () {
    $admin = createRealtimePlatformAdmin();

    $response = $this->actingAs($admin, 'platform')
        ->getJson('/platform/realtime/health')
        ->assertOk()
        ->assertJsonPath('status', true);

    $data = $response->json('data');
    expect($data)->toHaveKeys(['broadcast_connection', 'host', 'port', 'scheme', 'app_key_preview']);
    expect($data)->not->toHaveKey('secret');
    expect($data)->not->toHaveKey('app_secret');
});

test('platform admin can dispatch public reverb ping', function () {
    Event::fake([ReverbPingEvent::class]);

    $admin = createRealtimePlatformAdmin();

    $this->actingAs($admin, 'platform')
        ->getJson('/platform/realtime/ping?message=Hello')
        ->assertOk()
        ->assertJsonPath('data.channel', 'reverb-ping')
        ->assertJsonPath('data.event', 'ping');

    Event::assertDispatched(ReverbPingEvent::class, function (ReverbPingEvent $event): bool {
        return $event->message === 'Hello'
            && $event->broadcastAs() === 'ping'
            && $event->broadcastOn()[0]->name === 'reverb-ping';
    });
});

test('platform admin can dispatch private test event', function () {
    Event::fake([RealtimePrivateTestEvent::class]);

    $admin = createRealtimePlatformAdmin();

    $this->actingAs($admin, 'platform')
        ->postJson('/platform/realtime/test-notification')
        ->assertOk()
        ->assertJsonPath('data.channel', 'App.Models.User.'.$admin->id)
        ->assertJsonPath('data.event', 'realtime.test');

    Event::assertDispatched(RealtimePrivateTestEvent::class);
});

test('platform admin can open realtime console page', function () {
    $admin = createRealtimePlatformAdmin();

    $this->actingAs($admin, 'platform')
        ->withoutVite()
        ->get('/platform/realtime')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('platform/realtime/index'));
});
