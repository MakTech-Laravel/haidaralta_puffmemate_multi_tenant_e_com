<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace the legacy red theme default with the brand primary.
     */
    public function up(): void
    {
        DB::table('application_settings')
            ->where('theme_color', '#C4472F')
            ->update(['theme_color' => '#8E8E93']);
    }

    /**
     * Reverse the brand primary back to the legacy red theme default.
     */
    public function down(): void
    {
        DB::table('application_settings')
            ->where('theme_color', '#8E8E93')
            ->update(['theme_color' => '#C4472F']);
    }
};
