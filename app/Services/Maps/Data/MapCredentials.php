<?php

namespace App\Services\Maps\Data;

final readonly class MapCredentials
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public ?string $publicKey = null,
        public ?string $serverKey = null,
        public array $options = [],
    ) {}

    public function option(string $key, mixed $default = null): mixed
    {
        $value = $this->options[$key] ?? null;

        return filled($value) ? $value : $default;
    }
}
