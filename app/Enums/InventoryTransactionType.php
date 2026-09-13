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
}
