<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_shift_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_shift_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('duty_on');
            $table->foreignId('shift_template_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('kind');
            $table->timestamps();

            $table->unique(['user_shift_assignment_id', 'duty_on']);
            $table->index(['branch_id', 'duty_on']);
            $table->index(['user_id', 'duty_on']);
        });

        DB::statement('create unique index user_shift_days_person_shift_unique on user_shift_days (user_id, branch_id, duty_on, shift_template_id) where shift_template_id is not null');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_shift_days');
    }
};
