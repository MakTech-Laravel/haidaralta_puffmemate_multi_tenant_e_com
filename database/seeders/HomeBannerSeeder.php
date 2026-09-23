<?php

namespace Database\Seeders;

use App\Services\Cms\HomeBannerService;
use Illuminate\Database\Seeder;

class HomeBannerSeeder extends Seeder
{
    public function run(): void
    {
        app(HomeBannerService::class)->ensureDefaults();
    }
}
