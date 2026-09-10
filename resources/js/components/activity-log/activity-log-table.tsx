import { Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { eventBadgeVariant } from '@/lib/activity-log';
import type { PaginatedActivities } from '@/components/activity-log/types';
import { SimplePagination } from '@/components/simple-pagination';

function formatWhen(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

export function ActivityLogTable({
    activities,
    showUrl,
}: {
    activities: PaginatedActivities;
    showUrl: (id: number) => string;
}) {
    return (
        <>
            <div className="border-border bg-card rounded-xl border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>When</TableHead>
                            <TableHead>Event</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Causer</TableHead>
                            <TableHead>Subject</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {activities.data.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="text-muted-foreground py-10 text-center"
                                >
                                    No activity found.
                                </TableCell>
                            </TableRow>
                        ) : (
                            activities.data.map((activity) => (
                                <TableRow key={activity.id}>
                                    <TableCell className="text-muted-foreground whitespace-nowrap">
                                        {formatWhen(activity.created_at)}
                                    </TableCell>
                                    <TableCell>
                                        {activity.event ? (
                                            <Badge
                                                variant={eventBadgeVariant(
                                                    activity.event,
                                                )}
                                            >
                                                {activity.event}
                                            </Badge>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                —
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-foreground max-w-xs truncate font-medium">
                                        {activity.description}
                                    </TableCell>
                                    <TableCell>
                                        {activity.causer ? (
                                            <div className="min-w-0">
                                                <p className="text-foreground truncate">
                                                    {activity.causer.name}
                                                </p>
                                                {activity.causer.email ? (
                                                    <p className="text-muted-foreground truncate text-xs">
                                                        {activity.causer.email}
                                                    </p>
                                                ) : null}
                                            </div>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                System
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {activity.subject ? (
                                            <div className="min-w-0">
                                                <p className="text-foreground truncate">
                                                    {activity.subject.label ??
                                                        `#${activity.subject.id}`}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {activity.subject.type}
                                                </p>
                                            </div>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                —
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={showUrl(activity.id)}
                                                aria-label={`View activity ${activity.id}`}
                                            >
                                                <Eye className="size-4" />
                                                <span className="sr-only">
                                                    View
                                                </span>
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            <SimplePagination links={activities.links} />
        </>
    );
}
