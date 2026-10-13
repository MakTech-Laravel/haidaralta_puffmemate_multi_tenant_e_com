<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wholesale_inquiries', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->after('customer_id')->constrained('orders')->nullOnDelete();
            $table->text('message')->nullable()->change();
            $table->text('admin_note')->nullable()->after('message');
            $table->text('rejection_reason')->nullable()->after('admin_note');
            $table->foreignId('reviewed_by')->nullable()->after('read_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wholesale_inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('order_id');
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn(['admin_note', 'rejection_reason', 'reviewed_at']);
        });

        DB::table('wholesale_inquiries')->whereNull('message')->update(['message' => '']);

        Schema::table('wholesale_inquiries', function (Blueprint $table) {
            $table->text('message')->nullable(false)->change();
        });
    }
};
