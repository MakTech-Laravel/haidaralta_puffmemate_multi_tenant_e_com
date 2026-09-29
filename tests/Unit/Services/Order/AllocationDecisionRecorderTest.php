<?php

use App\Models\AllocationDecisionLog;
use App\Models\Branch;
use App\Models\Order;
use App\Services\Order\AllocationDecisionRecorder;
use Illuminate\Support\Facades\DB;

test('recording a decision writes the considered and rejected branch traces', function () {
    $order = Order::factory()->create();
    $winner = Branch::factory()->create();

    $log = app(AllocationDecisionRecorder::class)->record($order, $winner->id, [
        'considered' => [
            ['branch_id' => $winner->id, 'needs_transfer' => 0, 'operational_cost' => 0.0, 'distance_km' => 1.5, 'shipping_cost' => 5.0, 'load' => 0, 'transferred_units' => 0, 'priority' => 100],
        ],
        'rejected' => [
            ['branch_id' => 999, 'reason' => 'out_of_radius'],
        ],
    ]);

    expect($log->order_id)->toBe($order->id)
        ->and($log->winning_branch_id)->toBe($winner->id)
        ->and($log->considered_branches)->toHaveCount(1)
        ->and($log->rejected_branches[0]['reason'])->toBe('out_of_radius');

    $this->assertDatabaseHas('allocation_decision_logs', [
        'order_id' => $order->id,
        'winning_branch_id' => $winner->id,
    ]);
});

test('a rolled back order leaves no orphaned decision log', function () {
    $order = Order::factory()->create();
    $winner = Branch::factory()->create();

    try {
        DB::transaction(function () use ($order, $winner): void {
            app(AllocationDecisionRecorder::class)->record($order, $winner->id, [
                'considered' => [],
                'rejected' => [],
            ]);

            throw new RuntimeException('force rollback');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(AllocationDecisionLog::query()->where('order_id', $order->id)->exists())->toBeFalse();
});
