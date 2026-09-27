<?php

namespace App\Services\Announcements;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementSendMode;
use App\Enums\AnnouncementType;
use App\Jobs\SendAnnouncementEmailJob;
use App\Jobs\SendAnnouncementPhoneJob;
use App\Jobs\SendFcmPushJob;
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
        $viaPush = (bool) ($options['via_push'] ?? false);
        $viaPhone = (bool) ($options['via_phone'] ?? false);
        $viaEmail = (bool) ($options['via_email'] ?? false);

        return DB::transaction(function () use ($actor, $mode, $audiences, $userIds, $type, $icon, $title, $message, $options, $linkLabel, $linkUrl, $viaPush, $viaPhone, $viaEmail): Announcement {
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

            if ($viaPush) {
                SendFcmPushJob::dispatch(
                    userIds: $userIds,
                    title: $title,
                    body: $message,
                    data: [
                        'announcement_id' => (string) $announcement->id,
                        'url' => filled($linkUrl) ? (string) $linkUrl : url('/'),
                        'link_label' => (string) ($linkLabel ?? ''),
                    ],
                )->afterCommit();
            }

            if ($viaPhone) {
                SendAnnouncementPhoneJob::dispatch(
                    userIds: $userIds,
                    title: $title,
                    message: $message,
                    linkUrl: $linkUrl,
                )->afterCommit();
            }

            if ($viaEmail) {
                $emailUserIds = User::query()
                    ->whereIn('id', $userIds)
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->orderBy('id')
                    ->pluck('id');

                foreach ($emailUserIds as $userId) {
                    SendAnnouncementEmailJob::dispatch(
                        userId: (int) $userId,
                        title: $title,
                        message: $message,
                        linkLabel: $linkLabel,
                        linkUrl: $linkUrl,
                    )->afterCommit();
                }
            }

            return $announcement->refresh();
        });
    }
}
