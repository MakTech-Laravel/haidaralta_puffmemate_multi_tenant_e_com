<?php

namespace App\Services\Backup;

/**
 * Keeps operator history + Reverb payloads small (failed Spatie runs can dump full stack traces).
 */
final class BackupHistoryMessage
{
    public const MAX_LENGTH = 500;

    public static function summarize(string $message): string
    {
        $normalized = preg_replace("/\r\n?/", "\n", trim($message)) ?? '';

        if ($normalized === '') {
            return '';
        }

        if (preg_match('/pg_dump: detail:\s*(.+)/i', $normalized, $matches) === 1) {
            $normalized = 'Backup failed: '.trim($matches[1]);
        } elseif (preg_match('/pg_dump: error:\s*(.+)/i', $normalized, $matches) === 1) {
            $normalized = 'Backup failed: '.trim($matches[1]);
        } else {
            $lines = [];

            foreach (explode("\n", $normalized) as $line) {
                $line = trim($line);

                if ($line === '' || $line === '========' || $line === '<no output>') {
                    continue;
                }

                if (str_starts_with($line, '#')
                    || str_starts_with($line, 'Exitcode')
                    || str_starts_with($line, 'Output')
                    || str_starts_with($line, 'Error Output')
                    || str_starts_with($line, '{')
                    || str_starts_with($line, '"exception"')
                ) {
                    continue;
                }

                $lines[] = $line;
            }

            $normalized = preg_replace('/\s+/', ' ', implode(' ', $lines)) ?? '';
        }

        if (mb_strlen($normalized) <= self::MAX_LENGTH) {
            return $normalized;
        }

        return mb_substr($normalized, 0, self::MAX_LENGTH - 1).'…';
    }
}
