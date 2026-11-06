<?php

use App\Support\Geo\EncodedPolyline;

test('it decodes a google encoded polyline into latitude longitude pairs', function () {
    $points = EncodedPolyline::decode('_p~iF~ps|U_ulLnnqC_mqNvxq`@');

    expect($points)->toHaveCount(3)
        ->and($points[0])->toEqual([38.5, -120.2])
        ->and($points[1])->toEqual([40.7, -120.95])
        ->and($points[2])->toEqual([43.252, -126.453]);
});

test('an empty polyline decodes to no points', function () {
    expect(EncodedPolyline::decode(''))->toBe([]);
});
