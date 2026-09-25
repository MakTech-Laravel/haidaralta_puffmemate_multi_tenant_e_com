<?php

namespace App\Jobs;

use App\Events\UserNotificationCreated;
use App\Models\UserNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FanOutNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $notificationId) {}

    public function handle(): void
    {
        UserNotification::query()
            ->where('notification_id', $this->notificationId)
            ->with('notification')
            ->orderBy('id')
            ->chunkById(100, function ($userNotifications): void {
                foreach ($userNotifications as $userNotification) {
                    UserNotificationCreated::dispatch($userNotification);
                }
            });
    }
}
