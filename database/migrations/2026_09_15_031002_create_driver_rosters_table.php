<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->jsonb('days_of_week')->default('[1,2,3,4,5,6,7]');
            $table->string('status')->default('scheduled');
            $table->timestamps();

            $table->index(['branch_id', 'start_date']);
            $table->index(['driver_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_rosters');
    }
};
