<?php

namespace App\Jobs;

use App\Mail\AnnouncementMail;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAnnouncementEmailJob implements ShouldQueue
{
    use Queueable;

    public int $backoff = 10;

    public function __construct(
        public int $userId,
        public string $title = '',
        public string $message = '',
        public ?string $linkLabel = null,
        public ?string $linkUrl = null,
    ) {}

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new RateLimited('announcement-mail')];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(6);
    }

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

    public function failed(\Throwable $exception): void
    {
        Log::warning(
            "Announcement mail failed for user #{$this->userId}: {$exception->getMessage()}"
        );
    }
}
