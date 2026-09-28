<?php

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\NotificationService;

if (! function_exists('notify_users')) {
    /**
     * @param  iterable<int, int|string|User>  $userIds
     * @param  array<string, mixed>  $data
     */
    function notify_users(
        iterable $userIds,
        NotificationType $type,
        string $title,
        string $message,
        array $data = [],
        User|int|null $createdBy = null,
    ): ?Notification {
        return app(NotificationService::class)->send(
            $userIds,
            $type,
            $title,
            $message,
            $data,
            $createdBy,
        );
    }
}
