<?php

use App\Http\Controllers\Platform\ActivityLogController as PlatformActivityLogController;
use App\Http\Controllers\Platform\BranchController as PlatformBranchController;
use App\Http\Controllers\Platform\BranchStaffController as PlatformBranchStaffController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\DatabaseBackupController as PlatformDatabaseBackupController;
use App\Http\Controllers\Platform\FcmController as PlatformFcmController;
use App\Http\Controllers\Platform\PermissionController as PlatformPermissionController;
use App\Http\Controllers\Platform\RealtimeController as PlatformRealtimeController;
use App\Http\Controllers\Platform\RoleController as PlatformRoleController;
use App\Http\Controllers\Platform\StaffController as PlatformStaffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:platform'])->prefix('platform')->group(function () {
    Route::get('dashboard', [PlatformDashboardController::class, 'index'])->name('platform.dashboard');

    Route::get('realtime', [PlatformRealtimeController::class, 'index'])->name('platform.realtime.index');
    Route::get('realtime/ping', [PlatformRealtimeController::class, 'ping'])->name('platform.realtime.ping');
    Route::get('realtime/health', [PlatformRealtimeController::class, 'health'])->name('platform.realtime.health');
    Route::post('realtime/test-notification', [PlatformRealtimeController::class, 'testNotification'])->name('platform.realtime.test-notification');

    Route::get('fcm', [PlatformFcmController::class, 'index'])->name('platform.fcm.index');
    Route::post('fcm/test', [PlatformFcmController::class, 'test'])->middleware('throttle:10,1')->name('platform.fcm.test');

    Route::middleware(['permission:manage-roles'])->group(function () {
        Route::get('roles/export', [PlatformRoleController::class, 'export'])->name('platform.roles.export');
        Route::resource('roles', PlatformRoleController::class)->names('platform.roles');
        Route::get('permissions/export', [PlatformPermissionController::class, 'export'])->name('platform.permissions.export');
        Route::get('permissions', [PlatformPermissionController::class, 'index'])->name('platform.permissions.index');
    });

    Route::middleware(['permission:manage-users'])->group(function () {
        Route::resource('staff', PlatformStaffController::class)
            ->parameters(['staff' => 'user'])
            ->names('platform.staff');
        Route::get('branch-staff', [PlatformBranchStaffController::class, 'index'])->name('platform.branch-staff.index');
        Route::get('branch-staff/{user}', [PlatformBranchStaffController::class, 'show'])->name('platform.branch-staff.show');
    });

    Route::middleware(['permission:create-branches'])->group(function () {
        Route::get('branches/create', [PlatformBranchController::class, 'create'])->name('platform.branches.create');
        Route::post('branches', [PlatformBranchController::class, 'store'])->name('platform.branches.store');
    });

    Route::middleware(['permission:view-branches'])->group(function () {
        Route::get('branches', [PlatformBranchController::class, 'index'])->name('platform.branches.index');
        Route::get('branches/{branch}', [PlatformBranchController::class, 'show'])->name('platform.branches.show');
    });

    Route::middleware(['permission:edit-branches'])->group(function () {
        Route::get('branches/{branch}/edit', [PlatformBranchController::class, 'edit'])->name('platform.branches.edit');
        Route::put('branches/{branch}', [PlatformBranchController::class, 'update'])->name('platform.branches.update');
    });

    Route::middleware(['permission:delete-branches'])->group(function () {
        Route::delete('branches/{branch}', [PlatformBranchController::class, 'destroy'])->name('platform.branches.destroy');
    });

    Route::middleware(['permission:view-activity-logs'])->group(function () {
        Route::get('activity-logs', [PlatformActivityLogController::class, 'index'])->name('platform.activity-logs.index');
        Route::get('activity-logs/{activity}', [PlatformActivityLogController::class, 'show'])->name('platform.activity-logs.show');
    });

    Route::middleware(['permission:manage-database-backups'])->group(function () {
        Route::get('database-backups', [PlatformDatabaseBackupController::class, 'index'])->name('platform.database-backups.index');
        Route::post('database-backups/run', [PlatformDatabaseBackupController::class, 'run'])
            ->middleware('throttle:5,1')
            ->name('platform.database-backups.run');
        Route::post('database-backups/clean', [PlatformDatabaseBackupController::class, 'clean'])
            ->middleware('throttle:5,1')
            ->name('platform.database-backups.clean');
        Route::get('database-backups/download', [PlatformDatabaseBackupController::class, 'download'])
            ->name('platform.database-backups.download');
        Route::post('database-backups/restore', [PlatformDatabaseBackupController::class, 'restore'])
            ->middleware('throttle:5,1')
            ->name('platform.database-backups.restore');
    });
});
