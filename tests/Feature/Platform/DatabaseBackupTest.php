<?php

use App\Enums\DatabaseBackupStatus;
use App\Enums\DatabaseBackupType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Events\DatabaseBackupHistoryUpdated;
use App\Jobs\CleanBackupJob;
use App\Jobs\RestoreDatabaseFromBackupJob;
use App\Jobs\RunBackupJob;
use App\Models\DatabaseBackupHistory;
use App\Models\Permission;
use App\Models\User;
use App\Services\Backup\BackupService;
use App\Services\Backup\BackupZipSqlLocator;
use App\Services\Backup\DatabaseRestoreRunner;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use ZipArchive;

beforeEach(function () {
    Storage::fake('backups');

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform super admin can delete a backup zip', function () {
    Event::fake([DatabaseBackupHistoryUpdated::class]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $backupName = (string) config('backup.backup.name');
    $path = $backupName.'/delete-me.zip';
    Storage::disk('backups')->put($path, 'zip-bytes');

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.destroy'), ['path' => $path])
        ->assertRedirect(route('platform.database-backups.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Deleted delete-me.zip.',
        ]);

    expect(Storage::disk('backups')->exists($path))->toBeFalse();

    $history = DatabaseBackupHistory::query()->latest('id')->first();

    expect($history)->not->toBeNull()
        ->and($history->type)->toBe(DatabaseBackupType::Delete)
        ->and($history->status)->toBe(DatabaseBackupStatus::Completed)
        ->and($history->filename)->toBe('delete-me.zip');

    Event::assertDispatched(DatabaseBackupHistoryUpdated::class);
});

test('platform super admin can queue a backup run', function () {
    Queue::fake();
    Event::fake([DatabaseBackupHistoryUpdated::class]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.run'))
        ->assertRedirect(route('platform.database-backups.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Backup queued (#1).',
        ]);

    $history = DatabaseBackupHistory::query()->latest('id')->first();

    expect($history)->not->toBeNull()
        ->and($history->type)->toBe(DatabaseBackupType::Run)
        ->and($history->status)->toBe(DatabaseBackupStatus::Pending);

    Queue::assertPushed(RunBackupJob::class, fn (RunBackupJob $job): bool => $job->historyId === $history->id);
    Event::assertDispatched(DatabaseBackupHistoryUpdated::class);
});

test('database backup history updated event uses platform backups channel', function () {
    $history = DatabaseBackupHistory::factory()->create([
        'status' => DatabaseBackupStatus::Running,
        'message' => 'Backup running',
    ]);

    $event = new DatabaseBackupHistoryUpdated($history);

    expect($event->broadcastAs())->toBe('database-backup.history-updated')
        ->and($event->broadcastOn())->toHaveCount(1)
        ->and($event->broadcastOn()[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($event->broadcastOn()[0]->name)->toBe('private-platform.database-backups')
        ->and($event->broadcastWith()['status'])->toBe('running');
});

test('run backup job broadcasts running and completed history updates', function () {
    Event::fake([DatabaseBackupHistoryUpdated::class]);

    Artisan::shouldReceive('call')
        ->once()
        ->with('backup:run')
        ->andReturn(0);

    $history = DatabaseBackupHistory::factory()->create([
        'status' => DatabaseBackupStatus::Pending,
        'message' => 'Backup queued',
    ]);

    (new RunBackupJob($history->id))->handle();

    expect($history->fresh()->status)->toBe(DatabaseBackupStatus::Completed);

    Event::assertDispatchedTimes(DatabaseBackupHistoryUpdated::class, 2);
});

test('run backup job stores a short failure message from pg_dump mismatch output', function () {
    Event::fake([DatabaseBackupHistoryUpdated::class]);

    $longOutput = <<<'TXT'
Starting backup...
Dumping database postgres...
Backup failed because: The dump process failed with a non-successful exit code.
Exitcode
========
1: General error

Output
======
<no output>

Error Output
============
pg_dump: error: aborting because of server version mismatch
pg_dump: detail: server version: 18.6 (Debian 18.6-1.pgdg12+2); pg_dump version: 15.19 (Debian 15.19-0+deb12u1)
.
#0 /var/www/vendor/spatie/db-dumper/src/DbDumper.php(227): Spatie\DbDumper\Exceptions\DumpFailed::processDidNotEndSuccessfully()
TXT;

    Artisan::shouldReceive('call')
        ->once()
        ->with('backup:run')
        ->andReturn(1);

    Artisan::shouldReceive('output')
        ->andReturn($longOutput);

    $history = DatabaseBackupHistory::factory()->create([
        'status' => DatabaseBackupStatus::Pending,
        'message' => 'Backup queued',
    ]);

    try {
        (new RunBackupJob($history->id))->handle();
    } catch (Throwable) {
        // Job rethrows after marking failed.
    }

    $fresh = $history->fresh();

    expect($fresh->status)->toBe(DatabaseBackupStatus::Failed)
        ->and($fresh->message)->toBe('Backup failed: server version: 18.6 (Debian 18.6-1.pgdg12+2); pg_dump version: 15.19 (Debian 15.19-0+deb12u1)')
        ->and(mb_strlen((string) $fresh->message))->toBeLessThanOrEqual(500);

    Event::assertDispatched(DatabaseBackupHistoryUpdated::class, function (DatabaseBackupHistoryUpdated $event): bool {
        return $event->broadcastWith()['status'] === 'failed'
            && mb_strlen((string) $event->broadcastWith()['message']) <= 500;
    });
});

test('platform super admin can list and download zip from backups disk', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $backupName = (string) config('backup.backup.name');
    $path = $backupName.'/demo-backup.zip';
    Storage::disk('backups')->put($path, 'zip-bytes');

    config([
        'backup.cleanup.default_strategy.keep_all_backups_for_days' => 14,
        'backup.cleanup.default_strategy.keep_daily_backups_for_days' => 0,
        'backup.cleanup.default_strategy.delete_oldest_backups_when_using_more_megabytes_than' => 2500,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.database-backups.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/database-backups/index')
            ->has('backups', 1)
            ->where('backups.0.path', $path)
            ->where('backups.0.filename', 'demo-backup.zip')
            ->where('cleanup_policy.keep_all_days', 14)
            ->where('cleanup_policy.keep_daily_days', 0)
            ->where('cleanup_policy.max_storage_mb', 2500)
        );

    $this->actingAs($admin, 'platform')
        ->get(route('platform.database-backups.download', ['path' => $path]))
        ->assertOk();
});

test('backup cleanup strategy reads keep-all days and zero ladder from config', function () {
    config([
        'backup.cleanup.default_strategy.keep_all_backups_for_days' => 3,
        'backup.cleanup.default_strategy.keep_daily_backups_for_days' => 0,
        'backup.cleanup.default_strategy.keep_weekly_backups_for_weeks' => 0,
        'backup.cleanup.default_strategy.keep_monthly_backups_for_months' => 0,
        'backup.cleanup.default_strategy.keep_yearly_backups_for_years' => 0,
        'backup.cleanup.default_strategy.delete_oldest_backups_when_using_more_megabytes_than' => null,
    ]);

    expect(config('backup.cleanup.default_strategy.keep_all_backups_for_days'))->toBe(3)
        ->and(config('backup.cleanup.default_strategy.keep_daily_backups_for_days'))->toBe(0)
        ->and(config('backup.cleanup.default_strategy.keep_weekly_backups_for_weeks'))->toBe(0)
        ->and(config('backup.cleanup.default_strategy.keep_monthly_backups_for_months'))->toBe(0)
        ->and(config('backup.cleanup.default_strategy.keep_yearly_backups_for_years'))->toBe(0)
        ->and(config('backup.cleanup.default_strategy.delete_oldest_backups_when_using_more_megabytes_than'))->toBeNull();

    $policy = app(BackupService::class)->cleanupPolicy();

    expect($policy['keep_all_days'])->toBe(3)
        ->and($policy['keep_daily_days'])->toBe(0)
        ->and($policy['max_storage_mb'])->toBeNull();
});

test('platform super admin can queue cleanup', function () {
    Queue::fake();

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.clean'))
        ->assertRedirect(route('platform.database-backups.index'));

    Queue::assertPushed(CleanBackupJob::class);
});

test('restore requires confirmation', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.restore'), [
            'path' => 'app/missing.zip',
            'confirmed' => '0',
        ])
        ->assertSessionHasErrors('confirmed');
});

test('platform super admin can queue restore from zip', function () {
    Queue::fake();
    Event::fake([DatabaseBackupHistoryUpdated::class]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $backupName = (string) config('backup.backup.name');
    $path = $backupName.'/restore-me.zip';
    Storage::disk('backups')->put($path, 'zip');

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.restore'), [
            'path' => $path,
            'confirmed' => true,
        ])
        ->assertRedirect(route('platform.database-backups.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Database restore queued (#1).',
        ]);

    $history = DatabaseBackupHistory::query()->latest('id')->first();

    expect($history)->not->toBeNull()
        ->and($history->type)->toBe(DatabaseBackupType::Restore)
        ->and($history->status)->toBe(DatabaseBackupStatus::Pending);

    Queue::assertPushed(RestoreDatabaseFromBackupJob::class);
    Event::assertDispatched(DatabaseBackupHistoryUpdated::class);
});

test('restore job applies sql from zip and resyncs manage-database-backups permission', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

    $permission = Permission::findByName(
        'manage-database-backups',
        RolesAndPermissionsSeeder::PLATFORM_GUARD,
    );
    $permission->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(
        Permission::query()
            ->where('name', 'manage-database-backups')
            ->where('guard_name', RolesAndPermissionsSeeder::PLATFORM_GUARD)
            ->exists()
    )->toBeFalse();

    $tempZip = storage_path('framework/testing/'.uniqid('backup-', true).'.zip');
    @mkdir(dirname($tempZip), 0777, true);

    $zip = new ZipArchive;
    expect($zip->open($tempZip, ZipArchive::CREATE))->toBeTrue();
    $zip->addFromString(
        'db-dumps/sqlite.sql',
        "PRAGMA foreign_keys=OFF;\nSELECT 1;\nPRAGMA foreign_keys=ON;\n",
    );
    $zip->close();

    $backupName = (string) config('backup.backup.name');
    $path = $backupName.'/restore-job.zip';
    Storage::disk('backups')->put($path, file_get_contents($tempZip));
    @unlink($tempZip);

    $history = DatabaseBackupHistory::query()->create([
        'type' => DatabaseBackupType::Restore,
        'status' => DatabaseBackupStatus::Pending,
        'disk' => BackupService::DISK,
        'path' => $path,
        'filename' => basename($path),
        'triggered_by' => $admin->id,
        'connection' => (string) config('database.default'),
        'message' => 'Restore queued',
    ]);

    (new RestoreDatabaseFromBackupJob($history->id, $path))->handle(
        app(DatabaseRestoreRunner::class),
        app(BackupZipSqlLocator::class),
    );

    expect($history->fresh()->status)->toBe(DatabaseBackupStatus::Completed)
        ->and(
            Permission::query()
                ->where('name', 'manage-database-backups')
                ->where('guard_name', RolesAndPermissionsSeeder::PLATFORM_GUARD)
                ->exists()
        )->toBeTrue();
});

test('postgres restore refuses full wipe when dump lacks users table', function () {
    $sqlPath = storage_path('framework/testing/'.uniqid('bad-dump-', true).'.sql');
    @mkdir(dirname($sqlPath), 0777, true);
    file_put_contents(
        $sqlPath,
        "-- PostgreSQL database dump\nCREATE TABLE public.sessions (id text);\n",
    );

    $runner = app(DatabaseRestoreRunner::class);
    $method = new ReflectionMethod(DatabaseRestoreRunner::class, 'restorePostgreSql');

    expect(fn () => $method->invoke($runner, 'pgsql', $sqlPath))
        ->toThrow(RuntimeException::class, 'does not create the users table');

    @unlink($sqlPath);
});

test('unauthorized users cannot access backup routes', function () {
    $this->get(route('platform.database-backups.index'))
        ->assertRedirect();

    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.database-backups.index'))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->post(route('platform.database-backups.run'))
        ->assertForbidden();
});

test('platform manager does not get database backup permission by default', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::MANAGER->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.database-backups.index'))
        ->assertForbidden();
});
