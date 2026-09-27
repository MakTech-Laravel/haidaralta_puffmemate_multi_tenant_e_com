<?php

namespace App\Jobs;

use App\Mail\AnnouncementMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAnnouncementEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $title = '',
        public string $message = '',
        public ?string $linkLabel = null,
        public ?string $linkUrl = null,
    ) {}

    public function handle(): void
    {
        $user = User::query()
            ->whereKey($this->userId)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->first(['id', 'name', 'email']);

        if ($user === null) {
            return;
        }

        Mail::to($user->email)->send(new AnnouncementMail(
            title: $this->title,
            body: $this->message,
            recipientName: $user->name,
            linkLabel: $this->linkLabel,
            linkUrl: $this->linkUrl,
        ));
    }
}
