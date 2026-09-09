<?php

namespace App\Services\Fcm;

/**
 * @phpstan-type FcmDataMap array<string, string>
 */
readonly class FcmPushPayload
{
    /**
     * @param  FcmDataMap  $data
     */
    public function __construct(
        public string $title,
        public string $body,
        public array $data = [],
        public ?string $imageUrl = null,
    ) {}

    /**
     * @return FcmDataMap
     */
    public function stringData(): array
    {
        $data = [];

        foreach ($this->data as $key => $value) {
            $data[(string) $key] = (string) $value;
        }

        return $data;
    }
}
