<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('base_fee', 12, 2)->default(0);
            $table->decimal('free_delivery_distance_km', 8, 2)->nullable();
            $table->decimal('cost_per_km', 12, 2)->default(0);
            $table->decimal('minimum_delivery_charge', 12, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_shipping_rules');
    }
};
