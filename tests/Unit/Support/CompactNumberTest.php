<?php

use App\Support\CompactNumber;

test('compact numbers stay exact below one thousand', function () {
    expect(CompactNumber::format(0))->toBe('0')
        ->and(CompactNumber::format(999))->toBe('999');
});

test('compact numbers use k m and b with one decimal when needed', function (int $number, string $label) {
    expect(CompactNumber::format($number))->toBe($label);
})->with([
    [1000, '1k'],
    [1200, '1.2k'],
    [1500, '1.5k'],
    [1_000_000, '1m'],
    [1_500_000, '1.5m'],
    [1_000_000_000, '1b'],
    [999_999, '1m'],
]);
