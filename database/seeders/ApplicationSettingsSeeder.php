<?php

namespace Database\Seeders;

use App\Services\Application\ApplicationSettingsService;
use App\Services\Application\ContactSettingsService;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Application\SmtpSettingsService;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;

class ApplicationSettingsSeeder extends Seeder
{
    use ReportsSeedProgress;

    /**
     * Ensure singleton application settings rows exist with defaults.
     */
    public function run(): void
    {
        $this->withSeedProgress('Settings', [
            fn () => app(ApplicationSettingsService::class)->current(),
            fn () => app(SmtpSettingsService::class)->current(),
            fn () => app(ContactSettingsService::class)->current(),
            fn () => app(PaymentGatewaySettingsService::class)->ensureDefaults(),
            fn () => app(PaymentGatewaySettingsService::class)->refresh(),
        ], fn (callable $step) => $step(), 'Application, SMTP, contact and payment gateway settings ready.');
    }
}
