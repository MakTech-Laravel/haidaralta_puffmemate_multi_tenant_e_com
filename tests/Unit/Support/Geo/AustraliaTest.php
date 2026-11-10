<?php

use App\Support\Geo\Australia;

test('contains accepts mainland and tasmania coordinates', function () {
    expect(Australia::contains(-33.8688, 151.2093))->toBeTrue()
        ->and(Australia::contains(-27.4689623, 153.0235009))->toBeTrue()
        ->and(Australia::contains(-42.8821, 147.3272))->toBeTrue();
});

test('contains rejects pins outside Australia including northern hemisphere mistakes', function () {
    expect(Australia::contains(27.4689623, 153.0235009))->toBeFalse()
        ->and(Australia::contains(40.7128, -74.0060))->toBeFalse()
        ->and(Australia::contains(-41.2865, 174.7762))->toBeFalse();
});
