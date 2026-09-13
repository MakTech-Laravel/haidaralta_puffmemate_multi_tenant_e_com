import { type ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';
import {
    DataTable,
    DataTableRowActions,
    type DataTablePayload,
} from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { eventBadgeVariant } from '@/lib/activity-log';
import type { ActivityRow } from '@/components/activity-log/types';

function formatWhen(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

export function ActivityLogTable({
    payload,
    perPage,
    onPerPageChange,
    showUrl,
}: {
    payload: DataTablePayload<ActivityRow>;
    perPage: number;
    onPerPageChange: (value: number) => void;
    showUrl: (id: number) => string;
}) {
    const columns = useMemo<ColumnDef<ActivityRow>[]>(
        () => [
            {
                accessorKey: 'created_at',
                header: 'When',
                cell: ({ row }) => (
                    <span className="text-muted-foreground whitespace-nowrap">
                        {formatWhen(row.original.created_at)}
                    </span>
                ),
            },
            {
                accessorKey: 'event',
                header: 'Event',
                cell: ({ row }) =>
                    row.original.event ? (
                        <Badge
                            variant={eventBadgeVariant(row.original.event)}
                        >
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
                cell: ({ row }) =>
                    row.original.causer ? (
                        <div className="min-w-0">
                            <p className="text-foreground truncate">
                                {row.original.causer.name}
                            </p>
                            {row.original.causer.email ? (
                                <p className="text-muted-foreground truncate text-xs">
                                    {row.original.causer.email}
                                </p>
                            ) : null}
                        </div>
                    ) : (
                        <span className="text-muted-foreground">System</span>
                    ),
            },
            {
                id: 'subject',
                header: 'Subject',
                cell: ({ row }) =>
                    row.original.subject ? (
                        <div className="min-w-0">
                            <p className="text-foreground truncate">
                                {row.original.subject.label ??
                                    `#${row.original.subject.id}`}
                            </p>
                            <p className="text-muted-foreground text-xs">
                                {row.original.subject.type}
                            </p>
                        </div>
                    ) : (
                        <span className="text-muted-foreground">—</span>
                    ),
            },
            {
                id: 'actions',
                header: () => <span className="sr-only">Actions</span>,
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'View',
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
            emptyMessage="No activity found."
        />
    );
}
