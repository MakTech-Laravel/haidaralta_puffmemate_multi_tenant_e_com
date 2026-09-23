<?php

use App\Support\DutyWindow;

it('treats duty windows as half open intervals', function () {
    expect(DutyWindow::timesOverlap('05:00', '15:00', '15:00', '22:00'))->toBeFalse()
        ->and(DutyWindow::timesOverlap('05:00', '15:00', '14:59', '22:00'))->toBeTrue()
        ->and(DutyWindow::timesOverlap('05:00', '23:00', '01:00', '05:30'))->toBeTrue();
});

it('rejects zero length and overnight windows', function () {
    expect(DutyWindow::isSameDayWindow('05:00', '05:00'))->toBeFalse()
        ->and(DutyWindow::isSameDayWindow('22:00', '05:00'))->toBeFalse();
});
