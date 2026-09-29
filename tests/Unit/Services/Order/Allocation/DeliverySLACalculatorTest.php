<?php

use App\Services\Order\Allocation\DeliverySLACalculator;

test('same-day uses the same-day sla config', function () {
    config(['catalog.same_day_max_transfer_hours' => 4, 'catalog.australia_wide_max_transfer_hours' => 72]);

    $sla = app(DeliverySLACalculator::class);

    expect($sla->maxHours('same-day'))->toBe(4.0)
        ->and($sla->maxHours('australia-wide'))->toBe(72.0)
        ->and($sla->maxHours(null))->toBe(72.0);
});

test('withinSla is a plain <= comparison against the resolved max', function () {
    config(['catalog.same_day_max_transfer_hours' => 4]);

    $sla = app(DeliverySLACalculator::class);

    expect($sla->withinSla(4.0, 'same-day'))->toBeTrue()
        ->and($sla->withinSla(4.01, 'same-day'))->toBeFalse();
});
