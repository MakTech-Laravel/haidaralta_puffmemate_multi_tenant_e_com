<?php

use App\Enums\DiscountType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('buying_price', 12, 2)->nullable();
            $table->decimal('regular_price', 12, 2)->nullable();
            $table->decimal('selling_price', 12, 2);
            $table->string('discount_type', 20)->default(DiscountType::None->value);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'product_id', 'variant_id']);
            $table->index(['start_date', 'end_date']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('branch_product_prices', function (Blueprint $table) {
                $table->index(['branch_id', 'product_id', 'status']);
            });

            return;
        }

        $active = $driver === 'pgsql' ? 'status = true' : 'status = 1';

        DB::statement("create index branch_product_prices_active_index on branch_product_prices (branch_id, product_id) where {$active}");
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_product_prices');
    }
};
