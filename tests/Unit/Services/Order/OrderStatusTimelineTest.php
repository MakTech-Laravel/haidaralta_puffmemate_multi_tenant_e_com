<?php

use App\Enums\FulfillmentStatus;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Support\OrderStatusTimeline;

test('the track lists every forward status once and marks the current step', function () {
    $actor = User::factory()->create(['name' => 'Emma Walker']);
    $pending = new OrderStatusHistory([
        'new_status' => FulfillmentStatus::Pending->value,
        'comment' => 'Fulfillment created',
        'changed_by' => $actor->id,
    ]);
    $pending->setRelation('changer', $actor);
    $pending->created_at = now();
    $duplicate = new OrderStatusHistory([
        'new_status' => FulfillmentStatus::Pending->value,
        'comment' => 'Order placed',
        'fulfillment_id' => null,
        'changed_by' => $actor->id,
    ]);
    $duplicate->setRelation('changer', $actor);
    $duplicate->created_at = now();

    $steps = OrderStatusTimeline::steps(FulfillmentStatus::Assigned, [$pending, $duplicate]);

    expect($steps)->toHaveCount(9)
        ->and(collect($steps)->where('key', 'pending'))->toHaveCount(1)
        ->and($steps[0]['state'])->toBe('completed')
        ->and($steps[0]['comment'])->toBe('Fulfillment created')
        ->and($steps[1]['state'])->toBe('upcoming')
        ->and($steps[1]['actorName'])->toBeNull()
        ->and($steps[4]['key'])->toBe('assigned')
        ->and($steps[4]['state'])->toBe('current')
        ->and($steps[5]['state'])->toBe('upcoming')
        ->and($steps[5]['actorName'])->toBeNull();
});
