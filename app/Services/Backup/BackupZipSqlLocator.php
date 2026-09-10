<?php

namespace App\Services\Backup;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Locates the Spatie database dump SQL inside an extracted backup zip.
 *
 * Spatie also archives storage/app, which may contain unrelated .sql files.
 * Always prefer db-dumps/ and the largest candidate.
 */
final class BackupZipSqlLocator
{
    public function find(string $extractDirectory): string
    {
        $candidates = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractDirectory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'sql') {
                continue;
            }

            $candidates[] = $file->getPathname();
        }

        if ($candidates === []) {
            throw new RuntimeException('No SQL file found inside backup zip.');
        }

        $dbDumps = array_values(array_filter(
            $candidates,
            fn (string $path): bool => str_contains(str_replace('\\', '/', $path), '/db-dumps/'),
        ));

        $pool = $dbDumps !== [] ? $dbDumps : $candidates;

        usort(
            $pool,
            fn (string $left, string $right): int => (filesize($right) ?: 0) <=> (filesize($left) ?: 0),
        );

        return $pool[0];
    }
}
