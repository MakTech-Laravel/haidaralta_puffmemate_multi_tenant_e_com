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

test('cancelled after return stays on the same progress rail', function () {
    $histories = [];

    foreach ([
        FulfillmentStatus::Pending,
        FulfillmentStatus::Placed,
        FulfillmentStatus::Ready,
        FulfillmentStatus::Assigned,
        FulfillmentStatus::Arrived,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returning,
        FulfillmentStatus::Returned,
        FulfillmentStatus::Cancelled,
    ] as $status) {
        $row = new OrderStatusHistory([
            'new_status' => $status->value,
            'comment' => $status->label(),
        ]);
        $row->created_at = now();
        $histories[] = $row;
    }

    $steps = OrderStatusTimeline::steps(FulfillmentStatus::Cancelled, $histories);
    $keys = collect($steps)->pluck('key')->all();
    $previous = OrderStatusTimeline::previousAttemptRails($histories);

    expect($previous)->toBe([])
        ->and($keys)->toContain('arrived')
        ->and($keys)->toContain('delivery_failed')
        ->and($keys)->toContain('returning')
        ->and($keys)->toContain('returned')
        ->and($keys[array_key_last($keys)])->toBe('cancelled')
        ->and(collect($steps)->last()['state'])->toBe('current')
        ->and(collect($steps)->where('state', 'completed')->count())->toBeGreaterThan(3);
});

test('cancelled track keeps ready when cancelled from ready', function () {
    $ready = new OrderStatusHistory([
        'new_status' => FulfillmentStatus::Ready->value,
        'comment' => 'Packed',
    ]);
    $ready->created_at = now();
    $cancelled = new OrderStatusHistory([
        'new_status' => FulfillmentStatus::Cancelled->value,
        'comment' => 'Order cancelled',
    ]);
    $cancelled->created_at = now();

    $steps = OrderStatusTimeline::steps(FulfillmentStatus::Cancelled, [$ready, $cancelled]);
    $keys = collect($steps)->pluck('key')->all();

    expect($keys)->toContain('ready')
        ->and($keys[array_key_last($keys)])->toBe('cancelled');
});

test('reassign after return shows only the new attempt on progress', function () {
    $histories = [];

    foreach ([
        FulfillmentStatus::Pending,
        FulfillmentStatus::Placed,
        FulfillmentStatus::Ready,
        FulfillmentStatus::Assigned,
        FulfillmentStatus::Arrived,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returning,
        FulfillmentStatus::Returned,
        FulfillmentStatus::Assigned,
    ] as $status) {
        $row = new OrderStatusHistory([
            'new_status' => $status->value,
            'comment' => $status->label(),
        ]);
        $row->created_at = now();
        $histories[] = $row;
    }

    $steps = OrderStatusTimeline::steps(FulfillmentStatus::Assigned, $histories);
    $keys = collect($steps)->pluck('key')->all();

    expect($keys)->toBe([
        'assigned',
        'picked_up',
        'out_for_delivery',
        'arrived',
        'delivered',
    ])
        ->and($keys)->not->toContain('delivery_failed')
        ->and($keys)->not->toContain('returning')
        ->and($keys)->not->toContain('returned')
        ->and($steps[0]['key'])->toBe('assigned')
        ->and($steps[0]['state'])->toBe('current');
});

test('reassign after return advances progress without return steps', function () {
    $histories = [];

    foreach ([
        FulfillmentStatus::Pending,
        FulfillmentStatus::Ready,
        FulfillmentStatus::Assigned,
        FulfillmentStatus::Arrived,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returning,
        FulfillmentStatus::Returned,
        FulfillmentStatus::Assigned,
        FulfillmentStatus::OutForDelivery,
    ] as $status) {
        $row = new OrderStatusHistory([
            'new_status' => $status->value,
            'comment' => $status->label(),
        ]);
        $row->created_at = now();
        $histories[] = $row;
    }

    $steps = OrderStatusTimeline::steps(FulfillmentStatus::OutForDelivery, $histories);
    $keys = collect($steps)->pluck('key')->all();

    expect($keys)->toBe([
        'assigned',
        'picked_up',
        'out_for_delivery',
        'arrived',
        'delivered',
    ])
        ->and($keys)->not->toContain('delivery_failed')
        ->and($steps[0]['state'])->toBe('completed')
        ->and($steps[2]['key'])->toBe('out_for_delivery')
        ->and($steps[2]['state'])->toBe('current');
});

test('assigned without a return cycle stays on the happy path', function () {
    $assigned = new OrderStatusHistory([
        'new_status' => FulfillmentStatus::Assigned->value,
        'comment' => 'Driver assigned',
    ]);
    $assigned->created_at = now();

    $steps = OrderStatusTimeline::steps(FulfillmentStatus::Assigned, [$assigned]);
    $keys = collect($steps)->pluck('key')->all();

    expect($steps)->toHaveCount(9)
        ->and($keys)->not->toContain('delivery_failed')
        ->and($keys)->not->toContain('returning')
        ->and($keys)->not->toContain('returned')
        ->and(collect($steps)->where('key', 'assigned')->count())->toBe(1)
        ->and($steps[4]['key'])->toBe('assigned')
        ->and($steps[4]['state'])->toBe('current');
});

test('customer track after reassign shows only the new attempt milestones', function () {
    $histories = [];

    foreach ([
        FulfillmentStatus::Placed,
        FulfillmentStatus::Ready,
        FulfillmentStatus::Assigned,
        FulfillmentStatus::Arrived,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returning,
        FulfillmentStatus::Returned,
        FulfillmentStatus::Assigned,
    ] as $status) {
        $row = new OrderStatusHistory([
            'new_status' => $status->value,
            'comment' => $status->label(),
        ]);
        $row->created_at = now();
        $histories[] = $row;
    }

    $steps = OrderStatusTimeline::steps(FulfillmentStatus::Assigned, $histories, true);
    $keys = collect($steps)->pluck('key')->all();

    expect($keys)->toBe([
        'out_for_delivery',
        'arrived',
        'delivered',
    ])
        ->and($keys)->not->toContain('delivery_failed')
        ->and($keys)->not->toContain('returning')
        ->and($keys)->not->toContain('returned')
        ->and($steps[0]['key'])->toBe('out_for_delivery')
        ->and($steps[0]['state'])->toBe('current');
});

test('activity sections split at returned when a new attempt starts', function () {
    $histories = [];

    foreach ([
        FulfillmentStatus::Pending,
        FulfillmentStatus::Arrived,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returning,
        FulfillmentStatus::Returned,
        FulfillmentStatus::Assigned,
    ] as $status) {
        $row = new OrderStatusHistory([
            'new_status' => $status->value,
            'comment' => $status->label(),
        ]);
        $row->created_at = now();
        $histories[] = $row;
    }

    $activity = OrderStatusTimeline::activitySections($histories);

    expect($activity['previous'])->toHaveCount(1)
        ->and(collect($activity['previous'][0])->pluck('key')->all())->toBe([
            'returned',
            'returning',
            'delivery_failed',
            'arrived',
            'pending',
        ])
        ->and(collect($activity['current'])->pluck('key')->all())->toBe([
            'assigned',
        ]);
});

test('activity sections stay unsplit while still returned', function () {
    $histories = [];

    foreach ([
        FulfillmentStatus::Arrived,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returned,
    ] as $status) {
        $row = new OrderStatusHistory([
            'new_status' => $status->value,
            'comment' => $status->label(),
        ]);
        $row->created_at = now();
        $histories[] = $row;
    }

    $activity = OrderStatusTimeline::activitySections($histories);

    expect($activity['previous'])->toBe([])
        ->and(collect($activity['current'])->pluck('key')->all())->toBe([
            'returned',
            'delivery_failed',
            'arrived',
        ]);
});

test('previous attempt rails include the closed return path after reassign', function () {
    $histories = [];

    foreach ([
        FulfillmentStatus::Pending,
        FulfillmentStatus::Placed,
        FulfillmentStatus::Ready,
        FulfillmentStatus::Assigned,
        FulfillmentStatus::Arrived,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returning,
        FulfillmentStatus::Returned,
        FulfillmentStatus::Assigned,
    ] as $status) {
        $row = new OrderStatusHistory([
            'new_status' => $status->value,
            'comment' => $status->label(),
        ]);
        $row->created_at = now();
        $histories[] = $row;
    }

    $previous = OrderStatusTimeline::previousAttemptRails($histories);
    $current = OrderStatusTimeline::steps(FulfillmentStatus::Assigned, $histories);
    $keys = collect($previous[0])->pluck('key')->all();

    expect($previous)->toHaveCount(1)
        ->and($keys)->toContain('delivery_failed')
        ->and($keys)->toContain('returning')
        ->and($keys)->toContain('returned')
        ->and(collect($previous[0])->every(fn (array $step): bool => $step['state'] === 'completed'))->toBeTrue()
        ->and(collect($current)->pluck('key')->all())->toBe([
            'assigned',
            'picked_up',
            'out_for_delivery',
            'arrived',
            'delivered',
        ]);
});

test('previous attempt rails are empty without a reattempt', function () {
    $histories = [];

    foreach ([
        FulfillmentStatus::Arrived,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returned,
    ] as $status) {
        $row = new OrderStatusHistory([
            'new_status' => $status->value,
            'comment' => $status->label(),
        ]);
        $row->created_at = now();
        $histories[] = $row;
    }

    expect(OrderStatusTimeline::previousAttemptRails($histories))->toBe([])
        ->and(OrderStatusTimeline::previousAttemptRails([
            new OrderStatusHistory(['new_status' => FulfillmentStatus::Assigned->value]),
        ]))->toBe([]);
});

test('customer previous attempt rails use milestones and stay completed', function () {
    $histories = [];

    foreach ([
        FulfillmentStatus::Placed,
        FulfillmentStatus::Ready,
        FulfillmentStatus::Assigned,
        FulfillmentStatus::Arrived,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returning,
        FulfillmentStatus::Returned,
        FulfillmentStatus::Assigned,
    ] as $status) {
        $row = new OrderStatusHistory([
            'new_status' => $status->value,
            'comment' => $status->label(),
        ]);
        $row->created_at = now();
        $histories[] = $row;
    }

    $previous = OrderStatusTimeline::previousAttemptRails($histories, true);
    $keys = collect($previous[0])->pluck('key')->all();

    expect($previous)->toHaveCount(1)
        ->and($keys)->toContain('out_for_delivery')
        ->and($keys)->toContain('arrived')
        ->and($keys)->toContain('delivery_failed')
        ->and($keys)->toContain('returning')
        ->and($keys)->toContain('returned')
        ->and(collect($previous[0])->every(fn (array $step): bool => $step['state'] === 'completed'))->toBeTrue();
});

test('two return cycles produce two previous rails and a short current rail', function () {
    $histories = [];

    foreach ([
        FulfillmentStatus::Pending,
        FulfillmentStatus::Assigned,
        FulfillmentStatus::Arrived,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returning,
        FulfillmentStatus::Returned,
        FulfillmentStatus::Assigned,
        FulfillmentStatus::Arrived,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returning,
        FulfillmentStatus::Returned,
        FulfillmentStatus::Assigned,
    ] as $status) {
        $row = new OrderStatusHistory([
            'new_status' => $status->value,
            'comment' => $status->label(),
        ]);
        $row->created_at = now();
        $histories[] = $row;
    }

    $previous = OrderStatusTimeline::previousAttemptRails($histories);
    $current = OrderStatusTimeline::steps(FulfillmentStatus::Assigned, $histories);

    expect($previous)->toHaveCount(2)
        ->and(collect($previous[0])->pluck('key')->all())->toContain('pending')
        ->and(collect($previous[0])->pluck('key')->all())->toContain('returned')
        ->and(collect($previous[1])->pluck('key')->all())->toContain('returned')
        ->and(collect($previous[1])->pluck('key')->all())->not->toContain('pending')
        ->and(collect($current)->pluck('key')->all())->toBe([
            'assigned',
            'picked_up',
            'out_for_delivery',
            'arrived',
            'delivered',
        ])
        ->and($current[0]['state'])->toBe('current');
});
