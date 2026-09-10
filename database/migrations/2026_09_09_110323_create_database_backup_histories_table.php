<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_backup_histories', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('status');
            $table->string('disk')->default('local');
            $table->string('path')->nullable();
            $table->string('filename')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_history_id')->nullable()->constrained('database_backup_histories')->nullOnDelete();
            $table->string('connection')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_backup_histories');
    }
};
