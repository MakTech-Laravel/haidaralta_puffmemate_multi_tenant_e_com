<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case StockIn = 'stock_in';
    case StockOut = 'stock_out';
    case Adjustment = 'adjustment';
    case Reserve = 'reserve';
    case Release = 'release';
    case Sale = 'sale';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';

    public function label(): string
    {
        return match ($this) {
            self::StockIn => 'New entry',
            self::StockOut => 'Reduce',
            self::Adjustment => 'Adjustment',
            self::Reserve => 'Reserve',
            self::Release => 'Release',
            self::Sale => 'Sale',
            self::TransferOut => 'Transfer out',
            self::TransferIn => 'Transfer in',
        };
    }

    /**
     * @return list<self>
     */
    public static function manualCases(): array
    {
        return [
            self::StockIn,
            self::StockOut,
            self::Adjustment,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function manualOptions(): array
    {
        return array_map(
            fn (self $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::manualCases(),
        );
    }
}
