<?php

namespace App\Services\Announcements;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementSendMode;
use App\Enums\AnnouncementType;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnouncementService
{
    public function __construct(
        private AnnouncementRecipientResolver $recipients,
    ) {}

    /**
     * @param  list<AnnouncementAudience>  $audiences
     * @param  list<array{id: int, audience: AnnouncementAudience}>  $people
     * @param  array{link_label?: string|null, link_url?: string|null, via_phone?: bool, via_email?: bool, via_push?: bool}  $options
     */
    public function send(
        User $actor,
        AnnouncementSendMode $mode,
        array $audiences,
        array $people,
        AnnouncementType $type,
        string $icon,
        string $title,
        string $message,
        array $options = [],
    ): Announcement {
        $userIds = $this->recipients->resolve($mode, $audiences, $people);

        if ($userIds === []) {
            throw ValidationException::withMessages([
                'audiences' => 'No active recipients match this announcement.',
            ]);
        }

        $linkLabel = $options['link_label'] ?? null;
        $linkUrl = $options['link_url'] ?? null;

        return DB::transaction(function () use ($actor, $mode, $audiences, $userIds, $type, $icon, $title, $message, $options, $linkLabel, $linkUrl): Announcement {
            $announcement = Announcement::query()->create([
                'send_mode' => $mode,
                'audiences' => array_map(
                    fn (AnnouncementAudience $audience): string => $audience->value,
                    $audiences,
                ),
                'type' => $type,
                'icon' => $icon,
                'title' => $title,
                'message' => $message,
                'link_label' => $linkLabel,
                'link_url' => $linkUrl,
                'via_phone' => (bool) ($options['via_phone'] ?? false),
                'via_email' => (bool) ($options['via_email'] ?? false),
                'via_push' => (bool) ($options['via_push'] ?? false),
                'via_system' => true,
                'created_by' => $actor->id,
                'recipient_count' => 0,
            ]);

            $notification = notify_users(
                userIds: $userIds,
                type: $type->toNotificationType(),
                title: $title,
                message: $message,
                data: [
                    'announcement_id' => $announcement->id,
                    'icon' => $icon,
                    'link_label' => $linkLabel,
                    'link_url' => $linkUrl,
                ],
                createdBy: $actor,
            );

            $announcement->update([
                'notification_id' => $notification?->id,
                'recipient_count' => count($userIds),
                'sent_at' => now(),
            ]);

            return $announcement->refresh();
        });
    }
}
