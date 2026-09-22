<?php

namespace App\Enums;

enum ShiftDayKind: string
{
    case Scheduled = 'scheduled';
    case Override = 'override';
    case WeeklyOff = 'weekly_off';
    case Leave = 'leave';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Override => 'Different shift',
            self::WeeklyOff => 'Off',
            self::Leave => 'Leave',
        };
    }

    public function isOnDuty(): bool
    {
        return $this === self::Scheduled || $this === self::Override;
    }
}
