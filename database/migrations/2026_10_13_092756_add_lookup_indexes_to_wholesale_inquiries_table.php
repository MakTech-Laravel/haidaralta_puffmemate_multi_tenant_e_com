<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wholesale_inquiries', function (Blueprint $table) {
            $table->index(['customer_id', 'created_at']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wholesale_inquiries', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'created_at']);
            $table->dropIndex(['order_id']);
        });
    }
};
