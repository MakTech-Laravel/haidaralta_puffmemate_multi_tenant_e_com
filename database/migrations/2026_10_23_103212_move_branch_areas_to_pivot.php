<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('branch_areas')
            ->orderBy('id')
            ->select(['id', 'branch_id'])
            ->chunkById(200, function ($rows) use ($now): void {
                $inserts = [];

                foreach ($rows as $row) {
                    if ($row->branch_id === null) {
                        continue;
                    }

                    $inserts[] = [
                        'branch_id' => $row->branch_id,
                        'branch_area_id' => $row->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($inserts !== []) {
                    DB::table('branch_area_branch')->insertOrIgnore($inserts);
                }
            });

        Schema::table('branch_areas', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'is_active']);
        });

        Schema::table('branch_areas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('branch_areas', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $links = DB::table('branch_area_branch')
            ->orderBy('branch_area_id')
            ->orderBy('id')
            ->get(['branch_area_id', 'branch_id']);

        $assigned = [];

        foreach ($links as $link) {
            if (isset($assigned[$link->branch_area_id])) {
                continue;
            }

            DB::table('branch_areas')
                ->where('id', $link->branch_area_id)
                ->update(['branch_id' => $link->branch_id]);

            $assigned[$link->branch_area_id] = true;
        }

        Schema::table('branch_areas', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable(false)->change();
            $table->index(['branch_id', 'is_active']);
        });
    }
};
