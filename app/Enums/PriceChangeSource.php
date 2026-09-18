<?php

namespace App\Enums;

enum PriceChangeSource: string
{
    case PriceUpsert = 'price_upsert';
    case StockIn = 'stock_in';
    case ProductCreate = 'product_create';

    public function label(): string
    {
        return match ($this) {
            self::PriceUpsert => 'Price update',
            self::StockIn => 'New entry',
            self::ProductCreate => 'Product create',
        };
    }
}
