<?php

namespace App\Support\Order;

use App\Models\Order;

class OrderNumber
{
    /**
     * Next sequential order number for the current year, e.g. PMM-2026-0001.
     * Call inside the placing transaction; the unique index guards races.
     */
    public static function next(): string
    {
        $prefix = 'PMM-'.now()->format('Y');
        $latest = Order::query()
            ->where('order_number', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('order_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
