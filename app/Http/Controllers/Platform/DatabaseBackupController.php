<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ImportDatabaseBackupRequest;
use App\Models\DatabaseBackupHistory;
use App\Services\DatabaseBackup\DatabaseBackupService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DatabaseBackupController extends Controller
{
    public function __construct(private DatabaseBackupService $databaseBackups) {}

    /**
     * List database backup history and actions.
     */
    public function index(): Response
    {
        return Inertia::render('platform/database-backups/index', [
            'histories' => $this->databaseBackups->paginateHistory(),
            'connection' => (string) config('database.default'),
        ]);
    }

    /**
     * Export the current database to SQL, record history, and trigger download.
     */
    public function export(Request $request): RedirectResponse
    {
        try {
            $history = $this->databaseBackups->export($request->user());

            Inertia::flash(
                'download_url',
                route('platform.database-backups.download', $history),
            );
            Toast::success('Database export completed (#'.$history->id.'). Download starting…');
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Database export failed: '.$exception->getMessage());
        }

        return redirect()->route('platform.database-backups.index');
    }

    /**
     * Import an uploaded SQL dump and record history.
     */
    public function import(ImportDatabaseBackupRequest $request): RedirectResponse
    {
        try {
            $history = $this->databaseBackups->importUpload(
                $request->file('file'),
                $request->user(),
            );
            Toast::success('Database import completed (#'.$history->id.').');
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Database import failed: '.$exception->getMessage());
        }

        return redirect()->route('platform.database-backups.index');
    }

    /**
     * Download a completed backup SQL file.
     */
    public function download(DatabaseBackupHistory $databaseBackupHistory): StreamedResponse|RedirectResponse
    {
        if (! $databaseBackupHistory->hasDownloadableFile()
            || ! Storage::disk($databaseBackupHistory->disk)->exists((string) $databaseBackupHistory->path)) {
            Toast::error('Backup file is not available.');

            return redirect()->route('platform.database-backups.index');
        }

        return Storage::disk($databaseBackupHistory->disk)->download(
            (string) $databaseBackupHistory->path,
            (string) $databaseBackupHistory->filename,
            [
                'Content-Type' => 'application/sql',
                'Content-Disposition' => 'attachment; filename="'.$databaseBackupHistory->filename.'"',
            ],
        );
    }

    /**
     * Restore the database from a previous export history entry.
     */
    public function restore(Request $request, DatabaseBackupHistory $databaseBackupHistory): RedirectResponse
    {
        if (! $request->boolean('confirmed')) {
            Toast::error('You must confirm that restoring will overwrite the current database.');

            return redirect()->route('platform.database-backups.index');
        }

        try {
            $history = $this->databaseBackups->restoreFromHistory(
                $databaseBackupHistory,
                $request->user(),
            );
            Toast::success('Database restore completed (#'.$history->id.').');
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Database restore failed: '.$exception->getMessage());
        }

        return redirect()->route('platform.database-backups.index');
    }
}
