<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_stock_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode');
            $table->timestamps();

            $table->index(['branch_id', 'created_at']);
        });

        Schema::create('driver_stock_assignment_driver', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_stock_assignment_id')
                ->constrained('driver_stock_assignments')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unique(
                ['driver_stock_assignment_id', 'user_id'],
                'driver_stock_assignment_driver_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_stock_assignment_driver');
        Schema::dropIfExists('driver_stock_assignments');
    }
};
