<?php

use App\Services\Backup\BackupZipSqlLocator;

test('backup zip sql locator prefers db-dumps over storage sql decoys', function () {
    $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('sql-locate-', true);
    $dbDumps = $root.DIRECTORY_SEPARATOR.'PuffMeMate'.DIRECTORY_SEPARATOR.'db-dumps';
    $storage = $root.DIRECTORY_SEPARATOR.'PuffMeMate'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'private';
    mkdir($dbDumps, 0777, true);
    mkdir($storage, 0777, true);

    $decoy = $storage.DIRECTORY_SEPARATOR.'aaa-decoy.sql';
    $real = $dbDumps.DIRECTORY_SEPARATOR.'postgresql-postgres.sql';

    file_put_contents($decoy, "-- PostgreSQL database dump\nCREATE TABLE public.foo (id int);\n");
    file_put_contents($real, "-- PostgreSQL database dump\nCREATE TABLE public.users (\n    id bigint NOT NULL\n);\n");

    $path = (new BackupZipSqlLocator)->find($root);

    expect(str_replace('\\', '/', $path))->toEndWith('/db-dumps/postgresql-postgres.sql');

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($root);
});
