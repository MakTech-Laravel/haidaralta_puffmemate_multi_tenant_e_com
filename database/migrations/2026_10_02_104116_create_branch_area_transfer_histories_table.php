<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_area_transfer_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_area_id')->constrained('branch_areas')->cascadeOnDelete();
            $table->foreignId('from_branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->restrictOnDelete();
            $table->date('effective_date');
            $table->text('reason');
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_area_id', 'effective_date']);
            $table->index('to_branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_area_transfer_histories');
    }
};
