<?php

use App\Services\Order\Allocation\DeliverySLACalculator;

test('express uses the express sla config', function () {
    config([
        'catalog.express_max_transfer_hours' => 4,
        'catalog.regular_max_transfer_hours' => 72,
    ]);

    $sla = app(DeliverySLACalculator::class);

    expect($sla->maxHours('express'))->toBe(4.0)
        ->and($sla->maxHours('regular'))->toBe(72.0)
        ->and($sla->maxHours('same-day'))->toBe(4.0)
        ->and($sla->maxHours('australia-wide'))->toBe(72.0)
        ->and($sla->maxHours(null))->toBe(72.0);
});

test('withinSla is a plain <= comparison against the resolved max', function () {
    config(['catalog.express_max_transfer_hours' => 4]);

    $sla = app(DeliverySLACalculator::class);

    expect($sla->withinSla(4.0, 'express'))->toBeTrue()
        ->and($sla->withinSla(4.01, 'express'))->toBeFalse();
});
