import { Form, Head, router } from '@inertiajs/react';
import { Download, RotateCcw, Upload } from 'lucide-react';
import { useState } from 'react';
import DatabaseBackupController from '@/actions/App/Http/Controllers/Platform/DatabaseBackupController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
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

type HistoryRow = {
    id: number;
    type: string;
    status: string;
    filename: string | null;
    size_bytes: number | null;
    connection: string | null;
    message: string | null;
    source_history_id: number | null;
    can_download: boolean;
    can_restore: boolean;
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
    histories,
    connection,
}: {
    histories: PaginatedHistories;
    connection: string;
}) {
    const [exporting, setExporting] = useState(false);
    const [confirmedImport, setConfirmedImport] = useState(false);
    const [restoringId, setRestoringId] = useState<number | null>(null);
    const [restoreProcessing, setRestoreProcessing] = useState(false);

    return (
        <>
            <Head title="Database backups" />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Database backups"
                        description={`Export downloads a SQL dump immediately and keeps history. Connection: ${connection}.`}
                    />
                    <Button
                        disabled={exporting}
                        onClick={() => {
                            setExporting(true);
                            router.post(
                                DatabaseBackupController.export.url(),
                                {},
                                {
                                    preserveScroll: true,
                                    onFlash: (flash) => {
                                        const downloadUrl = (
                                            flash as {
                                                download_url?: unknown;
                                            }
                                        ).download_url;

                                        if (
                                            typeof downloadUrl === 'string' &&
                                            downloadUrl !== ''
                                        ) {
                                            window.location.assign(downloadUrl);
                                        }
                                    },
                                    onFinish: () => setExporting(false),
                                },
                            );
                        }}
                    >
                        Export SQL
                    </Button>
                </div>

                <section className="border-border bg-card space-y-4 rounded-xl border p-4">
                    <div>
                        <h2 className="text-foreground text-sm font-medium">
                            Import SQL
                        </h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Uploading a dump will overwrite the current
                            database. Confirm before continuing.
                        </p>
                    </div>

                    <Form
                        {...DatabaseBackupController.import.form()}
                        encType="multipart/form-data"
                        className="space-y-4"
                        options={{ preserveScroll: true }}
                        onSuccess={() => setConfirmedImport(false)}
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="space-y-2">
                                    <Label htmlFor="backup-file">
                                        SQL file
                                    </Label>
                                    <Input
                                        id="backup-file"
                                        type="file"
                                        name="file"
                                        accept=".sql,.txt,text/plain"
                                        required
                                    />
                                    {errors.file ? (
                                        <p className="text-destructive text-sm">
                                            {errors.file}
                                        </p>
                                    ) : null}
                                </div>

                                <div className="flex items-start gap-2">
                                    <Checkbox
                                        id="import-confirmed"
                                        checked={confirmedImport}
                                        onCheckedChange={(checked) =>
                                            setConfirmedImport(checked === true)
                                        }
                                    />
                                    <div className="space-y-1">
                                        <Label htmlFor="import-confirmed">
                                            I understand this will overwrite the
                                            current database
                                        </Label>
                                        <input
                                            type="hidden"
                                            name="confirmed"
                                            value={confirmedImport ? '1' : '0'}
                                        />
                                        {errors.confirmed ? (
                                            <p className="text-destructive text-sm">
                                                {errors.confirmed}
                                            </p>
                                        ) : null}
                                    </div>
                                </div>

                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing || !confirmedImport}
                                >
                                    <Upload className="size-4" />
                                    Import SQL
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <section className="space-y-3">
                    <h2 className="text-foreground text-sm font-medium">
                        History
                    </h2>
                    <div className="border-border bg-card rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>When</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>File</TableHead>
                                    <TableHead>By</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {histories.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="text-muted-foreground py-10 text-center"
                                        >
                                            No backup history yet.
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
                                                {history.message ? (
                                                    <p className="text-muted-foreground mt-1 max-w-xs truncate text-xs">
                                                        {history.message}
                                                    </p>
                                                ) : null}
                                            </TableCell>
                                            <TableCell>
                                                <div className="min-w-0">
                                                    <p className="text-foreground truncate">
                                                        {history.filename ??
                                                            '—'}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {formatBytes(
                                                            history.size_bytes,
                                                        )}
                                                        {history.source_history_id
                                                            ? ` · from #${history.source_history_id}`
                                                            : ''}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {history.triggered_by ? (
                                                    <div className="min-w-0">
                                                        <p className="text-foreground truncate">
                                                            {
                                                                history
                                                                    .triggered_by
                                                                    .name
                                                            }
                                                        </p>
                                                        <p className="text-muted-foreground truncate text-xs">
                                                            {
                                                                history
                                                                    .triggered_by
                                                                    .email
                                                            }
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="space-x-1 text-right">
                                                {history.can_download ? (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <a
                                                            href={DatabaseBackupController.download.url(
                                                                history.id,
                                                            )}
                                                            download={
                                                                history.filename ??
                                                                true
                                                            }
                                                            onClick={(
                                                                event,
                                                            ) => {
                                                                // Inertia intercepts same-origin <a>; force a real file navigation.
                                                                event.preventDefault();
                                                                window.location.assign(
                                                                    DatabaseBackupController.download.url(
                                                                        history.id,
                                                                    ),
                                                                );
                                                            }}
                                                        >
                                                            <Download className="size-4" />
                                                            <span className="sr-only">
                                                                Download
                                                            </span>
                                                        </a>
                                                    </Button>
                                                ) : null}
                                                {history.can_restore ? (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            setRestoringId(
                                                                history.id,
                                                            )
                                                        }
                                                    >
                                                        <RotateCcw className="size-4" />
                                                        <span className="sr-only">
                                                            Restore
                                                        </span>
                                                    </Button>
                                                ) : null}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                    <SimplePagination links={histories.links} />
                </section>
            </div>

            <ConfirmDeleteDialog
                open={restoringId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRestoringId(null);
                    }
                }}
                title="Restore this backup?"
                description="This will overwrite the current database with the selected SQL dump."
                confirmLabel="Restore"
                processing={restoreProcessing}
                onConfirm={() => {
                    if (restoringId === null) {
                        return;
                    }

                    setRestoreProcessing(true);
                    router.post(
                        DatabaseBackupController.restore.url(restoringId),
                        { confirmed: true },
                        {
                            preserveScroll: true,
                            onFinish: () => {
                                setRestoreProcessing(false);
                                setRestoringId(null);
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
