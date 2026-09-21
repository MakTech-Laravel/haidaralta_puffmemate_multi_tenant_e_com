<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('application_settings', 'slogan')) {
            Schema::table('application_settings', function (Blueprint $table) {
                $table->dropColumn('slogan');
            });
        }

        if (! Schema::hasColumn('application_settings', 'apple_touch_icon_path')) {
            Schema::table('application_settings', function (Blueprint $table) {
                $table->string('apple_touch_icon_path')->nullable()->after('pwa_maskable_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('application_settings', 'apple_touch_icon_path')) {
            Schema::table('application_settings', function (Blueprint $table) {
                $table->dropColumn('apple_touch_icon_path');
            });
        }

        if (! Schema::hasColumn('application_settings', 'slogan')) {
            Schema::table('application_settings', function (Blueprint $table) {
                $table->string('slogan')->nullable()->after('app_short_name');
            });
        }
    }
};
