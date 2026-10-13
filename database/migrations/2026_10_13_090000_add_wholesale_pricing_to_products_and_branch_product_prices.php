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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('wholesale_price', 10, 2)->nullable()->after('regular_price');
            $table->unsignedInteger('min_wholesale_qty')->nullable()->after('wholesale_price');
        });

        Schema::table('branch_product_prices', function (Blueprint $table) {
            $table->decimal('wholesale_price', 10, 2)->nullable()->after('selling_price');
            $table->unsignedInteger('min_wholesale_qty')->nullable()->after('wholesale_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_product_prices', function (Blueprint $table) {
            $table->dropColumn(['wholesale_price', 'min_wholesale_qty']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['wholesale_price', 'min_wholesale_qty']);
        });
    }
};
