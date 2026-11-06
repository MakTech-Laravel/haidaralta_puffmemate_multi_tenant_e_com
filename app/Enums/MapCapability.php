<?php

namespace App\Enums;

enum MapCapability: string
{
    case Display = 'display';
    case Geocoding = 'geocoding';
    case Routing = 'routing';

    /**
     * Server-side capabilities make billable HTTP calls from the backend; display is a browser-side load.
     */
    public function isServerBilled(): bool
    {
        return $this !== self::Display;
    }

    public function label(): string
    {
        return match ($this) {
            self::Display => 'Map display',
            self::Geocoding => 'Address search (geocoding)',
            self::Routing => 'Routing & directions',
        };
    }
}
