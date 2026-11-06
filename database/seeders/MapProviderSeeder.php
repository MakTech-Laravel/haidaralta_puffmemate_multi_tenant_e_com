<?php

namespace Database\Seeders;

use App\Services\Maps\MapSettingsService;
use Illuminate\Database\Seeder;

class MapProviderSeeder extends Seeder
{
    /**
     * Creates one row per code-registered map provider plus the selection row.
     */
    public function run(MapSettingsService $settings): void
    {
        $settings->sync();
        $settings->refresh();
    }
}
