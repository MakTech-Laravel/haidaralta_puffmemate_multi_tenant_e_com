<?php

namespace App\Enums;

enum ShiftOwnerType: string
{
    case Platform = 'platform';
    case Branch = 'branch';

    public function label(): string
    {
        return match ($this) {
            self::Platform => 'Platform',
            self::Branch => 'Branch',
        };
    }
}
