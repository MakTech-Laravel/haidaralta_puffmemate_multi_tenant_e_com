<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\DeleteDatabaseBackupRequest;
use App\Http\Requests\Platform\RestoreDatabaseBackupRequest;
use App\Services\Backup\BackupService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DatabaseBackupController extends Controller
{
    public function __construct(private BackupService $backups) {}

    /**
     * List Spatie backup zips and operator history.
     */
    public function index(): Response
    {
        return Inertia::render('platform/database-backups/index', [
            'backups' => $this->backups->list(),
            'histories' => $this->backups->paginateHistory(),
            'connection' => (string) config('database.default'),
            'cleanup_policy' => $this->backups->cleanupPolicy(),
        ]);
    }

    /**
     * Queue a full Spatie backup (database + storage/app files).
     */
    public function run(Request $request): RedirectResponse
    {
        try {
            $history = $this->backups->run($request->user());
            Toast::success('Backup queued (#'.$history->id.').');
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Failed to queue backup: '.$exception->getMessage());
        }

        return redirect()->route('platform.database-backups.index');
    }

    /**
     * Queue Spatie retention cleanup of old backups (does not delete recent zips).
     */
    public function clean(Request $request): RedirectResponse
    {
        try {
            $history = $this->backups->clean($request->user());
            $days = $this->backups->cleanupPolicy()['keep_all_days'];
            Toast::success(
                'Backup cleanup queued (#'.$history->id.'). Deletes zips older than '.$days.' days (newest kept).',
            );
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Failed to queue cleanup: '.$exception->getMessage());
        }

        return redirect()->route('platform.database-backups.index');
    }

    /**
     * Download a backup zip from the backups disk.
     */
    public function download(Request $request): StreamedResponse|RedirectResponse
    {
        $path = (string) $request->query('path', '');

        try {
            return $this->backups->download($path);
        } catch (Throwable $exception) {
            report($exception);
            Toast::error($exception->getMessage());

            return redirect()->route('platform.database-backups.index');
        }
    }

    /**
     * Delete one backup zip from the backups disk.
     */
    public function destroy(DeleteDatabaseBackupRequest $request): RedirectResponse
    {
        try {
            $history = $this->backups->delete(
                (string) $request->validated('path'),
                $request->user(),
            );
            Toast::success('Deleted '.$history->filename.'.');
        } catch (Throwable $exception) {
            report($exception);
            Toast::error($exception->getMessage());
        }

        return redirect()->route('platform.database-backups.index');
    }

    /**
     * Queue a database-only restore from a Spatie backup zip.
     */
    public function restore(RestoreDatabaseBackupRequest $request): RedirectResponse
    {
        try {
            $history = $this->backups->restoreDatabase(
                (string) $request->validated('path'),
                $request->user(),
            );
            Toast::success('Database restore queued (#'.$history->id.').');
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Failed to queue restore: '.$exception->getMessage());
        }

        return redirect()->route('platform.database-backups.index');
    }
}
