<?php

namespace App\Enums;

enum ConversationParticipantRole: string
{
    case Customer = 'customer';
    case Driver = 'driver';
    case BranchStaff = 'branch_staff';
    case PlatformStaff = 'platform_staff';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Driver => 'Driver',
            self::BranchStaff => 'Branch staff',
            self::PlatformStaff => 'Platform staff',
        };
    }

    /**
     * Map a portal user type onto its conversation participant role.
     */
    public static function fromUserType(UserType $userType): self
    {
        return match ($userType) {
            UserType::CUSTOMER => self::Customer,
            UserType::DRIVER => self::Driver,
            UserType::BRANCH => self::BranchStaff,
            UserType::PLATFORM => self::PlatformStaff,
        };
    }
}
