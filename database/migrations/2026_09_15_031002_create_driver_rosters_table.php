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
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('scheduled');
            $table->timestamps();

            $table->index(['branch_id', 'date']);
            $table->index(['driver_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_rosters');
    }
};
