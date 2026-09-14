<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->string('slogan')->nullable();
            $table->json('color_code')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('featured')->default(false);
            $table->boolean('show_in_nav')->default(false);
            $table->unsignedSmallInteger('nav_sort')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id');
            $table->index('status');
            $table->index('featured');
            $table->index('show_in_nav');
            $table->index('nav_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
