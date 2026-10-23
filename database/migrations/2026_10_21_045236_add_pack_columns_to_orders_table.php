<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('pack_id')
                ->nullable()
                ->after('notes')
                ->constrained('packs')
                ->nullOnDelete();
            $table->unsignedInteger('pack_quantity')
                ->nullable()
                ->after('pack_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pack_id');
            $table->dropColumn('pack_quantity');
        });
    }
};
