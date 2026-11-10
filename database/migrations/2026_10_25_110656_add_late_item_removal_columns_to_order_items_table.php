<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('local_reserved_quantity')->default(0)->after('is_late_delivery');
            $table->timestamp('removed_at')->nullable()->after('local_reserved_quantity');
            $table->foreignId('removed_by')->nullable()->after('removed_at')->constrained('users')->nullOnDelete();
            $table->string('removal_note')->nullable()->after('removed_by');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('removed_by');
            $table->dropColumn(['local_reserved_quantity', 'removed_at', 'removal_note']);
        });
    }
};
