<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('application_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name');
            $table->string('app_short_name');
            $table->text('short_description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('pwa_icon_192_path')->nullable();
            $table->string('pwa_icon_512_path')->nullable();
            $table->string('pwa_maskable_path')->nullable();
            $table->string('apple_touch_icon_path')->nullable();
            $table->string('theme_color', 32)->default('#C4472F');
            $table->string('background_color', 32)->default('#FFFFFF');
            $table->string('display_timezone', 64)->default('Asia/Dhaka');
            $table->string('date_format', 32)->default('Y-m-d');
            $table->string('time_format', 32)->default('H:i');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};
