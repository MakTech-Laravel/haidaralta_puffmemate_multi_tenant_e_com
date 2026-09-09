<?php

namespace App\Jobs;

use App\Services\Fcm\FcmPushPayload;
use App\Services\Fcm\FcmPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendFcmPushJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<int>  $userIds
     * @param  list<string>  $tokens
     * @param  array<string, string>  $data
     */
    public function __construct(
        public array $userIds = [],
        public array $tokens = [],
        public string $title = '',
        public string $body = '',
        public array $data = [],
        public ?string $imageUrl = null,
    ) {}

    public function handle(FcmPushService $service): void
    {
        $payload = new FcmPushPayload(
            title: $this->title,
            body: $this->body,
            data: $this->data,
            imageUrl: $this->imageUrl,
        );

        if ($this->tokens !== []) {
            $service->sendToTokens($this->tokens, $payload);

            return;
        }

        if ($this->userIds !== []) {
            $service->sendToUserIds($this->userIds, $payload);
        }
    }
}
