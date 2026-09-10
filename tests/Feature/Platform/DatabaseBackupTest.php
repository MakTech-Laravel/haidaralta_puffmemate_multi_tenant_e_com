<?php

use App\Enums\DatabaseBackupStatus;
use App\Enums\DatabaseBackupType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Jobs\CleanBackupJob;
use App\Jobs\RestoreDatabaseFromBackupJob;
use App\Jobs\RunBackupJob;
use App\Models\DatabaseBackupHistory;
use App\Models\Permission;
use App\Models\User;
use App\Services\Backup\BackupService;
use App\Services\Backup\DatabaseRestoreRunner;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use ZipArchive;

beforeEach(function () {
    Storage::fake('backups');

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform super admin can queue a backup run', function () {
    Queue::fake();

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
});

test('platform super admin can list and download zip from backups disk', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $backupName = (string) config('backup.backup.name');
    $path = $backupName.'/demo-backup.zip';
    Storage::disk('backups')->put($path, 'zip-bytes');

    $this->actingAs($admin, 'platform')
        ->get(route('platform.database-backups.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/database-backups/index')
            ->has('backups', 1)
            ->where('backups.0.path', $path)
            ->where('backups.0.filename', 'demo-backup.zip')
        );

    $this->actingAs($admin, 'platform')
        ->get(route('platform.database-backups.download', ['path' => $path]))
        ->assertOk();
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

    (new RestoreDatabaseFromBackupJob($history->id, $path))->handle(app(DatabaseRestoreRunner::class));

    expect($history->fresh()->status)->toBe(DatabaseBackupStatus::Completed)
        ->and(
            Permission::query()
                ->where('name', 'manage-database-backups')
                ->where('guard_name', RolesAndPermissionsSeeder::PLATFORM_GUARD)
                ->exists()
        )->toBeTrue();
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
