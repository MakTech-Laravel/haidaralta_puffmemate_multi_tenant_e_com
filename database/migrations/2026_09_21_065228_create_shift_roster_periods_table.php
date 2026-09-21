<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_roster_periods', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type');
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['owner_type', 'branch_id']);
        });

        DB::statement('create unique index shift_roster_periods_platform_name_unique on shift_roster_periods (name) where branch_id is null');
        DB::statement('create unique index shift_roster_periods_branch_name_unique on shift_roster_periods (branch_id, name) where branch_id is not null');
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_roster_periods');
    }
};
