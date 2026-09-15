<?php

namespace App\Enums;

enum DriverAvailabilityStatus: string
{
    case Available = 'available';
    case Busy = 'busy';
    case Offline = 'offline';
    case OnBreak = 'on_break';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Busy => 'Busy',
            self::Offline => 'Offline',
            self::OnBreak => 'On break',
        };
    }
}
