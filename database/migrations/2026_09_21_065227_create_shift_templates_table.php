<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type');
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('audience')->default('all');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['owner_type', 'branch_id']);
        });

        DB::statement('create unique index shift_templates_platform_name_unique on shift_templates (name) where branch_id is null');
        DB::statement('create unique index shift_templates_branch_name_unique on shift_templates (branch_id, name) where branch_id is not null');
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_templates');
    }
};
