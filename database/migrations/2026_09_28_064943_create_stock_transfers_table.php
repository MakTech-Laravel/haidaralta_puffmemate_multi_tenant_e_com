<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fulfillment_id')->constrained('order_fulfillments')->cascadeOnDelete();
            $table->foreignId('source_branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('destination_branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('status', 32)->default('requested');
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('eta_hours', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['destination_branch_id', 'status']);
            $table->index(['source_branch_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
