<?php

namespace App\Enums;

enum CustomerRegistrationStatus: string
{
    case Provisional = 'provisional';
    case Registered = 'registered';

    public function label(): string
    {
        return match ($this) {
            self::Provisional => 'Provisional',
            self::Registered => 'Registered',
        };
    }
}
