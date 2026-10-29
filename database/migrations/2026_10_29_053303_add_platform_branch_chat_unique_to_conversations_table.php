<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE UNIQUE INDEX conversations_platform_branch_branch_id_unique ON conversations (branch_id) WHERE type = \'platform_branch\'',
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS conversations_platform_branch_branch_id_unique');
    }
};
