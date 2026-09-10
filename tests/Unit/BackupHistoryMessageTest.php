<?php

use App\Services\Backup\BackupHistoryMessage;

test('backup history message prefers pg_dump detail and truncates long text', function () {
    $raw = str_repeat('x', 800)."\npg_dump: detail: server version: 18.6; pg_dump version: 15.19\n".str_repeat('y', 800);

    $summary = BackupHistoryMessage::summarize($raw);

    expect($summary)->toBe('Backup failed: server version: 18.6; pg_dump version: 15.19')
        ->and(mb_strlen($summary))->toBeLessThanOrEqual(BackupHistoryMessage::MAX_LENGTH);
});

test('backup history message truncates generic long failures', function () {
    $raw = str_repeat('Backup noise ', 80);

    $summary = BackupHistoryMessage::summarize($raw);

    expect(mb_strlen($summary))->toBe(BackupHistoryMessage::MAX_LENGTH)
        ->and($summary)->toEndWith('…');
});
