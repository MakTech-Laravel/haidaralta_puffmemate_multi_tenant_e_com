<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_stock_assignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_stock_assignment_id')
                ->constrained('driver_stock_assignments')
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->index('driver_stock_assignment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_stock_assignment_items');
    }
};
