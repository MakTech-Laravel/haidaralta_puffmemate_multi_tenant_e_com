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
        Schema::create('map_settings', function (Blueprint $table) {
            $table->id();
            $table->string('display_provider', 32);
            $table->string('geocoding_provider', 32);
            $table->string('routing_provider', 32);
            $table->string('fallback_provider', 32);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_settings');
    }
};
