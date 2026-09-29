<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supports OrderAllocationEngine's active-branch lookup
     * (`where('status', true)->orderBy('id')`), which runs on every checkout.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
