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

test('customer track collapses to five milestones and marks assigned as on the way', function () {
    $actor = User::factory()->create(['name' => 'Emma Walker']);
    $placed = new OrderStatusHistory([
        'new_status' => FulfillmentStatus::Placed->value,
        'comment' => 'Order confirmed',
        'changed_by' => $actor->id,
    ]);
    $placed->setRelation('changer', $actor);
    $placed->created_at = now();
    $ready = new OrderStatusHistory([
        'new_status' => FulfillmentStatus::Ready->value,
        'comment' => 'Packed',
        'changed_by' => $actor->id,
    ]);
    $ready->setRelation('changer', $actor);
    $ready->created_at = now();

    $steps = OrderStatusTimeline::steps(FulfillmentStatus::Assigned, [$placed, $ready], true);

    expect($steps)->toHaveCount(5)
        ->and(collect($steps)->pluck('key')->all())->toBe([
            'placed',
            'ready',
            'out_for_delivery',
            'arrived',
            'delivered',
        ])
        ->and($steps[0]['label'])->toBe('Order placed')
        ->and($steps[0]['state'])->toBe('completed')
        ->and($steps[1]['label'])->toBe('Packed')
        ->and($steps[1]['state'])->toBe('completed')
        ->and($steps[2]['key'])->toBe('out_for_delivery')
        ->and($steps[2]['label'])->toBe('On the way')
        ->and($steps[2]['state'])->toBe('current')
        ->and($steps[3]['state'])->toBe('upcoming')
        ->and($steps[4]['state'])->toBe('upcoming');
});

test('customer track marks processing as packed current', function () {
    $actor = User::factory()->create(['name' => 'Sam Rivera']);
    $placed = new OrderStatusHistory([
        'new_status' => FulfillmentStatus::Placed->value,
        'comment' => 'Placed',
        'changed_by' => $actor->id,
    ]);
    $placed->setRelation('changer', $actor);
    $placed->created_at = now();
    $processing = new OrderStatusHistory([
        'new_status' => FulfillmentStatus::Processing->value,
        'comment' => 'Preparing',
        'changed_by' => $actor->id,
    ]);
    $processing->setRelation('changer', $actor);
    $processing->created_at = now();

    $steps = OrderStatusTimeline::steps(FulfillmentStatus::Processing, [$placed, $processing], true);

    expect($steps)->toHaveCount(5)
        ->and($steps[0]['key'])->toBe('placed')
        ->and($steps[0]['state'])->toBe('completed')
        ->and($steps[0]['comment'])->toBe('Placed')
        ->and($steps[1]['key'])->toBe('ready')
        ->and($steps[1]['label'])->toBe('Packed')
        ->and($steps[1]['state'])->toBe('current')
        ->and($steps[1]['comment'])->toBe('Preparing')
        ->and($steps[2]['state'])->toBe('upcoming');
});
