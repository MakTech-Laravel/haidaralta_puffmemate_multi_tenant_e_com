<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_methods')) {
            return;
        }

        $this->renameMethod(
            fromKey: 'australia-wide',
            toKey: 'regular',
            label: 'Regular delivery',
            description: 'Standard delivery for eligible orders.',
            requiresSlot: false,
            sort: 0,
        );

        $this->renameMethod(
            fromKey: 'same-day',
            toKey: 'express',
            label: 'Express delivery',
            description: 'Priority delivery in selected areas with a chosen time slot.',
            requiresSlot: true,
            sort: 1,
        );

        if (Schema::hasTable('orders')) {
            DB::table('orders')->where('delivery_method', 'australia-wide')->update(['delivery_method' => 'regular']);
            DB::table('orders')->where('delivery_method', 'same-day')->update(['delivery_method' => 'express']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('delivery_methods')) {
            return;
        }

        $this->renameMethod(
            fromKey: 'regular',
            toKey: 'australia-wide',
            label: 'Australia-wide shipping',
            description: 'Shipping available for eligible orders.',
            requiresSlot: false,
            sort: 1,
        );

        $this->renameMethod(
            fromKey: 'express',
            toKey: 'same-day',
            label: 'Same-day local delivery',
            description: 'Fast local delivery in selected areas.',
            requiresSlot: true,
            sort: 0,
        );

        if (Schema::hasTable('orders')) {
            DB::table('orders')->where('delivery_method', 'regular')->update(['delivery_method' => 'australia-wide']);
            DB::table('orders')->where('delivery_method', 'express')->update(['delivery_method' => 'same-day']);
        }
    }

    private function renameMethod(
        string $fromKey,
        string $toKey,
        string $label,
        string $description,
        bool $requiresSlot,
        int $sort,
    ): void {
        $existing = DB::table('delivery_methods')->where('key', $fromKey)->first();

        if ($existing === null) {
            return;
        }

        $target = DB::table('delivery_methods')->where('key', $toKey)->first();

        if ($target !== null && (int) $target->id !== (int) $existing->id) {
            if (Schema::hasTable('delivery_time_slots')) {
                DB::table('delivery_time_slots')
                    ->where('delivery_method_id', $existing->id)
                    ->update(['delivery_method_id' => $target->id]);
            }

            DB::table('delivery_methods')->where('id', $existing->id)->delete();

            DB::table('delivery_methods')->where('id', $target->id)->update([
                'label' => $label,
                'description' => $description,
                'requires_slot' => $requiresSlot,
                'sort' => $sort,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('delivery_methods')->where('id', $existing->id)->update([
            'key' => $toKey,
            'label' => $label,
            'description' => $description,
            'requires_slot' => $requiresSlot,
            'sort' => $sort,
            'updated_at' => now(),
        ]);
    }
};
