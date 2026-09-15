<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('license_number')->nullable()->unique();
            $table->string('vehicle_type');
            $table->string('availability_status')->default('offline');
            $table->text('notes')->nullable();
            $table->text('address')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('vehicle_image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};
