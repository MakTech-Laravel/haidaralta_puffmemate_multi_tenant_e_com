<?php

namespace App\Enums;

enum StockTransferStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Picking = 'picking';
    case Dispatched = 'dispatched';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function isOpen(): bool
    {
        return match ($this) {
            self::Received, self::Cancelled => false,
            default => true,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::Picking => 'Picking',
            self::Dispatched => 'Dispatched',
            self::InTransit => 'In transit',
            self::Received => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }

    public function sourceAction(): ?string
    {
        return match ($this) {
            self::Requested => 'approve',
            self::Approved => 'pick',
            self::Picking => 'dispatch',
            default => null,
        };
    }

    public function destinationAction(): ?string
    {
        return $this === self::InTransit ? 'receive' : null;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }
}
