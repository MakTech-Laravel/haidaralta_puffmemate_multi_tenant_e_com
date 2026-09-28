<?php

namespace App\Enums;

enum AnnouncementAudience: string
{
    case Customer = 'customer';
    case Driver = 'driver';
    case Branch = 'branch';
    case Platform = 'platform';

    public function userType(): UserType
    {
        return match ($this) {
            self::Customer => UserType::CUSTOMER,
            self::Driver => UserType::DRIVER,
            self::Branch => UserType::BRANCH,
            self::Platform => UserType::PLATFORM,
        };
    }
}
