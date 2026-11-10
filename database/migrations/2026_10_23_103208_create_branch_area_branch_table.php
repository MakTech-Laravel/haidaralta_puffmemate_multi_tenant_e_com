<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_area_branch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_area_id')->constrained('branch_areas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'branch_area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_area_branch');
    }
};
