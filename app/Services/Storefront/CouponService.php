<?php

namespace App\Services\Storefront;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /**
     * @return array{coupon: Coupon, discount: float}
     */
    public function quote(string $code, float $subtotal, ?User $user = null): array
    {
        $coupon = Coupon::query()
            ->whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])
            ->first();

        if ($coupon === null || ! $coupon->status) {
            throw ValidationException::withMessages([
                'coupon' => 'That coupon code is not valid.',
            ]);
        }

        $now = Carbon::now();

        if ($coupon->starts_at !== null && $now->lt($coupon->starts_at)) {
            throw ValidationException::withMessages([
                'coupon' => 'That coupon is not active yet.',
            ]);
        }

        if ($coupon->ends_at !== null && $now->gt($coupon->ends_at)) {
            throw ValidationException::withMessages([
                'coupon' => 'That coupon has expired.',
            ]);
        }

        $min = $coupon->min_subtotal === null ? 0.0 : (float) $coupon->min_subtotal;

        if ($subtotal < $min) {
            throw ValidationException::withMessages([
                'coupon' => 'This coupon requires a minimum purchase of $'.number_format($min, 2).'.',
            ]);
        }

        if ($coupon->usage_limit !== null && $coupon->redemptions()->count() >= $coupon->usage_limit) {
            throw ValidationException::withMessages([
                'coupon' => 'This coupon has reached its usage limit.',
            ]);
        }

        if (
            $user !== null
            && $coupon->usage_limit_per_customer !== null
            && $coupon->redemptions()->where('user_id', $user->id)->count() >= $coupon->usage_limit_per_customer
        ) {
            throw ValidationException::withMessages([
                'coupon' => 'You have already used this coupon the maximum number of times.',
            ]);
        }

        $discount = $this->discountFor($coupon, $subtotal);

        return [
            'coupon' => $coupon,
            'discount' => $discount,
        ];
    }

    public function discountFor(Coupon $coupon, float $subtotal): float
    {
        $value = (float) $coupon->value;

        $discount = $coupon->type === CouponType::Percent
            ? $subtotal * ($value / 100)
            : $value;

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return round(min(max($discount, 0), $subtotal), 2);
    }
}
