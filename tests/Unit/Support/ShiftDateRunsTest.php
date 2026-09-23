<?php

use App\Support\ShiftDateRuns;

it('normalizes dates and partitions consecutive runs across month boundaries', function () {
    expect(ShiftDateRuns::partition([
        '2026-11-02',
        '2026-10-31',
        '2026-11-01',
        '2026-11-04',
        '2026-11-04',
    ]))->toBe([
        [
            'start_date' => '2026-10-31',
            'end_date' => '2026-11-02',
            'dates' => ['2026-10-31', '2026-11-01', '2026-11-02'],
        ],
        [
            'start_date' => '2026-11-04',
            'end_date' => '2026-11-04',
            'dates' => ['2026-11-04'],
        ],
    ])->and(ShiftDateRuns::inclusiveSpan([
        '2026-10-31',
        '2026-11-04',
    ]))->toBe(5);
});

it('rejects dates that are not strict calendar dates', function (string $date) {
    expect(fn () => ShiftDateRuns::normalize([$date]))
        ->toThrow(InvalidArgumentException::class, 'Shift dates must use the Y-m-d format.');
})->with([
    'non-padded date' => '2026-9-1',
    'impossible date' => '2026-02-30',
    'date time' => '2026-09-22 08:00:00',
]);

it('compresses month dates into day-of-month run labels', function () {
    expect(ShiftDateRuns::compactMonthDayLabel([
        '2026-09-01',
        '2026-09-02',
        '2026-09-03',
        '2026-09-05',
        '2026-09-10',
        '2026-09-11',
        '2026-09-13',
    ]))->toBe('1-3,5,10-11,13')
        ->and(ShiftDateRuns::compactMonthDayLabel([]))->toBe('')
        ->and(ShiftDateRuns::compactMonthDayLabel(['2026-09-22']))->toBe('22');
});
