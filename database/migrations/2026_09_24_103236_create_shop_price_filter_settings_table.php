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
        Schema::create('shop_price_filter_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('slider_min', 12, 2)->default(0);
            $table->decimal('slider_max', 12, 2)->default(50000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_price_filter_settings');
    }
};
