<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

test('pulse card cache objects survive a serialize round trip', function () {
    $store = Cache::store('file');
    $key = 'pulse-serializable-classes-smoke';

    $store->forget($key);

    $payload = collect([(object) ['key' => '1', 'count' => 2]])
        ->push(CarbonImmutable::parse('2026-09-10 12:00:00'));

    $store->put($key, $payload, 60);

    $restored = $store->get($key);

    expect($restored)->toBeInstanceOf(Collection::class)
        ->and($restored->first())->toBeInstanceOf(stdClass::class)
        ->and($restored->last())->toBeInstanceOf(CarbonImmutable::class)
        ->and($restored->first()->count)->toBe(2);

    $store->forget($key);
});
