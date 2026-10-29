<?php

namespace App\Enums;

enum ConversationType: string
{
    case Order = 'order';
    case DriverOps = 'driver_ops';
    case PlatformBranch = 'platform_branch';

    public function label(): string
    {
        return match ($this) {
            self::Order => 'Order chat',
            self::DriverOps => 'Driver chat',
            self::PlatformBranch => 'Platform chat',
        };
    }
}
