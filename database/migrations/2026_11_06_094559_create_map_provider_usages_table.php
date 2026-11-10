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
        Schema::create('map_provider_usages', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32);
            $table->string('capability', 16);
            $table->date('used_on');
            $table->unsignedInteger('request_count')->default(0);
            $table->timestamps();

            $table->unique(['provider', 'capability', 'used_on']);
            $table->index(['provider', 'used_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_provider_usages');
    }
};
