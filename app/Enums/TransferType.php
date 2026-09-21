<?php

namespace App\Enums;

enum TransferType: string
{
    case BranchChange = 'branch_change';
    case RoleChange = 'role_change';

    public function label(): string
    {
        return match ($this) {
            self::BranchChange => 'Branch change',
            self::RoleChange => 'Role change',
        };
    }
}
