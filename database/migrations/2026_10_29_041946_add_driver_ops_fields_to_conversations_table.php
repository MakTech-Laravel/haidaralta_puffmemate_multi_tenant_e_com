<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('type', 16)->default('order')->after('id');
            $table->foreignId('driver_id')->nullable()->after('branch_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->change();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->unique('order_id');
            $table->unique(['branch_id', 'driver_id']);
            $table->index(['type', 'branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'driver_id']);
            $table->dropIndex(['type', 'branch_id', 'status']);
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['type', 'driver_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropUnique(['order_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable(false)->change();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->unique('order_id');
        });
    }
};
