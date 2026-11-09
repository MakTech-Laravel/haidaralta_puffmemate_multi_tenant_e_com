<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->relabel('regular', 'Regular delivery', 'Same day delivery');
        $this->relabel('express', 'Express delivery', 'Late delivery');
    }

    public function down(): void
    {
        $this->relabel('regular', 'Same day delivery', 'Regular delivery');
        $this->relabel('express', 'Late delivery', 'Express delivery');
    }

    /**
     * Only rename rows still carrying the previous default label so labels an
     * admin customised are left alone.
     */
    private function relabel(string $key, string $from, string $to): void
    {
        if (! Schema::hasTable('delivery_methods')) {
            return;
        }

        DB::table('delivery_methods')
            ->where('key', $key)
            ->where('label', $from)
            ->update(['label' => $to, 'updated_at' => now()]);
    }
};
