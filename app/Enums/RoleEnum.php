<?php

namespace App\Enums;

/**
 * Canonical role names shared across platform and branch guards.
 *
 * The enum does not encode scope — the same name exists as separate rows
 * per guard and Spatie team (platform team id 0 vs branch id).
 */
enum RoleEnum: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case STAFF = 'staff';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super admin',
            self::ADMIN => 'Admin',
            self::MANAGER => 'Manager',
            self::STAFF => 'Staff',
        };
    }
}
