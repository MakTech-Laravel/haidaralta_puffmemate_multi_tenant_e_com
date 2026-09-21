<?php

namespace App\Enums;

enum TransferSubjectType: string
{
    case Driver = 'driver';
    case BranchStaff = 'branch_staff';

    public function label(): string
    {
        return match ($this) {
            self::Driver => 'Driver',
            self::BranchStaff => 'Branch staff',
        };
    }
}
