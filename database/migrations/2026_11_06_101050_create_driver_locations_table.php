<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Breadcrumbs only: the live position of a driver is kept in the cache, never
     * written here per ping. driver_id is users.id (user_type=driver).
     */
    public function up(): void
    {
        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fulfillment_id')->nullable()->constrained('order_fulfillments')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('accuracy')->nullable();
            $table->float('speed')->nullable();
            $table->unsignedSmallInteger('heading')->nullable();
            $table->timestamp('recorded_at');

            $table->index(['driver_id', 'recorded_at']);
            $table->index('recorded_at');
            $table->index('fulfillment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
    }
};
