<?php

namespace App\Services\Backup;

use App\Models\Branch;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseRestoreRunner
{
    /**
     * Tables whose rows are snapshotted before a full PostgreSQL schema reset.
     *
     * @var list<string>
     */
    private const PRESERVED_TABLES = [
        'database_backup_histories',
        'activity_log',
    ];

    public function restoreFromSqlFile(string $connection, string $absolutePath): void
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

        $this->resyncRolesAndPermissions();
    }

    /**
     * Re-apply current app role/permission definitions after a restore.
     */
    public function resyncRolesAndPermissions(): void
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

    private function restorePostgreSql(string $connection, string $absolutePath): void
    {
        $rawSql = file_get_contents($absolutePath);
        $preview = is_string($rawSql) ? $this->sanitizeSqlDump($rawSql) : '';
        $isFullDump = $this->isFullPostgresDump($preview);
        $preservedSnapshots = $isFullDump ? $this->snapshotPreservedTableRows($connection) : [];

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
                DB::purge($connection);
                DB::reconnect($connection);

                $this->ensureExcludedTablesExist($connection);
                $this->restorePreservedTableRows($connection, $preservedSnapshots);
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
            '-h',
            (string) ($config['host'] ?? '127.0.0.1'),
            '-p',
            (string) ($config['port'] ?? '5432'),
            '-U',
            (string) ($config['username'] ?? ''),
            '-d',
            (string) ($config['database'] ?? ''),
            '-v',
            'ON_ERROR_STOP=1',
            '-f',
            $normalizedPath,
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
        $sql = (string) preg_replace('/^\s*\\\\(?:un)?restrict\b.*$/mi', '', $sql);
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
     * @return array<string, list<array<string, mixed>>>
     */
    private function snapshotPreservedTableRows(string $connection): array
    {
        $snapshots = [];

        foreach (self::PRESERVED_TABLES as $table) {
            $snapshots[$table] = $this->snapshotTableRows($connection, $table);
        }

        return $snapshots;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function snapshotTableRows(string $connection, string $table): array
    {
        if (! Schema::connection($connection)->hasTable($table)) {
            return [];
        }

        return DB::connection($connection)
            ->table($table)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $snapshots
     */
    private function restorePreservedTableRows(string $connection, array $snapshots): void
    {
        foreach (self::PRESERVED_TABLES as $table) {
            $this->restoreTableRows($connection, $table, $snapshots[$table] ?? []);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function restoreTableRows(string $connection, string $table, array $rows): void
    {
        if ($rows === [] || ! Schema::connection($connection)->hasTable($table)) {
            return;
        }

        $db = DB::connection($connection);

        $db->table($table)->delete();

        foreach (array_chunk($rows, 100) as $chunk) {
            $db->table($table)->insert($chunk);
        }

        if ((string) config("database.connections.{$connection}.driver") === 'pgsql') {
            $maxId = (int) $db->table($table)->max('id');
            $db->unprepared(
                'SELECT setval(pg_get_serial_sequence(\''.$table.'\', \'id\'), '.max($maxId, 1).', '.($maxId > 0 ? 'true' : 'false').')'
            );
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

        foreach (
            [
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
            ] as $key
        ) {
            $value = getenv($key);

            if (is_string($value) && $value !== '') {
                $env[$key] = $value;
            }
        }

        return array_merge($env, $overrides);
    }

    /**
     * Resolve PostgreSQL client bin directory for restore when PATH is incomplete.
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
