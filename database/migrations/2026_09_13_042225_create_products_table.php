<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->string('pack_label')->nullable();
            $table->unsignedInteger('pieces_per_pack')->nullable();
            $table->string('pack_unit', 40)->nullable();
            $table->json('tags')->nullable();
            $table->json('specifications')->nullable();
            $table->json('color_code')->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->decimal('regular_price', 12, 2)->nullable();
            $table->decimal('wholesale_price', 10, 2)->nullable();
            $table->unsignedInteger('min_wholesale_qty')->nullable();
            $table->string('discount_type', 20)->default('none');
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->date('discount_start_date')->nullable();
            $table->date('discount_end_date')->nullable();
            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('brand');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('products', function (Blueprint $table) {
                $table->index('status');
                $table->index(['status', 'name']);
                $table->index(['status', 'created_at']);
                $table->index('selling_price');
            });

            return;
        }

        $active = $driver === 'pgsql'
            ? 'status = true and deleted_at is null'
            : 'status = 1 and deleted_at is null';

        DB::statement("create index products_active_name_index on products (name) where {$active}");
        DB::statement("create index products_active_created_at_index on products (created_at) where {$active}");
        DB::statement("create index products_active_selling_price_index on products (selling_price) where {$active}");

        if ($driver === 'pgsql') {
            DB::statement('alter table products alter column tags type jsonb using tags::jsonb');
            DB::statement('create index products_tags_gin on products using gin (tags jsonb_path_ops)');
            DB::statement('create extension if not exists pg_trgm');
            DB::statement('create index products_name_trgm on products using gin (name gin_trgm_ops)');
            DB::statement('create index products_brand_trgm on products using gin (brand gin_trgm_ops)');
            DB::statement('create index products_sku_trgm on products using gin (sku gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
