<?php

namespace App\Support;

use App\Models\OrderItem;

class OrderItemPackageDisplay
{
    public static function label(OrderItem $item): ?string
    {
        if (filled($item->package_label_snapshot)) {
            return (string) $item->package_label_snapshot;
        }

        $snapshot = is_array($item->product_snapshot) ? $item->product_snapshot : [];
        $label = $snapshot['package_label'] ?? null;

        return filled($label) ? (string) $label : null;
    }

    public static function itemsPerPackage(OrderItem $item): ?int
    {
        if ($item->package_quantity_snapshot !== null) {
            return (int) $item->package_quantity_snapshot;
        }

        $snapshot = is_array($item->product_snapshot) ? $item->product_snapshot : [];
        $quantity = $snapshot['package_quantity'] ?? null;

        return $quantity === null || $quantity === '' ? null : (int) $quantity;
    }

    public static function subtitle(OrderItem $item): ?string
    {
        $label = self::label($item);

        if ($label === null) {
            return null;
        }

        $itemsPerPackage = self::itemsPerPackage($item);

        if ($itemsPerPackage === null || $itemsPerPackage < 1) {
            return $label;
        }

        return $label.' · '.$itemsPerPackage.' '.($itemsPerPackage === 1 ? 'item' : 'items');
    }
}
