<?php

namespace App\Enums;

enum OrderSource: string
{
    case CustomerCheckout = 'customer_checkout';
    case DriverSale = 'driver_sale';
    case Wholesale = 'wholesale';

    public function label(): string
    {
        return match ($this) {
            self::CustomerCheckout => 'Customer',
            self::DriverSale => 'Driver Sale',
            self::Wholesale => 'Wholesale',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $source): array => [
                'value' => $source->value,
                'label' => $source->label(),
            ],
            self::cases(),
        );
    }
}
