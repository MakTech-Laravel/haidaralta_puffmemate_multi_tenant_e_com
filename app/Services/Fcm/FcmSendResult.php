<?php

namespace App\Services\Fcm;

readonly class FcmSendResult
{
    /**
     * @param  array<string, string>  $errors  token => message
     */
    public function __construct(
        public int $sent = 0,
        public int $failed = 0,
        public int $invalidTokensRemoved = 0,
        public array $errors = [],
    ) {}

    /**
     * @param  array<string, string>  $errors
     */
    public function merge(self $other): self
    {
        return new self(
            sent: $this->sent + $other->sent,
            failed: $this->failed + $other->failed,
            invalidTokensRemoved: $this->invalidTokensRemoved + $other->invalidTokensRemoved,
            errors: [...$this->errors, ...$other->errors],
        );
    }

    /**
     * @return array{sent: int, failed: int, invalid_tokens_removed: int, errors: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'sent' => $this->sent,
            'failed' => $this->failed,
            'invalid_tokens_removed' => $this->invalidTokensRemoved,
            'errors' => $this->errors,
        ];
    }
}
