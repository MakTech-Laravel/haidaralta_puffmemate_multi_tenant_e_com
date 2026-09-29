<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Explainability trace for OrderAllocationEngine: which branches were
     * considered (with their scoring dimensions) and rejected (with why),
     * plus the winner, for one order. Written inside the same transaction
     * as order placement, so a rolled-back order leaves no orphaned log.
     */
    public function up(): void
    {
        Schema::create('allocation_decision_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('winning_branch_id')->constrained('branches')->restrictOnDelete();
            $table->json('considered_branches');
            $table->json('rejected_branches');
            $table->timestamp('created_at')->useCurrent();

            $table->unique('order_id');
            $table->index('winning_branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_decision_logs');
    }
};
