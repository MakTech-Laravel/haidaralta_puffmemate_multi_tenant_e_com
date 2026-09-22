<?php

namespace Database\Seeders;

use App\Services\Cms\CmsPageService;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        app(CmsPageService::class)->ensureDefaults();
    }
}
