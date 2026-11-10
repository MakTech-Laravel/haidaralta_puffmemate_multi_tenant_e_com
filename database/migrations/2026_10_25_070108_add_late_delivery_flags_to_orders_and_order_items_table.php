<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('accepts_late_delivery')->default(false)->after('notes');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_late_delivery')->default(false)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('accepts_late_delivery');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('is_late_delivery');
        });
    }
};
