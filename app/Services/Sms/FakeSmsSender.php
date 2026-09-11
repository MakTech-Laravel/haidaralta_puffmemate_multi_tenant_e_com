<?php

namespace App\Services\Sms;

use App\Contracts\SendsSms;

class FakeSmsSender implements SendsSms
{
    /**
     * @var list<array{to: string, message: string}>
     */
    public array $messages = [];

    public ?string $lastCode = null;

    public function send(string $toE164, string $message): void
    {
        $this->messages[] = [
            'to' => $toE164,
            'message' => $message,
        ];

        if (preg_match('/\b(\d{6})\b/', $message, $matches) === 1) {
            $this->lastCode = $matches[1];
        }
    }

    public function lastCode(): ?string
    {
        return $this->lastCode;
    }

    public function reset(): void
    {
        $this->messages = [];
        $this->lastCode = null;
    }
}
