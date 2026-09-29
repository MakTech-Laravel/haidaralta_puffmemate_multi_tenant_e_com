<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Services\Cms\CmsPageService;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    use ReportsSeedProgress;

    public function run(): void
    {
        $this->withSeedProgress(
            'CMS pages',
            [fn () => app(CmsPageService::class)->ensureDefaults()],
            fn (callable $step) => $step(),
            fn (): string => sprintf('%d CMS pages ready.', CmsPage::query()->count()),
        );
    }
}
