<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_driver_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fulfillment_id')->constrained('order_fulfillments')->cascadeOnDelete();
            $table->foreignId('from_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_driver_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_driver_assignment_histories');
    }
};
