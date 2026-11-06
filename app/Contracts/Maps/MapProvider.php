<?php

namespace App\Contracts\Maps;

use App\Enums\MapCapability;

interface MapProvider
{
    /**
     * Registry slug, e.g. "google".
     */
    public function key(): string;

    public function label(): string;

    /**
     * Capabilities usable right now (depends on which credentials are present).
     *
     * @return list<MapCapability>
     */
    public function capabilities(): array;

    public function supports(MapCapability $capability): bool;

    public function isConfigured(): bool;

    /**
     * Browser-safe configuration for the map renderer. Never contains the server key.
     *
     * @return array<string, mixed>
     */
    public function frontendConfig(): array;

    /**
     * Makes one cheap request to verify the credentials.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(): array;
}
