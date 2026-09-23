<?php

namespace App\Enums;

/**
 * Who a named shift is for. All is the default.
 */
enum ShiftAudience: string
{
    case All = 'all';
    case Staff = 'staff';
    case Driver = 'driver';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::All => 'All',
            self::Staff => 'Staff only',
            self::Driver => 'Drivers only',
        };
    }

    /**
     * Whether this window should appear when the board is filtered.
     */
    public function visibleFor(self $filter): bool
    {
        return $filter === self::All || $this === self::All || $this === $filter;
    }
}
