<?php

namespace App\Enums;

enum DeliveryFailureReason: string
{
    case CustomerUnavailable = 'customer_unavailable';
    case WrongAddress = 'wrong_address';
    case Refused = 'refused';
    case UnableToComplete = 'unable_to_complete';

    public function label(): string
    {
        return match ($this) {
            self::CustomerUnavailable => 'Customer unavailable',
            self::WrongAddress => 'Wrong address',
            self::Refused => 'Customer refused delivery',
            self::UnableToComplete => 'Driver cannot complete delivery',
        };
    }
}
