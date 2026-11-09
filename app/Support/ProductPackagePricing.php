<?php

namespace App\Support;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductPackage;
use InvalidArgumentException;

class ProductPackagePricing
{
    public static function packagesEnabled(): bool
    {
        return (bool) config('catalog.show_product_packages');
    }

    public static function hasEnabledPackages(Product $product): bool
    {
        if (! self::packagesEnabled()) {
            return false;
        }

        if ($product->relationLoaded('packages')) {
            return $product->packages->contains(
                fn (ProductPackage $package): bool => $package->status,
            );
        }

        return $product->packages()->enabled()->exists();
    }

    public static function resolveEnabledPackage(Product $product, ?int $packageId): ?ProductPackage
    {
        if ($packageId === null) {
            return null;
        }

        if (! self::packagesEnabled()) {
            throw new InvalidArgumentException('That package is not available for this product.');
        }

        $package = ProductPackage::query()
            ->whereKey($packageId)
            ->where('product_id', $product->id)
            ->enabled()
            ->first();

        if ($package === null) {
            throw new InvalidArgumentException('That package is not available for this product.');
        }

        return $package;
    }

    public static function requirePackageSelection(Product $product, ?int $packageId): ?ProductPackage
    {
        if (! self::packagesEnabled()) {
            if ($packageId !== null) {
                throw new InvalidArgumentException('That package is not available for this product.');
            }

            return null;
        }

        $hasEnabled = self::hasEnabledPackages($product);

        if (! $hasEnabled) {
            if ($packageId !== null) {
                throw new InvalidArgumentException('That package is not available for this product.');
            }

            return null;
        }

        if ($packageId === null) {
            throw new InvalidArgumentException('Please select a package for this product.');
        }

        return self::resolveEnabledPackage($product, $packageId);
    }

    public static function unitAmount(Product $product, ?ProductPackage $package): float
    {
        if ($package !== null) {
            $amount = (float) $package->price;

            if ($amount <= 0) {
                throw new InvalidArgumentException('This package cannot be added to the cart.');
            }

            return $amount;
        }

        $amount = BranchProductPriceDisplay::catalogEffectiveAmount($product);

        if ($amount <= 0) {
            throw new InvalidArgumentException('This product cannot be added to the cart.');
        }

        return $amount;
    }

    public static function unitAmountForCartItem(CartItem $item): float
    {
        $product = $item->product;

        if ($product === null) {
            return 0.0;
        }

        $package = $item->package;

        if ($item->product_package_id !== null) {
            if ($package === null || ! $package->status || $package->product_id !== $product->id) {
                return 0.0;
            }

            return (float) $package->price;
        }

        if (self::hasEnabledPackages($product)) {
            return 0.0;
        }

        return BranchProductPriceDisplay::catalogEffectiveAmount($product);
    }
}
