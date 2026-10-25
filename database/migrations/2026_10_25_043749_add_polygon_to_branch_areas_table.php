<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_areas', function (Blueprint $table) {
            $table->json('polygon')->nullable()->after('longitude');
        });

        $delta = 0.01;

        DB::table('branch_areas')
            ->orderBy('id')
            ->select(['id', 'latitude', 'longitude'])
            ->chunkById(200, function ($rows) use ($delta): void {
                foreach ($rows as $row) {
                    $lat = (float) $row->latitude;
                    $lng = (float) $row->longitude;

                    $ring = [
                        ['lat' => $lat - $delta, 'lng' => $lng - $delta],
                        ['lat' => $lat - $delta, 'lng' => $lng + $delta],
                        ['lat' => $lat + $delta, 'lng' => $lng + $delta],
                        ['lat' => $lat + $delta, 'lng' => $lng - $delta],
                        ['lat' => $lat - $delta, 'lng' => $lng - $delta],
                    ];

                    DB::table('branch_areas')
                        ->where('id', $row->id)
                        ->update(['polygon' => json_encode($ring)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('branch_areas', function (Blueprint $table) {
            $table->dropColumn('polygon');
        });
    }
};
