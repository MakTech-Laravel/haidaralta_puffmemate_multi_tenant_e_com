import { Head, router, usePoll } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Download, RotateCcw, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import DatabaseBackupController from '@/actions/App/Http/Controllers/Platform/DatabaseBackupController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableRowActions,
    DataTableToolbar,
    DataTableTruncated,
    buildArchivesDataTableQuery,
    buildDataTableQuery,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useDatabaseBackupRealtime } from '@/hooks/use-database-backup-realtime';
import {
    backupStatusBadgeVariant,
    backupTypeBadgeVariant,
    formatBytes,
} from '@/lib/database-backup';
import { formatAppDateTime } from '@/lib/datetime';

type BackupZip = {
    path: string;
    filename: string;
    size_bytes: number;
    date: string | null;
};

type HistoryRow = {
    id: number;
    type: string;
    status: string;
    filename: string | null;
    path: string | null;
    size_bytes: number | null;
    connection: string | null;
    message: string | null;
    triggered_by: { id: number; name: string; email: string } | null;
    created_at: string | null;
};

type CleanupPolicy = {
    keep_all_days: number;
    keep_daily_days: number;
    max_storage_mb: number | null;
};

function formatWhen(value: string | null): string {
    if (!value) {
        return '—';
    }

    const formatted = formatAppDateTime(value);

    if (formatted !== '') {
        return formatted;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

export default function PlatformDatabaseBackupsIndex({
    archives,
    table: historyTable,
    connection,
    cleanup_policy,
}: {
    archives: DataTablePayload<BackupZip>;
    table: DataTablePayload<HistoryRow>;
    connection: string;
    cleanup_policy: CleanupPolicy;
}) {
    const [running, setRunning] = useState(false);
    const [cleaning, setCleaning] = useState(false);
    const [restorePath, setRestorePath] = useState<string | null>(null);
    const [deletePath, setDeletePath] = useState<string | null>(null);
    const [confirmedRestore, setConfirmedRestore] = useState(false);
    const [restoreProcessing, setRestoreProcessing] = useState(false);
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    useDatabaseBackupRealtime();

    const historyQuery = useDataTableQuery({
        indexUrl: DatabaseBackupController.index.url(),
        initial: {
            filter: {
                search: historyTable.filters.search ?? '',
                type: historyTable.filters.type ?? 'all',
                status: historyTable.filters.status ?? 'all',
            },
            sort: historyTable.sort,
            per_page: historyTable.per_page,
        },
        extraQuery: () =>
            buildArchivesDataTableQuery({
                filter: {
                    search: archives.filters.search ?? null,
                },
                sort: archives.sort,
                per_page: archives.per_page,
                page: archives.meta.current_page,
            }),
    });

    const archivesQuery = useDataTableQuery({
        indexUrl: DatabaseBackupController.index.url(),
        initial: {
            filter: {
                search: archives.filters.search ?? '',
            },
            sort: archives.sort,
            per_page: archives.per_page,
            page: archives.meta.current_page,
        },
        buildQuery: buildArchivesDataTableQuery,
        extraQuery: () =>
            buildDataTableQuery({
                filter: {
                    search: historyTable.filters.search ?? null,
                    type: historyTable.filters.type ?? null,
                    status: historyTable.filters.status ?? null,
                },
                sort: historyTable.sort,
                per_page: historyTable.per_page,
                page: historyTable.meta.current_page,
            }),
    });

    const hasActiveRestore = useMemo(
        () =>
            historyTable.data.some(
                (history) =>
                    history.type === 'restore' &&
                    (history.status === 'pending' ||
                        history.status === 'running'),
            ),
        [historyTable.data],
    );

    const hasActiveHistory = useMemo(
        () =>
            historyTable.data.some(
                (history) =>
                    history.status === 'pending' ||
                    history.status === 'running',
            ),
        [historyTable.data],
    );

    const archiveColumns = useMemo<ColumnDef<BackupZip>[]>(
        () => [
            {
                accessorKey: 'filename',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="File"
                        sortable
                        sortKey="filename"
                        currentSort={archivesQuery.sort}
                        onSort={archivesQuery.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <div className="min-w-0">
                        <DataTableTruncated className="font-medium">
                            {row.original.filename}
                        </DataTableTruncated>
                        <DataTableTruncated className="text-muted-foreground mt-0.5 text-xs md:hidden">
                            {formatWhen(row.original.date)}
                        </DataTableTruncated>
                    </div>
                ),
                meta: { truncate: false },
            },
            {
                accessorKey: 'date',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Created"
                        sortable
                        sortKey="date"
                        currentSort={archivesQuery.sort}
                        onSort={archivesQuery.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <span className="text-muted-foreground hidden whitespace-nowrap md:inline">
                        {formatWhen(row.original.date)}
                    </span>
                ),
            },
            {
                accessorKey: 'size_bytes',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Size"
                        sortable
                        sortKey="size_bytes"
                        currentSort={archivesQuery.sort}
                        onSort={archivesQuery.toggleSort}
                    />
                ),
                cell: ({ row }) => formatBytes(row.original.size_bytes),
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => (
                    <div className="flex justify-end">
                        <DataTableRowActions
                            items={[
                                {
                                    label: 'Download',
                                    icon: Download,
                                    href: DatabaseBackupController.download.url(
                                        {
                                            query: {
                                                path: row.original.path,
                                            },
                                        },
                                    ),
                                },
                                {
                                    label: 'Restore DB',
                                    icon: RotateCcw,
                                    onSelect: () => {
                                        setConfirmedRestore(false);
                                        setRestorePath(row.original.path);
                                    },
                                },
                                {
                                    label: 'Delete zip',
                                    icon: Trash2,
                                    destructive: true,
                                    separatorBefore: true,
                                    onSelect: () =>
                                        setDeletePath(row.original.path),
                                },
                            ]}
                        />
                    </div>
                ),
            },
        ],
        [archivesQuery.sort, archivesQuery.toggleSort],
    );

    const historyColumns = useMemo<ColumnDef<HistoryRow>[]>(
        () => [
            {
                accessorKey: 'created_at',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="When"
                        sortable
                        sortKey="created_at"
                        currentSort={historyQuery.sort}
                        onSort={historyQuery.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <span className="text-muted-foreground whitespace-nowrap">
                        {formatWhen(row.original.created_at)}
                    </span>
                ),
            },
            {
                accessorKey: 'type',
                header: 'Type',
                cell: ({ row }) => (
                    <Badge variant={backupTypeBadgeVariant(row.original.type)}>
                        {row.original.type}
                    </Badge>
                ),
            },
            {
                accessorKey: 'status',
                header: 'Status',
                cell: ({ row }) => (
                    <Badge
                        variant={backupStatusBadgeVariant(row.original.status)}
                    >
                        {row.original.status}
                    </Badge>
                ),
            },
            {
                accessorKey: 'filename',
                header: 'File',
                cell: ({ row }) => (
                    <span className="max-w-xs truncate">
                        {row.original.filename ?? '—'}
                    </span>
                ),
            },
            {
                id: 'triggered_by',
                header: 'Triggered by',
                cell: ({ row }) => row.original.triggered_by?.name ?? 'System',
            },
            {
                accessorKey: 'message',
                header: 'Message',
                cell: ({ row }) => (
                    <span className="text-muted-foreground max-w-sm truncate">
                        {row.original.message ?? '—'}
                    </span>
                ),
            },
        ],
        [historyQuery.sort, historyQuery.toggleSort],
    );

    const { start: startPoll, stop: stopPoll } = usePoll(
        3000,
        {
            only: ['archives', 'table'],
        },
        { autoStart: false },
    );

    useEffect(() => {
        // Restore drops the public schema; Inertia polls would 500 mid-wipe.
        if (hasActiveRestore) {
            stopPoll();

            return;
        }

        if (hasActiveHistory) {
            startPoll();
        } else {
            stopPoll();
        }
    }, [hasActiveRestore, hasActiveHistory, startPoll, stopPoll]);

    const deleteFilename = deletePath ? deletePath.split('/').pop() : null;

    return (
        <>
            <Head title="Database backups" />

            <div className="space-y-4 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Backups"
                        description={`Spatie zip backups (database + storage/app). Connection: ${connection}.`}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="secondary"
                            disabled={cleaning}
                            title={`Deletes backup zips older than ${cleanup_policy.keep_all_days} days (BACKUP_KEEP_ALL_DAYS). Newest zip is never removed.`}
                            onClick={() => {
                                setCleaning(true);
                                router.post(
                                    DatabaseBackupController.clean.url(),
                                    {},
                                    {
                                        preserveScroll: true,
                                        onFinish: () => setCleaning(false),
                                    },
                                );
                            }}
                        >
                            <Trash2 className="size-4" />
                            Clean old backups
                        </Button>
                        <Button
                            disabled={running}
                            onClick={() => {
                                setRunning(true);
                                router.post(
                                    DatabaseBackupController.run.url(),
                                    {},
                                    {
                                        preserveScroll: true,
                                        onFinish: () => setRunning(false),
                                    },
                                );
                            }}
                        >
                            Run backup
                        </Button>
                    </div>
                </div>

                <p className="text-muted-foreground text-sm">
                    <span className="text-foreground font-medium">
                        Clean old backups
                    </span>{' '}
                    deletes zips older than {cleanup_policy.keep_all_days} days
                    (BACKUP_KEEP_ALL_DAYS)
                    {cleanup_policy.max_storage_mb
                        ? `, then trims oldest if storage exceeds ${cleanup_policy.max_storage_mb} MB`
                        : ''}
                    . Use Delete zip in the row menu to remove a zip inside the
                    keep window.
                </p>

                {hasActiveRestore ? (
                    <p className="bg-muted text-foreground rounded-md px-3 py-2 text-sm">
                        Database restore in progress. Keep this tab open — the
                        page will hard-reload when restore finishes. Do not
                        refresh until then (the schema is briefly unavailable).
                    </p>
                ) : null}

                <div className="space-y-3">
                    <h2 className="text-foreground text-sm font-semibold">
                        Backup archives
                    </h2>
                    <DataTable
                        columns={archiveColumns}
                        payload={archives}
                        perPage={archivesQuery.perPage}
                        onPerPageChange={archivesQuery.changePerPage}
                        emptyMessage="No backup zips yet. Run a backup to create one."
                        toolbar={
                            <DataTableToolbar
                                search={archivesQuery.search}
                                onSearchChange={archivesQuery.setSearch}
                                searchPlaceholder="Search zip filename…"
                                onClear={archivesQuery.clearFilters}
                            />
                        }
                    />
                </div>

                <div className="space-y-3">
                    <h2 className="text-foreground text-sm font-semibold">
                        Operator history
                    </h2>
                    <DataTable
                        columns={historyColumns}
                        payload={historyTable}
                        perPage={historyQuery.perPage}
                        onPerPageChange={historyQuery.changePerPage}
                        emptyMessage="No operator history yet."
                        toolbar={
                            <DataTableToolbar
                                search={historyQuery.search}
                                onSearchChange={historyQuery.setSearch}
                                searchPlaceholder="Search filename or message…"
                                onClear={historyQuery.clearFilters}
                            />
                        }
                    />
                </div>
            </div>

            <ConfirmDeleteDialog
                open={restorePath !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRestorePath(null);
                        setConfirmedRestore(false);
                    }
                }}
                title="Restore database from zip?"
                description={
                    <div className="space-y-4">
                        <p>
                            This overwrites the current database with the SQL
                            dump inside the selected Spatie backup zip.
                            Application files on the server are not restored.
                        </p>
                        <div className="flex items-start gap-2">
                            <Checkbox
                                id="confirm-restore"
                                checked={confirmedRestore}
                                onCheckedChange={(checked) =>
                                    setConfirmedRestore(checked === true)
                                }
                            />
                            <Label
                                htmlFor="confirm-restore"
                                className="text-sm"
                            >
                                I understand this will overwrite the live
                                database.
                            </Label>
                        </div>
                    </div>
                }
                confirmLabel="Restore database"
                processing={restoreProcessing || !confirmedRestore}
                onConfirm={() => {
                    if (!restorePath || !confirmedRestore) {
                        return;
                    }

                    setRestoreProcessing(true);
                    router.post(
                        DatabaseBackupController.restore.url(),
                        {
                            path: restorePath,
                            confirmed: true,
                        },
                        {
                            preserveScroll: true,
                            onFinish: () => {
                                setRestoreProcessing(false);
                                setRestorePath(null);
                                setConfirmedRestore(false);
                            },
                        },
                    );
                }}
            />

            <ConfirmDeleteDialog
                open={deletePath !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletePath(null);
                    }
                }}
                title="Delete this backup zip?"
                description={
                    <p>
                        Permanently removes{' '}
                        <span className="font-medium">{deleteFilename}</span>{' '}
                        from storage. This cannot be undone.
                    </p>
                }
                confirmLabel="Delete zip"
                processing={deleteProcessing}
                onConfirm={() => {
                    if (!deletePath) {
                        return;
                    }

                    setDeleteProcessing(true);
                    router.post(
                        DatabaseBackupController.destroy.url(),
                        { path: deletePath },
                        {
                            preserveScroll: true,
                            onFinish: () => {
                                setDeleteProcessing(false);
                                setDeletePath(null);
                            },
                        },
                    );
                }}
            />
        </>
    );
}

PlatformDatabaseBackupsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Database backups', href: '/platform/database-backups' },
    ],
};
