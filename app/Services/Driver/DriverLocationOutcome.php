<?php

namespace App\Services\Driver;

final readonly class DriverLocationOutcome
{
    /**
     * @param  bool  $tracking  Whether the driver should be sharing location right now
     * @param  bool  $accepted  Whether this reading was stored as the driver's position
     * @param  string|null  $reason  Why a reading was ignored: inaccurate, too_old, out_of_order, jump
     */
    public function __construct(
        public bool $tracking,
        public bool $accepted = false,
        public bool $broadcast = false,
        public ?string $reason = null,
    ) {}

    public static function notTracking(): self
    {
        return new self(tracking: false);
    }

    public static function ignored(string $reason): self
    {
        return new self(tracking: true, reason: $reason);
    }
}
