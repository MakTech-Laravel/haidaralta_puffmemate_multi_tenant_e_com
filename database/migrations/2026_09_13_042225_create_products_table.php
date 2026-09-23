<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->string('image')->nullable();
            $table->json('tags')->nullable();
            $table->json('specifications')->nullable();
            $table->json('color_code')->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->decimal('regular_price', 12, 2)->nullable();
            $table->string('discount_type', 20)->default('none');
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->date('discount_start_date')->nullable();
            $table->date('discount_end_date')->nullable();
            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('brand');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
