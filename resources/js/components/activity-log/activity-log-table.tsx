import { type ColumnDef } from '@tanstack/react-table';
import { Eye } from 'lucide-react';
import { useMemo } from 'react';
import { AppDateTime } from '@/components/app-datetime';
import {
    DataTable,
    DataTableRowActions,
    DataTableTruncated,
    type DataTablePayload,
} from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { eventBadgeVariant } from '@/lib/activity-log';
import type { ActivityRow } from '@/components/activity-log/types';

export function ActivityLogTable({
    payload,
    perPage,
    onPerPageChange,
    showUrl,
    isLoading = false,
}: {
    payload: DataTablePayload<ActivityRow>;
    perPage: number;
    onPerPageChange: (value: number) => void;
    showUrl: (id: number) => string;
    isLoading?: boolean;
}) {
    const columns = useMemo<ColumnDef<ActivityRow>[]>(
        () => [
            {
                accessorKey: 'created_at',
                header: 'When',
                cell: ({ row }) => (
                    <AppDateTime
                        value={row.original.created_at}
                        className="text-muted-foreground whitespace-nowrap"
                    />
                ),
            },
            {
                accessorKey: 'event',
                header: 'Event',
                cell: ({ row }) =>
                    row.original.event ? (
                        <Badge variant={eventBadgeVariant(row.original.event)}>
                            {row.original.event}
                        </Badge>
                    ) : (
                        <span className="text-muted-foreground">—</span>
                    ),
            },
            {
                accessorKey: 'description',
                header: 'Description',
                cell: ({ row }) => (
                    <span className="text-foreground max-w-xs truncate font-medium">
                        {row.original.description}
                    </span>
                ),
            },
            {
                id: 'causer',
                header: 'Causer',
                meta: { truncate: false },
                cell: ({ row }) =>
                    row.original.causer ? (
                        <div className="min-w-0">
                            <DataTableTruncated className="text-foreground">
                                {row.original.causer.name}
                            </DataTableTruncated>
                            {row.original.causer.email ? (
                                <DataTableTruncated className="text-muted-foreground text-xs">
                                    {row.original.causer.email}
                                </DataTableTruncated>
                            ) : null}
                        </div>
                    ) : (
                        <span className="text-muted-foreground">System</span>
                    ),
            },
            {
                id: 'subject',
                header: 'Subject',
                meta: { truncate: false },
                cell: ({ row }) =>
                    row.original.subject ? (
                        <div className="min-w-0">
                            <DataTableTruncated className="text-foreground">
                                {row.original.subject.label ??
                                    `#${row.original.subject.id}`}
                            </DataTableTruncated>
                            <DataTableTruncated className="text-muted-foreground text-xs">
                                {row.original.subject.type}
                            </DataTableTruncated>
                        </div>
                    ) : (
                        <span className="text-muted-foreground">—</span>
                    ),
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'View',
                                icon: Eye,
                                href: showUrl(row.original.id),
                            },
                        ]}
                    />
                ),
            },
        ],
        [showUrl],
    );

    return (
        <DataTable
            columns={columns}
            payload={payload}
            perPage={perPage}
            onPerPageChange={onPerPageChange}
            isLoading={isLoading}
            emptyMessage="No activity found."
        />
    );
}
