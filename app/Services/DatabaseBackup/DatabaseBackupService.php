<?php

namespace App\Services\DatabaseBackup;

use App\Enums\DatabaseBackupStatus;
use App\Enums\DatabaseBackupType;
use App\Models\Branch;
use App\Models\DatabaseBackupHistory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\DbDumper\Databases\MySql;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupService
{
    public const DISK = 'local';

    public const DIRECTORY = 'database-backups';

    /**
     * Tables excluded from SQL dumps so backup history survives restores.
     *
     * @var list<string>
     */
    private const EXCLUDED_TABLES = [
        'database_backup_histories',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
    ];

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateHistory(int $perPage = 20): LengthAwarePaginator
    {
        $this->ensureExcludedTablesExist((string) config('database.default'));

        return DatabaseBackupHistory::query()
            ->with(['triggeredBy:id,name,email'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (DatabaseBackupHistory $history): array => $this->toListRow($history));
    }

    /**
     * @return array<string, mixed>
     */
    public function toListRow(DatabaseBackupHistory $history): array
    {
        return [
            'id' => $history->id,
            'type' => $history->type->value,
            'status' => $history->status->value,
            'filename' => $history->filename,
            'size_bytes' => $history->size_bytes,
            'connection' => $history->connection,
            'message' => $history->message,
            'source_history_id' => $history->source_history_id,
            'can_download' => $history->hasDownloadableFile()
                && Storage::disk($history->disk)->exists((string) $history->path),
            'can_restore' => $history->type === DatabaseBackupType::Export
                && $history->hasDownloadableFile()
                && Storage::disk($history->disk)->exists((string) $history->path),
            'triggered_by' => $history->triggeredBy === null ? null : [
                'id' => $history->triggeredBy->id,
                'name' => $history->triggeredBy->name,
                'email' => $history->triggeredBy->email,
            ],
            'created_at' => $history->created_at?->toIso8601String(),
        ];
    }

    public function export(?User $user): DatabaseBackupHistory
    {
        $connection = (string) config('database.default');
        $this->ensureExcludedTablesExist($connection);
        $filename = 'export-'.now()->format('Y-m-d-His').'-'.Str::lower(Str::random(8)).'.sql';
        $relativePath = self::DIRECTORY.'/'.$filename;

        $history = DatabaseBackupHistory::query()->create([
            'type' => DatabaseBackupType::Export,
            'status' => DatabaseBackupStatus::Running,
            'disk' => self::DISK,
            'path' => $relativePath,
            'filename' => $filename,
            'triggered_by' => $user?->id,
            'connection' => $connection,
            'message' => 'Export running',
        ]);

        try {
            Storage::disk(self::DISK)->makeDirectory(self::DIRECTORY);

            $absolutePath = Storage::disk(self::DISK)->path($relativePath);
            $this->dumpToFile($connection, $absolutePath);

            $history->update([
                'status' => DatabaseBackupStatus::Completed,
                'size_bytes' => Storage::disk(self::DISK)->size($relativePath),
                'message' => 'Export completed',
            ]);
        } catch (Throwable $exception) {
            $this->ensureExcludedTablesExist($connection);

            $history->update([
                'status' => DatabaseBackupStatus::Failed,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $history->fresh(['triggeredBy']) ?? $history;
    }

    public function importUpload(UploadedFile $file, ?User $user): DatabaseBackupHistory
    {
        $connection = (string) config('database.default');
        $this->ensureExcludedTablesExist($connection);
        $filename = 'import-'.now()->format('Y-m-d-His').'-'.Str::lower(Str::random(8)).'.sql';
        $relativePath = self::DIRECTORY.'/'.$filename;

        $history = DatabaseBackupHistory::query()->create([
            'type' => DatabaseBackupType::Import,
            'status' => DatabaseBackupStatus::Running,
            'disk' => self::DISK,
            'path' => $relativePath,
            'filename' => $filename,
            'triggered_by' => $user?->id,
            'connection' => $connection,
            'message' => 'Import running',
        ]);

        try {
            Storage::disk(self::DISK)->makeDirectory(self::DIRECTORY);
            Storage::disk(self::DISK)->putFileAs(self::DIRECTORY, $file, $filename);

            $absolutePath = Storage::disk(self::DISK)->path($relativePath);
            $this->restoreFromFile($connection, $absolutePath);

            $history->update([
                'status' => DatabaseBackupStatus::Completed,
                'size_bytes' => Storage::disk(self::DISK)->size($relativePath),
                'message' => 'Import completed',
            ]);
        } catch (Throwable $exception) {
            $this->ensureExcludedTablesExist($connection);

            $history->update([
                'status' => DatabaseBackupStatus::Failed,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $history->fresh(['triggeredBy']) ?? $history;
    }

    public function restoreFromHistory(DatabaseBackupHistory $source, ?User $user): DatabaseBackupHistory
    {
        if (! $source->hasDownloadableFile() || ! Storage::disk($source->disk)->exists((string) $source->path)) {
            throw new RuntimeException('Backup file is missing or not downloadable.');
        }

        if ($source->type !== DatabaseBackupType::Export) {
            throw new RuntimeException('Only completed export backups can be restored.');
        }

        $connection = (string) config('database.default');
        $this->ensureExcludedTablesExist($connection);
        $filename = 'restore-'.now()->format('Y-m-d-His').'-'.Str::lower(Str::random(8)).'.sql';
        $relativePath = self::DIRECTORY.'/'.$filename;

        $history = DatabaseBackupHistory::query()->create([
            'type' => DatabaseBackupType::Import,
            'status' => DatabaseBackupStatus::Running,
            'disk' => self::DISK,
            'path' => $relativePath,
            'filename' => $filename,
            'triggered_by' => $user?->id,
            'source_history_id' => $source->id,
            'connection' => $connection,
            'message' => 'Restore running',
        ]);

        try {
            Storage::disk(self::DISK)->makeDirectory(self::DIRECTORY);
            Storage::disk(self::DISK)->copy((string) $source->path, $relativePath);

            $absolutePath = Storage::disk(self::DISK)->path($relativePath);
            $this->restoreFromFile($connection, $absolutePath);

            $history->update([
                'status' => DatabaseBackupStatus::Completed,
                'size_bytes' => Storage::disk(self::DISK)->size($relativePath),
                'message' => 'Restore completed from #'.$source->id,
            ]);
        } catch (Throwable $exception) {
            $this->ensureExcludedTablesExist($connection);

            $history->update([
                'status' => DatabaseBackupStatus::Failed,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $history->fresh(['triggeredBy']) ?? $history;
    }

    public function absolutePath(DatabaseBackupHistory $history): string
    {
        if (! $history->hasDownloadableFile()) {
            throw new RuntimeException('Backup file is not available.');
        }

        return Storage::disk($history->disk)->path((string) $history->path);
    }

    private function dumpToFile(string $connection, string $absolutePath): void
    {
        $driver = (string) config("database.connections.{$connection}.driver");

        match ($driver) {
            'pgsql' => $this->dumpPostgreSql($connection, $absolutePath),
            'mysql', 'mariadb' => $this->dumpMySql($connection, $absolutePath),
            'sqlite' => $this->dumpSqlite($connection, $absolutePath),
            default => throw new RuntimeException("Unsupported database driver [{$driver}] for export."),
        };
    }

    private function restoreFromFile(string $connection, string $absolutePath): void
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException('SQL file not found.');
        }

        $driver = (string) config("database.connections.{$connection}.driver");

        match ($driver) {
            'pgsql' => $this->restorePostgreSql($connection, $absolutePath),
            'mysql', 'mariadb' => $this->restoreMySql($connection, $absolutePath),
            'sqlite' => $this->restoreSqlite($connection, $absolutePath),
            default => throw new RuntimeException("Unsupported database driver [{$driver}] for import."),
        };

        // Dumps from older environments may omit newer permissions (e.g. manage-database-backups).
        $this->resyncRolesAndPermissions();
    }

    /**
     * Re-apply current app role/permission definitions after a restore.
     */
    private function resyncRolesAndPermissions(): void
    {
        try {
            $seeder = app(RolesAndPermissionsSeeder::class);
            $seeder->run();

            if (Schema::hasTable('branches')) {
                Branch::query()
                    ->orderBy('id')
                    ->each(fn (Branch $branch) => $seeder->seedBranchRolesForBranch($branch));
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function dumpPostgreSql(string $connection, string $absolutePath): void
    {
        try {
            $this->dumpPostgreSqlWithPgDump($connection, $absolutePath);
        } catch (Throwable $exception) {
            report($exception);
            $this->dumpPostgreSqlNative($connection, $absolutePath);
        }
    }

    private function dumpPostgreSqlWithPgDump(string $connection, string $absolutePath): void
    {
        $config = config("database.connections.{$connection}");
        $binaryDir = $this->resolvePostgresBinaryPath();
        $pgDump = $binaryDir !== null
            ? rtrim($binaryDir, '/\\').DIRECTORY_SEPARATOR.'pg_dump'
            : 'pg_dump';

        if (PHP_OS_FAMILY === 'Windows' && ! str_ends_with(strtolower($pgDump), '.exe')) {
            $pgDump .= '.exe';
        }

        if (! is_file($pgDump) && $pgDump !== 'pg_dump') {
            throw new RuntimeException('pg_dump binary was not found at '.$pgDump);
        }

        // Ensure the target file exists so realpath() can normalize Windows paths.
        touch($absolutePath);
        $normalizedPath = realpath($absolutePath) ?: $absolutePath;

        $command = [
            $pgDump,
            '--host='.(string) ($config['host'] ?? '127.0.0.1'),
            '--port='.(string) ($config['port'] ?? '5432'),
            '--username='.(string) ($config['username'] ?? ''),
            '--dbname='.(string) ($config['database'] ?? ''),
            '--inserts',
            '--restrict-key='.bin2hex(random_bytes(16)),
            '--file='.$normalizedPath,
            '--no-owner',
            '--no-acl',
        ];

        foreach (self::EXCLUDED_TABLES as $table) {
            $command[] = '--exclude-table='.$table;
        }

        $process = new Process(
            $command,
            base_path(),
            $this->processEnvironment([
                'PGPASSWORD' => (string) ($config['password'] ?? ''),
            ]),
        );
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $details = trim($process->getErrorOutput()."\n".$process->getOutput());

            throw new RuntimeException(
                'pg_dump failed (exit '.$process->getExitCode().'): '.($details !== '' ? $details : 'no output')
            );
        }

        clearstatcache(true, $normalizedPath);

        if (! is_file($normalizedPath) || filesize($normalizedPath) === 0) {
            throw new RuntimeException('PostgreSQL dump file was not created or is empty.');
        }
    }

    /**
     * Portable SQL dump via Laravel DB (no pg_dump). Safe for Windows web workers.
     */
    private function dumpPostgreSqlNative(string $connection, string $absolutePath): void
    {
        $db = DB::connection($connection);

        $tables = collect($db->select(<<<'SQL'
            SELECT tablename
            FROM pg_catalog.pg_tables
            WHERE schemaname = 'public'
            ORDER BY tablename
            SQL))
            ->pluck('tablename')
            ->reject(fn (mixed $name): bool => in_array((string) $name, self::EXCLUDED_TABLES, true))
            ->values()
            ->all();

        $handle = fopen($absolutePath, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open dump file for writing.');
        }

        try {
            fwrite($handle, "-- Native PostgreSQL dump generated by DatabaseBackupService\n");
            fwrite($handle, "BEGIN;\nSET session_replication_role = replica;\n");

            foreach ($tables as $table) {
                $quotedTable = '"'.str_replace('"', '""', (string) $table).'"';
                fwrite($handle, 'TRUNCATE TABLE '.$quotedTable." RESTART IDENTITY CASCADE;\n");

                $orderColumn = $this->postgresPrimaryOrderColumn($connection, (string) $table);
                $query = $db->table($table);

                $rows = $orderColumn === 'ctid'
                    ? $query->orderByRaw('ctid')->get()
                    : $query->orderBy($orderColumn)->get();

                foreach ($rows as $row) {
                    $pdo = $db->getPdo();
                    $attributes = (array) $row;

                    if ($attributes === []) {
                        continue;
                    }

                    $columns = collect(array_keys($attributes))
                        ->map(fn (string|int $column): string => '"'.str_replace('"', '""', (string) $column).'"')
                        ->implode(', ');

                    $values = collect($attributes)->map(function (mixed $value) use ($pdo): string {
                        if ($value === null) {
                            return 'NULL';
                        }

                        if (is_bool($value)) {
                            return $value ? 'TRUE' : 'FALSE';
                        }

                        if (is_int($value) || is_float($value)) {
                            return (string) $value;
                        }

                        if (is_array($value) || is_object($value)) {
                            return $pdo->quote((string) json_encode($value));
                        }

                        return $pdo->quote((string) $value);
                    })->implode(', ');

                    fwrite($handle, 'INSERT INTO '.$quotedTable.' ('.$columns.') VALUES ('.$values.");\n");
                }
            }

            fwrite($handle, "SET session_replication_role = DEFAULT;\nCOMMIT;\n");
        } finally {
            fclose($handle);
        }

        clearstatcache(true, $absolutePath);

        if (! is_file($absolutePath) || filesize($absolutePath) === 0) {
            throw new RuntimeException('Native PostgreSQL dump file was not created or is empty.');
        }
    }

    private function postgresPrimaryOrderColumn(string $connection, string $table): string
    {
        $column = DB::connection($connection)->selectOne(<<<'SQL'
            SELECT a.attname AS column_name
            FROM pg_index i
            JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY (i.indkey)
            WHERE i.indrelid = ?::regclass
              AND i.indisprimary
            ORDER BY a.attnum
            LIMIT 1
            SQL, [$table]);

        return is_string($column?->column_name ?? null) && $column->column_name !== ''
            ? $column->column_name
            : 'ctid';
    }

    private function dumpMySql(string $connection, string $absolutePath): void
    {
        $config = config("database.connections.{$connection}");

        MySql::create()
            ->setHost((string) ($config['host'] ?? '127.0.0.1'))
            ->setPort((string) ($config['port'] ?? '3306'))
            ->setDbName((string) ($config['database'] ?? ''))
            ->setUserName((string) ($config['username'] ?? ''))
            ->setPassword((string) ($config['password'] ?? ''))
            ->excludeTables(self::EXCLUDED_TABLES)
            ->dumpToFile($absolutePath);
    }

    private function dumpSqlite(string $connection, string $absolutePath): void
    {
        $db = DB::connection($connection);
        $tables = collect($db->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
            ->pluck('name')
            ->reject(fn (mixed $name): bool => in_array((string) $name, self::EXCLUDED_TABLES, true))
            ->values()
            ->all();

        $handle = fopen($absolutePath, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open dump file for writing.');
        }

        try {
            fwrite($handle, "PRAGMA foreign_keys=OFF;\nBEGIN;\n");

            foreach ($tables as $table) {
                $create = $db->selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $table]);

                if ($create?->sql) {
                    fwrite($handle, 'DROP TABLE IF EXISTS "'.$table.'";'."\n");
                    fwrite($handle, $create->sql.";\n");
                }

                $rows = $db->table($table)->get();

                foreach ($rows as $row) {
                    $pdo = $db->getPdo();
                    $values = collect((array) $row)->map(function (mixed $value) use ($pdo): string {
                        if ($value === null) {
                            return 'NULL';
                        }

                        if (is_int($value) || is_float($value)) {
                            return (string) $value;
                        }

                        return $pdo->quote((string) $value);
                    })->implode(', ');

                    $columns = collect((array) $row)
                        ->keys()
                        ->map(fn (string|int $column): string => '"'.$column.'"')
                        ->implode(', ');

                    fwrite($handle, "INSERT INTO \"{$table}\" ({$columns}) VALUES ({$values});\n");
                }
            }

            fwrite($handle, "COMMIT;\nPRAGMA foreign_keys=ON;\n");
        } finally {
            fclose($handle);
        }
    }

    private function restorePostgreSql(string $connection, string $absolutePath): void
    {
        $rawSql = file_get_contents($absolutePath);
        $preview = is_string($rawSql) ? $this->sanitizeSqlDump($rawSql) : '';
        $isFullDump = $this->isFullPostgresDump($preview);
        $historyRows = $isFullDump ? $this->snapshotBackupHistoryRows($connection) : [];

        try {
            if ($isFullDump) {
                $this->resetPublicSchema($connection);
            }

            try {
                $this->restorePostgreSqlWithPsql($connection, $absolutePath);
            } catch (Throwable $exception) {
                report($exception);
                $this->restoreSqlViaDatabase($connection, $absolutePath, schemaAlreadyReset: $isFullDump);
            }
        } finally {
            if ($isFullDump) {
                $this->ensureExcludedTablesExist($connection);
                $this->restoreBackupHistoryRows($connection, $historyRows);
            }
        }
    }

    private function restorePostgreSqlWithPsql(string $connection, string $absolutePath): void
    {
        $config = config("database.connections.{$connection}");
        $binaryDir = $this->resolvePostgresBinaryPath();
        $psql = $binaryDir !== null
            ? rtrim($binaryDir, '/\\').DIRECTORY_SEPARATOR.'psql'
            : 'psql';

        if (PHP_OS_FAMILY === 'Windows' && ! str_ends_with(strtolower($psql), '.exe')) {
            $psql .= '.exe';
        }

        $normalizedPath = realpath($absolutePath) ?: $absolutePath;

        $process = new Process([
            $psql,
            '-h', (string) ($config['host'] ?? '127.0.0.1'),
            '-p', (string) ($config['port'] ?? '5432'),
            '-U', (string) ($config['username'] ?? ''),
            '-d', (string) ($config['database'] ?? ''),
            '-v', 'ON_ERROR_STOP=1',
            '-f', $normalizedPath,
        ], base_path(), $this->processEnvironment([
            'PGPASSWORD' => (string) ($config['password'] ?? ''),
        ]));

        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'PostgreSQL restore failed.'
            );
        }
    }

    private function restoreSqlViaDatabase(string $connection, string $absolutePath, bool $schemaAlreadyReset = false): void
    {
        $sql = file_get_contents($absolutePath);

        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('SQL file is empty or unreadable.');
        }

        $sql = $this->sanitizeSqlDump($sql);

        if ($sql === '') {
            return;
        }

        if (preg_match('/^\s*COPY\s+.+\s+FROM\s+stdin\s*;/mi', $sql) === 1) {
            throw new RuntimeException(
                'This dump uses COPY FROM stdin and requires the psql client. Ensure PostgreSQL bin is installed and reachable.'
            );
        }

        $driver = (string) config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql' && $this->isFullPostgresDump($sql) && ! $schemaAlreadyReset) {
            $this->resetPublicSchema($connection);
        }

        DB::connection($connection)->unprepared($sql);
    }

    /**
     * Remove client-only / wrapper directives so PDO can execute the dump.
     */
    private function sanitizeSqlDump(string $sql): string
    {
        // PG 17+/18 pg_dump meta-commands (psql-only).
        $sql = (string) preg_replace('/^\s*\\\\(?:un)?restrict\b.*$/mi', '', $sql);

        // Other common psql meta-commands that are not SQL.
        $sql = (string) preg_replace('/^\s*\\\\(?!N\b).*$/mi', '', $sql);

        $sql = (string) preg_replace('/^\s*BEGIN\s*;\s*/mi', '', $sql);
        $sql = (string) preg_replace('/^\s*COMMIT\s*;\s*/mi', '', $sql);

        return trim($sql);
    }

    private function isFullPostgresDump(string $sql): bool
    {
        if (str_contains($sql, 'Native PostgreSQL dump')) {
            return false;
        }

        return str_contains($sql, 'PostgreSQL database dump')
            || str_contains($sql, 'CREATE TABLE public.');
    }

    private function resetPublicSchema(string $connection): void
    {
        $username = (string) (config("database.connections.{$connection}.username") ?: 'postgres');

        DB::connection($connection)->unprepared('DROP SCHEMA IF EXISTS __backup_preserve CASCADE');

        DB::connection($connection)->unprepared(<<<SQL
            DROP SCHEMA IF EXISTS public CASCADE;
            CREATE SCHEMA public;
            GRANT ALL ON SCHEMA public TO PUBLIC;
            GRANT ALL ON SCHEMA public TO "{$username}";
            SQL);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function snapshotBackupHistoryRows(string $connection): array
    {
        if (! Schema::connection($connection)->hasTable('database_backup_histories')) {
            return [];
        }

        return DB::connection($connection)
            ->table('database_backup_histories')
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function restoreBackupHistoryRows(string $connection, array $rows): void
    {
        if ($rows === [] || ! Schema::connection($connection)->hasTable('database_backup_histories')) {
            return;
        }

        $db = DB::connection($connection);

        $db->table('database_backup_histories')->delete();

        foreach (array_chunk($rows, 100) as $chunk) {
            $db->table('database_backup_histories')->insert($chunk);
        }

        if ((string) config("database.connections.{$connection}.driver") === 'pgsql') {
            $maxId = (int) $db->table('database_backup_histories')->max('id');
            $db->unprepared(
                'SELECT setval(pg_get_serial_sequence(\'database_backup_histories\', \'id\'), '.max($maxId, 1).', '.($maxId > 0 ? 'true' : 'false').')'
            );
        }
    }

    /**
     * Recreate excluded infra tables wiped by a full schema restore.
     */
    public function ensureExcludedTablesExist(?string $connection = null): void
    {
        $connection ??= (string) config('database.default');
        $schema = Schema::connection($connection);
        $usersExist = $schema->hasTable('users');

        if (! $schema->hasTable('database_backup_histories')) {
            $schema->create('database_backup_histories', function (Blueprint $table) use ($usersExist): void {
                $table->id();
                $table->string('type');
                $table->string('status');
                $table->string('disk')->default('local');
                $table->string('path')->nullable();
                $table->string('filename')->nullable();
                $table->unsignedBigInteger('size_bytes')->nullable();

                if ($usersExist) {
                    $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('triggered_by')->nullable();
                }

                $table->unsignedBigInteger('source_history_id')->nullable();
                $table->string('connection')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();

                $table->index(['type', 'status']);
                $table->index('created_at');
            });

            if ($schema->hasTable('database_backup_histories')) {
                try {
                    $schema->table('database_backup_histories', function (Blueprint $table): void {
                        $table->foreign('source_history_id')
                            ->references('id')
                            ->on('database_backup_histories')
                            ->nullOnDelete();
                    });
                } catch (Throwable) {
                    // Self-FK may already exist depending on driver quirks.
                }
            }
        }

        if (! $schema->hasTable('cache')) {
            $schema->create('cache', function (Blueprint $table): void {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->bigInteger('expiration')->index();
            });
        }

        if (! $schema->hasTable('cache_locks')) {
            $schema->create('cache_locks', function (Blueprint $table): void {
                $table->string('key')->primary();
                $table->string('owner');
                $table->bigInteger('expiration')->index();
            });
        }

        if (! $schema->hasTable('jobs')) {
            $schema->create('jobs', function (Blueprint $table): void {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedSmallInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! $schema->hasTable('job_batches')) {
            $schema->create('job_batches', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (! $schema->hasTable('failed_jobs')) {
            $schema->create('failed_jobs', function (Blueprint $table): void {
                $table->id();
                $table->string('uuid')->unique();
                $table->string('connection');
                $table->string('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();

                $table->index(['connection', 'queue', 'failed_at']);
            });
        }

        if (! $schema->hasTable('sessions')) {
            $schema->create('sessions', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    private function restoreMySql(string $connection, string $absolutePath): void
    {
        $config = config("database.connections.{$connection}");
        $sql = file_get_contents($absolutePath);

        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('SQL file is empty or unreadable.');
        }

        $process = new Process([
            'mysql',
            '--host='.(string) ($config['host'] ?? '127.0.0.1'),
            '--port='.(string) ($config['port'] ?? '3306'),
            '--user='.(string) ($config['username'] ?? ''),
            '--password='.(string) ($config['password'] ?? ''),
            (string) ($config['database'] ?? ''),
        ]);

        $process->setInput($sql);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'MySQL restore failed.');
        }
    }

    private function restoreSqlite(string $connection, string $absolutePath): void
    {
        $this->restoreSqlViaDatabase($connection, $absolutePath);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function processEnvironment(array $overrides = []): array
    {
        $env = [];

        foreach (array_merge($_SERVER, $_ENV) as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $env[$key] = $value;
            }
        }

        foreach ([
            'PATH',
            'Path',
            'SystemRoot',
            'SYSTEMROOT',
            'WINDIR',
            'TEMP',
            'TMP',
            'USERPROFILE',
            'HOMEDRIVE',
            'HOMEPATH',
            'PATHEXT',
            'ComSpec',
            'COMSPEC',
        ] as $key) {
            $value = getenv($key);

            if (is_string($value) && $value !== '') {
                $env[$key] = $value;
            }
        }

        return array_merge($env, $overrides);
    }

    /**
     * Resolve PostgreSQL client bin directory for dump/restore when PATH is incomplete.
     */
    private function resolvePostgresBinaryPath(): ?string
    {
        $configured = config('database.connections.pgsql.dump.dump_binary_path')
            ?? env('POSTGRES_BINARY_PATH')
            ?? env('PG_DUMP_PATH');

        if (is_string($configured) && $configured !== '') {
            $configured = rtrim(str_replace('\\', '/', $configured), '/');

            if (is_dir($configured)) {
                return $configured;
            }

            if (is_file($configured)) {
                return rtrim(str_replace('\\', '/', dirname($configured)), '/');
            }
        }

        $fromPath = $this->findExecutableDirectory('pg_dump');

        if ($fromPath !== null) {
            return $fromPath;
        }

        $candidates = [
            'C:/Program Files/PostgreSQL/18/bin',
            'C:/Program Files/PostgreSQL/17/bin',
            'C:/Program Files/PostgreSQL/16/bin',
            'C:/Program Files/PostgreSQL/15/bin',
            'C:/Program Files/PostgreSQL/14/bin',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate.'/pg_dump.exe') || is_file($candidate.'/pg_dump')) {
                return $candidate;
            }
        }

        return null;
    }

    private function findExecutableDirectory(string $binary): ?string
    {
        $command = PHP_OS_FAMILY === 'Windows'
            ? ['where.exe', $binary]
            : ['which', $binary];

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $firstLine = trim(explode("\n", str_replace("\r\n", "\n", $process->getOutput()))[0] ?? '');

        if ($firstLine === '' || ! is_file($firstLine)) {
            return null;
        }

        return rtrim(str_replace('\\', '/', dirname($firstLine)), '/');
    }
}
