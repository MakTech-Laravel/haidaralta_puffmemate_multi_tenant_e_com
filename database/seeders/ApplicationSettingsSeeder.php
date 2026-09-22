<?php

namespace Database\Seeders;

use App\Services\Application\ApplicationSettingsService;
use App\Services\Application\ContactSettingsService;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Application\SmtpSettingsService;
use Illuminate\Database\Seeder;

class ApplicationSettingsSeeder extends Seeder
{
    /**
     * Ensure singleton application settings rows exist with defaults.
     */
    public function run(): void
    {
        app(ApplicationSettingsService::class)->current();
        app(SmtpSettingsService::class)->current();
        app(ContactSettingsService::class)->current();
        app(PaymentGatewaySettingsService::class)->ensureDefaults();
        app(PaymentGatewaySettingsService::class)->refresh();
    }
}
