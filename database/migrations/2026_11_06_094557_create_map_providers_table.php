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
        Schema::create('map_providers', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();
            $table->boolean('is_enabled')->default(true);
            $table->text('public_key')->nullable();
            $table->text('server_key')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('daily_limit')->nullable();
            $table->unsignedInteger('monthly_limit')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_providers');
    }
};
