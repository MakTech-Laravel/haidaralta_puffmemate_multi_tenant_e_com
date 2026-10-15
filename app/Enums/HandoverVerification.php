<?php

namespace App\Enums;

enum HandoverVerification: string
{
    case PhoneCall = 'phone_call';
    case PhotoId = 'photo_id';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PhoneCall => 'Called the customer',
            self::PhotoId => 'Checked photo ID',
            self::Other => 'Other',
        };
    }
}
