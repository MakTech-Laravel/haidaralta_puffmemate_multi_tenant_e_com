import { Head, router } from '@inertiajs/react';
import { Download, RotateCcw, Trash2 } from 'lucide-react';
import { useState } from 'react';
import DatabaseBackupController from '@/actions/App/Http/Controllers/Platform/DatabaseBackupController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    backupStatusBadgeVariant,
    backupTypeBadgeVariant,
    formatBytes,
} from '@/lib/database-backup';

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

type PaginatedHistories = {
    data: HistoryRow[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

function formatWhen(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

export default function PlatformDatabaseBackupsIndex({
    backups,
    histories,
    connection,
}: {
    backups: BackupZip[];
    histories: PaginatedHistories;
    connection: string;
}) {
    const [running, setRunning] = useState(false);
    const [cleaning, setCleaning] = useState(false);
    const [restorePath, setRestorePath] = useState<string | null>(null);
    const [confirmedRestore, setConfirmedRestore] = useState(false);
    const [restoreProcessing, setRestoreProcessing] = useState(false);

    return (
        <>
            <Head title="Database backups" />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Backups"
                        description={`Spatie zip backups (database + storage/app). Connection: ${connection}.`}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="secondary"
                            disabled={cleaning}
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

                <div className="space-y-3">
                    <h2 className="text-foreground text-sm font-semibold">
                        Backup archives
                    </h2>
                    <div className="border-border bg-card rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>File</TableHead>
                                    <TableHead>Size</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {backups.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="text-muted-foreground py-10 text-center"
                                        >
                                            No backup zips yet. Run a backup to
                                            create one.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    backups.map((backup) => (
                                        <TableRow key={backup.path}>
                                            <TableCell className="font-medium">
                                                {backup.filename}
                                            </TableCell>
                                            <TableCell>
                                                {formatBytes(backup.size_bytes)}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground whitespace-nowrap">
                                                {formatWhen(backup.date)}
                                            </TableCell>
                                            <TableCell className="space-x-2 text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <a
                                                        href={DatabaseBackupController.download.url(
                                                            {
                                                                query: {
                                                                    path: backup.path,
                                                                },
                                                            },
                                                        )}
                                                    >
                                                        <Download className="size-4" />
                                                        <span className="sr-only">
                                                            Download
                                                        </span>
                                                    </a>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => {
                                                        setConfirmedRestore(
                                                            false,
                                                        );
                                                        setRestorePath(
                                                            backup.path,
                                                        );
                                                    }}
                                                >
                                                    <RotateCcw className="size-4" />
                                                    <span className="sr-only">
                                                        Restore DB
                                                    </span>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <div className="space-y-3">
                    <h2 className="text-foreground text-sm font-semibold">
                        Operator history
                    </h2>
                    <div className="border-border bg-card rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>When</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>File</TableHead>
                                    <TableHead>Triggered by</TableHead>
                                    <TableHead>Message</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {histories.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="text-muted-foreground py-10 text-center"
                                        >
                                            No operator history yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    histories.data.map((history) => (
                                        <TableRow key={history.id}>
                                            <TableCell className="text-muted-foreground whitespace-nowrap">
                                                {formatWhen(history.created_at)}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={backupTypeBadgeVariant(
                                                        history.type,
                                                    )}
                                                >
                                                    {history.type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={backupStatusBadgeVariant(
                                                        history.status,
                                                    )}
                                                >
                                                    {history.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="max-w-xs truncate">
                                                {history.filename ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {history.triggered_by?.name ??
                                                    'System'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground max-w-sm truncate">
                                                {history.message ?? '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                    <SimplePagination links={histories.links} />
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
        </>
    );
}

PlatformDatabaseBackupsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Database backups', href: '/platform/database-backups' },
    ],
};
