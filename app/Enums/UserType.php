<?php

namespace App\Enums;

/**
 * The portal an account belongs to.
 *
 * PLATFORM → platform owner/staff. Roles are team-scoped to reserved team id 0.
 * BRANCH   → branch staff. Roles are team-scoped to their branch.
 * DRIVER   → delivery drivers. Hold no roles; guard + ownership checks only.
 * CUSTOMER → shoppers. Hold no roles; guard + ownership checks only.
 */
enum UserType: string
{
    case PLATFORM = 'platform';
    case BRANCH = 'branch';
    case DRIVER = 'driver';
    case CUSTOMER = 'customer';

    /**
     * The auth guard name for this user type.
     */
    public function guard(): string
    {
        return $this->value;
    }

    /**
     * Platform and branch share one Profile page (password + Fortify 2FA).
     */
    public function usesMergedAccountProfile(): bool
    {
        return $this === self::PLATFORM || $this === self::BRANCH;
    }

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::PLATFORM => 'Platform Staff',
            self::BRANCH => 'Branch Staff',
            self::DRIVER => 'Driver',
            self::CUSTOMER => 'Customer',
        };
    }
}
