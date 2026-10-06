<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_banners', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('eyebrow');
            $table->string('title');
            $table->string('description', 500);
            $table->string('primary_cta_label');
            $table->string('primary_cta_href');
            $table->string('secondary_cta_label');
            $table->string('secondary_cta_href');
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_banners');
    }
};
