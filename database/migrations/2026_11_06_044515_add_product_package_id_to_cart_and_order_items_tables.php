<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id', 'variant_id']);
            $table->foreignId('product_package_id')
                ->nullable()
                ->after('variant_id')
                ->constrained('product_packages')
                ->nullOnDelete();
            $table->unique(['cart_id', 'product_id', 'variant_id', 'product_package_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_package_id')
                ->nullable()
                ->after('variant_id')
                ->constrained('product_packages')
                ->nullOnDelete();
            $table->string('package_label_snapshot')->nullable()->after('product_snapshot');
            $table->unsignedInteger('package_quantity_snapshot')->nullable()->after('package_label_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_package_id');
            $table->dropColumn(['package_label_snapshot', 'package_quantity_snapshot']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id', 'variant_id', 'product_package_id']);
            $table->dropConstrainedForeignId('product_package_id');
            $table->unique(['cart_id', 'product_id', 'variant_id']);
        });
    }
};
