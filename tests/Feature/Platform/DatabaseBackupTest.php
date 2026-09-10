<?php

use App\Enums\DatabaseBackupStatus;
use App\Enums\DatabaseBackupType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\DatabaseBackupHistory;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Storage::fake('local');

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform super admin can export database sql and see history', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.export'))
        ->assertRedirect(route('platform.database-backups.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Database export completed (#1). Download starting…',
        ])
        ->assertInertiaFlash(
            'download_url',
            route('platform.database-backups.download', 1),
        );

    $history = DatabaseBackupHistory::query()->latest('id')->first();

    expect($history)->not->toBeNull()
        ->and($history->type)->toBe(DatabaseBackupType::Export)
        ->and($history->status)->toBe(DatabaseBackupStatus::Completed)
        ->and($history->filename)->not->toBeNull()
        ->and(Storage::disk('local')->exists((string) $history->path))->toBeTrue();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.database-backups.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/database-backups/index')
            ->has('histories.data', 1)
            ->where('histories.data.0.id', $history->id)
            ->where('histories.data.0.can_download', true)
            ->where('histories.data.0.can_restore', true)
        );

    $this->actingAs($admin, 'platform')
        ->get(route('platform.database-backups.download', $history))
        ->assertOk();
});

test('platform super admin can import sql upload with confirmation', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $sql = UploadedFile::fake()->createWithContent(
        'restore.sql',
        "PRAGMA foreign_keys=OFF;\nSELECT 1;\nPRAGMA foreign_keys=ON;\n",
    );

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.import'), [
            'file' => $sql,
            'confirmed' => '1',
        ])
        ->assertRedirect(route('platform.database-backups.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Database import completed (#1).',
        ]);

    $history = DatabaseBackupHistory::query()->latest('id')->first();

    expect($history)->not->toBeNull()
        ->and($history->type)->toBe(DatabaseBackupType::Import)
        ->and($history->status)->toBe(DatabaseBackupStatus::Completed);
});

test('import strips postgres restrict meta commands before restore', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $sql = UploadedFile::fake()->createWithContent(
        'restore.sql',
        implode("\n", [
            '-- PostgreSQL database dump',
            '\restrict 622a52b6d8c542e6f8e258c35dfedab3',
            'SELECT 1;',
            '\unrestrict 622a52b6d8c542e6f8e258c35dfedab3',
            '',
        ]),
    );

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.import'), [
            'file' => $sql,
            'confirmed' => '1',
        ])
        ->assertRedirect(route('platform.database-backups.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Database import completed (#1).',
        ]);

    $history = DatabaseBackupHistory::query()->latest('id')->first();

    expect($history)->not->toBeNull()
        ->and($history->status)->toBe(DatabaseBackupStatus::Completed);
});

test('import resyncs manage-database-backups permission so admins keep access', function () {
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

    $sql = UploadedFile::fake()->createWithContent(
        'restore.sql',
        "PRAGMA foreign_keys=OFF;\nSELECT 1;\nPRAGMA foreign_keys=ON;\n",
    );

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.import'), [
            'file' => $sql,
            'confirmed' => '1',
        ])
        ->assertRedirect(route('platform.database-backups.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Database import completed (#1).',
        ]);

    expect(
        Permission::query()
            ->where('name', 'manage-database-backups')
            ->where('guard_name', RolesAndPermissionsSeeder::PLATFORM_GUARD)
            ->exists()
    )->toBeTrue();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.database-backups.index'))
        ->assertOk();
});

test('import requires confirmation', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $sql = UploadedFile::fake()->createWithContent('restore.sql', "BEGIN;\nCOMMIT;\n");

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.import'), [
            'file' => $sql,
            'confirmed' => '0',
        ])
        ->assertSessionHasErrors('confirmed');
});

test('platform super admin can restore from export history', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.export'))
        ->assertRedirect();

    $export = DatabaseBackupHistory::query()->latest('id')->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.database-backups.restore', $export), [
            'confirmed' => true,
        ])
        ->assertRedirect(route('platform.database-backups.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Database restore completed (#2).',
        ]);

    $import = DatabaseBackupHistory::query()->where('type', DatabaseBackupType::Import)->latest('id')->first();

    expect($import)->not->toBeNull()
        ->and($import->source_history_id)->toBe($export->id)
        ->and($import->status)->toBe(DatabaseBackupStatus::Completed);
});

test('platform staff without manage database backups cannot access', function () {
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
        ->post(route('platform.database-backups.export'))
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
