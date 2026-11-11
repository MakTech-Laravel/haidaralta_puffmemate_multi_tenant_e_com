<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move the theme colour default from Apple grey to the brand grey-green.
     * Rows holding a custom colour are left untouched.
     */
    public function up(): void
    {
        Schema::table('application_settings', function (Blueprint $table): void {
            $table->string('theme_color', 32)->default('#174B36')->change();
        });

        DB::table('application_settings')
            ->where('theme_color', '#8E8E93')
            ->update(['theme_color' => '#174B36']);
    }

    /**
     * Restore the previous Apple grey default.
     */
    public function down(): void
    {
        DB::table('application_settings')
            ->where('theme_color', '#174B36')
            ->update(['theme_color' => '#8E8E93']);

        Schema::table('application_settings', function (Blueprint $table): void {
            $table->string('theme_color', 32)->default('#8E8E93')->change();
        });
    }
};
