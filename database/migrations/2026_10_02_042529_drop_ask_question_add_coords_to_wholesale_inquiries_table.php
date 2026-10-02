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
        Schema::table('wholesale_inquiries', function (Blueprint $table) {
            $table->dropColumn('ask_question');
            $table->string('latitude')->nullable()->after('address');
            $table->string('longitude')->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wholesale_inquiries', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
            $table->string('ask_question')->nullable()->after('company');
        });
    }
};
