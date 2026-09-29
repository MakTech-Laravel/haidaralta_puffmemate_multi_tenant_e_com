<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'product_id', 'variant_id']);
            $table->index(['branch_id', 'product_id']);
            $table->index('product_id');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE inventories ADD CONSTRAINT inventories_reserved_quantity_check CHECK (reserved_quantity >= 0 AND reserved_quantity <= quantity)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
