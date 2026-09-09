<?php

use App\Models\User;
use App\Services\Fcm\FcmPushPayload;
use App\Services\Fcm\FcmPushService;
use App\Services\Fcm\FcmSendResult;

if (! function_exists('fcm_notify_user')) {
    /**
     * @param  array<string, string>  $data
     */
    function fcm_notify_user(User|int $user, string $title, string $body, array $data = []): FcmSendResult
    {
        $service = app(FcmPushService::class);
        $payload = new FcmPushPayload($title, $body, $data);

        if ($user instanceof User) {
            return $service->sendToUser($user, $payload);
        }

        return $service->sendToUserIds([$user], $payload);
    }
}

if (! function_exists('fcm_notify_users')) {
    /**
     * @param  iterable<int, User|int|string>  $usersOrIds
     * @param  array<string, string>  $data
     */
    function fcm_notify_users(iterable $usersOrIds, string $title, string $body, array $data = []): FcmSendResult
    {
        return app(FcmPushService::class)->sendToUsers(
            $usersOrIds,
            new FcmPushPayload($title, $body, $data),
        );
    }
}
