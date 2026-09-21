<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_shift_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('previous_shift_template_id')->nullable()->constrained('shift_templates')->nullOnDelete();
            $table->foreignId('new_shift_template_id')->constrained('shift_templates')->restrictOnDelete();
            $table->time('previous_start_time')->nullable();
            $table->time('previous_end_time')->nullable();
            $table->time('new_start_time');
            $table->time('new_end_time');
            $table->date('effective_date');
            $table->string('reason')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'effective_date']);
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_shift_histories');
    }
};
