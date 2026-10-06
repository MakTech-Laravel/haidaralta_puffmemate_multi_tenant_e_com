<?php

namespace Database\Seeders;

use App\Models\HomeBanner;
use App\Services\Cms\HomeBannerService;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;

class HomeBannerSeeder extends Seeder
{
    use ReportsSeedProgress;

    public function run(): void
    {
        $this->withSeedProgress(
            'Banners',
            [fn () => app(HomeBannerService::class)->ensureDefaults()],
            fn (callable $step) => $step(),
            fn (): string => sprintf('%d home banners ready.', HomeBanner::query()->count()),
        );
    }
}
