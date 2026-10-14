<?php

namespace App\Enums;

enum DeliveryFailureReason: string
{
    case CustomerUnavailable = 'customer_unavailable';
    case WrongAddress = 'wrong_address';
    case Refused = 'refused';
    case IdCheckFailed = 'id_check_failed';
    case Unsafe = 'unsafe';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CustomerUnavailable => 'Customer unavailable',
            self::WrongAddress => 'Wrong address',
            self::Refused => 'Customer refused delivery',
            self::IdCheckFailed => "Couldn't show valid ID / underage",
            self::Unsafe => 'Unsafe to deliver',
            self::Other => 'Other',
        };
    }
}
