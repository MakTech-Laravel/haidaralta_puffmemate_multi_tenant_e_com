<?php

namespace App\Jobs;

use App\Contracts\SendsSms;
use App\Models\User;
use App\Services\Announcements\AnnouncementPhoneMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAnnouncementPhoneJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        public array $userIds = [],
        public string $title = '',
        public string $message = '',
        public ?string $linkUrl = null,
    ) {}

    public function handle(SendsSms $sms): void
    {
        if ($this->userIds === []) {
            return;
        }

        $users = User::query()
            ->whereIn('id', $this->userIds)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get(['id', 'phone']);

        $body = AnnouncementPhoneMessage::render(
            $this->title,
            $this->message,
            $this->linkUrl,
        );

        foreach ($users as $user) {
            $sms->send($user->phone, $body);
        }
    }
}
