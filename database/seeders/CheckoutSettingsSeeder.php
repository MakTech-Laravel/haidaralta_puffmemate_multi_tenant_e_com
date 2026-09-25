<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Catalog\DeliverySettingsService;
use Illuminate\Database\Seeder;

class CheckoutSettingsSeeder extends Seeder
{
    public function run(): void
    {
        app(PaymentGatewaySettingsService::class)->ensureDefaults();
        app(DeliverySettingsService::class)->ensureDefaults();

        Coupon::query()->updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'type' => CouponType::Fixed,
                'value' => 10,
                'min_subtotal' => 50,
                'max_discount' => null,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addYear(),
                'usage_limit' => null,
                'usage_limit_per_customer' => 1,
                'status' => true,
            ],
        );

        Coupon::query()->updateOrCreate(
            ['code' => 'SAVE15'],
            [
                'type' => CouponType::Percent,
                'value' => 15,
                'min_subtotal' => 100,
                'max_discount' => 40,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addYear(),
                'usage_limit' => null,
                'usage_limit_per_customer' => null,
                'status' => true,
            ],
        );
    }
}
