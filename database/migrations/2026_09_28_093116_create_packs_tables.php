<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('offer_type');
            $table->decimal('offer_value', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('pack_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->boolean('status')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamp('highlighted_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['branch_id', 'slug']);
            $table->index(['branch_id', 'status']);
        });

        Schema::create('pack_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pack_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['pack_id', 'product_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('pack_items');
        Schema::dropIfExists('packs');
    }
};
