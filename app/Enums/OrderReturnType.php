<?php

namespace App\Enums;

enum OrderReturnType: string
{
    case Return = 'return';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::Return => 'Return',
            self::Refund => 'Refund',
        };
    }
}
