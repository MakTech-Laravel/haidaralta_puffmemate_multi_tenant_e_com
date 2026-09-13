<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('status')->default(false);
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_products');
    }
};
