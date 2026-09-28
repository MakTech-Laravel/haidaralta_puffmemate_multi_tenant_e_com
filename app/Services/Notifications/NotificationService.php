<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Jobs\FanOutNotificationJob;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    
    public function send(
        iterable $userIds,
        NotificationType $type,
        string $title,
        string $message,
        array $data = [],
        User|int|null $createdBy = null,
    ): ?Notification {
        $ids = $this->uniqueUserIds($userIds);

        if ($ids === []) {
            return null;
        }

        $createdById = $createdBy instanceof User
            ? $createdBy->id
            : $createdBy;

        $notification = DB::transaction(function () use ($ids, $type, $title, $message, $data, $createdById): Notification {
            $notification = Notification::query()->create([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data === [] ? null : $data,
                'created_by' => $createdById,
            ]);

            $now = now();

            UserNotification::query()->insert(
                array_map(fn (int $userId): array => [
                    'notification_id' => $notification->id,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $ids),
            );

            return $notification;
        });

        FanOutNotificationJob::dispatch($notification->id)->afterCommit();

        return $notification;
    }

    private function uniqueUserIds(iterable $userIds): array
    {
        $ids = [];

        foreach ($userIds as $userId) {
            $id = $userId instanceof User ? $userId->id : (int) $userId;

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }
}
