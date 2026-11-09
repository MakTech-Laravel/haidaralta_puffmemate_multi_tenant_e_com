<?php

namespace App\Services\Maps\Exceptions;

use App\Enums\MapCapability;
use RuntimeException;

class MapProviderUnavailableException extends RuntimeException
{
    public static function forCapability(MapCapability $capability, ?string $reason = null): self
    {
        $message = "No map provider is available for {$capability->value}.";

        if (filled($reason)) {
            $message .= " {$reason}";
        }

        return new self($message);
    }
}
