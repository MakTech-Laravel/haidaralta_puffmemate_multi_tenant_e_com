<?php

use App\Enums\UserType;
use App\Models\FcmDeviceToken;
use App\Models\User;
use App\Services\Fcm\FcmPushPayload;
use App\Services\Fcm\FcmPushService;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;

beforeEach(function () {
    $this->messaging = Mockery::mock(Messaging::class);
    $this->app->instance(Messaging::class, $this->messaging);
    $this->service = app(FcmPushService::class);
});

test('sendToUser sends to all of the users tokens', function () {
    $user = User::factory()->create(['user_type' => UserType::PLATFORM]);
    FcmDeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'token-one-abcdefghijklmnopqrstuvwxyz']);
    FcmDeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'token-two-abcdefghijklmnopqrstuvwxyz']);

    $this->messaging->shouldReceive('send')->twice()->andReturn(['name' => 'projects/x/messages/1']);

    $result = $this->service->sendToUser($user, new FcmPushPayload('Hi', 'Body'));

    expect($result->sent)->toBe(2)
        ->and($result->failed)->toBe(0);
});

test('sendToUsers aggregates tokens across users', function () {
    $one = User::factory()->create(['user_type' => UserType::PLATFORM]);
    $two = User::factory()->create(['user_type' => UserType::PLATFORM]);

    FcmDeviceToken::factory()->create([
        'user_id' => $one->id,
        'token' => 'user-one-token-abcdefghijklmnopqrstuvwxyz',
    ]);
    FcmDeviceToken::factory()->create([
        'user_id' => $two->id,
        'token' => 'user-two-token-abcdefghijklmnopqrstuvwxyz',
    ]);

    $this->messaging->shouldReceive('send')->twice()->andReturn(['name' => 'projects/x/messages/1']);

    $result = $this->service->sendToUserIds([$one->id, $two->id], new FcmPushPayload('Hi', 'Body'));

    expect($result->sent)->toBe(2);
});

test('sendToTokens dedupes identical tokens', function () {
    $token = 'dedupe-token-abcdefghijklmnopqrstuvwxyz0123';

    $this->messaging->shouldReceive('send')->once()->andReturn(['name' => 'ok']);

    $result = $this->service->sendToTokens([$token, $token, " {$token} "], new FcmPushPayload('Hi', 'Body'));

    expect($result->sent)->toBe(1);
});

test('invalid tokens are removed and other tokens still send', function () {
    $user = User::factory()->create(['user_type' => UserType::PLATFORM]);
    FcmDeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'bad-token-abcdefghijklmnopqrstuvwxyz']);
    FcmDeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'good-token-abcdefghijklmnopqrstuvwxyz']);

    $this->messaging
        ->shouldReceive('send')
        ->twice()
        ->andReturnUsing(function (CloudMessage $message): array {
            $token = (string) ($message->jsonSerialize()['token'] ?? '');

            if (str_starts_with($token, 'bad-')) {
                throw NotFound::becauseTokenNotFound($token);
            }

            return ['name' => 'ok'];
        });

    $result = $this->service->sendToUser($user, new FcmPushPayload('Hi', 'Body'));

    expect($result->sent)->toBe(1)
        ->and($result->failed)->toBe(1)
        ->and($result->invalidTokensRemoved)->toBe(1)
        ->and(FcmDeviceToken::query()->where('token', 'like', 'bad-%')->exists())->toBeFalse()
        ->and(FcmDeviceToken::query()->where('token', 'like', 'good-%')->exists())->toBeTrue();
});

test('fcm_notify_users helper uses the service', function () {
    $user = User::factory()->create(['user_type' => UserType::PLATFORM]);
    FcmDeviceToken::factory()->create([
        'user_id' => $user->id,
        'token' => 'helper-token-abcdefghijklmnopqrstuvwxyz',
    ]);

    $this->messaging->shouldReceive('send')->once()->andReturn(['name' => 'ok']);

    $result = fcm_notify_users([$user->id], 'Title', 'Body');

    expect($result->sent)->toBe(1);
});
