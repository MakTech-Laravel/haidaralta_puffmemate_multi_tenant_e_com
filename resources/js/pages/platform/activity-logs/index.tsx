import { Head, Link, router } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { useState } from 'react';
import ActivityLogController from '@/actions/App/Http/Controllers/Platform/ActivityLogController';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { eventBadgeVariant } from '@/lib/activity-log';

type ActivityRow = {
    id: number;
    description: string;
    event: string | null;
    log_name: string | null;
    causer: { id: number; name: string; email: string | null } | null;
    subject: {
        type: string;
        id: number | string | null;
        label: string | null;
    } | null;
    created_at: string | null;
};

type PaginatedActivities = {
    data: ActivityRow[];
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

export default function PlatformActivityLogsIndex({
    activities,
    filters,
}: {
    activities: PaginatedActivities;
    filters: { search: string; event: string };
}) {
    const [search, setSearch] = useState(filters.search);
    const [event, setEvent] = useState(filters.event || 'all');

    const applyFilters = (nextEvent = event) => {
        router.get(
            ActivityLogController.index.url({
                query: {
                    search: search || undefined,
                    event: nextEvent === 'all' ? undefined : nextEvent,
                },
            }),
            {},
            { preserveState: true },
        );
    };

    return (
        <>
            <Head title="Activity log" />

            <div className="space-y-6 p-2">
                <Heading
                    title="Activity log"
                    description="Audit trail of platform model changes"
                />

                <form
                    className="flex max-w-2xl flex-wrap gap-2"
                    onSubmit={(eventSubmit) => {
                        eventSubmit.preventDefault();
                        applyFilters();
                    }}
                >
                    <Input
                        value={search}
                        onChange={(changeEvent) =>
                            setSearch(changeEvent.target.value)
                        }
                        placeholder="Search description, subject, or causer"
                        className="min-w-56 flex-1"
                    />
                    <Select
                        value={event}
                        onValueChange={(value) => {
                            setEvent(value);
                            applyFilters(value);
                        }}
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="Event" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All events</SelectItem>
                            <SelectItem value="created">Created</SelectItem>
                            <SelectItem value="updated">Updated</SelectItem>
                            <SelectItem value="deleted">Deleted</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <div className="border-border bg-card rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>When</TableHead>
                                <TableHead>Event</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead>Causer</TableHead>
                                <TableHead>Subject</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
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
                                                            {
                                                                activity.causer
                                                                    .email
                                                            }
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
                                                        {activity.subject
                                                            .label ??
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
                                                    href={ActivityLogController.show.url(
                                                        activity.id,
                                                    )}
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
            </div>
        </>
    );
}

PlatformActivityLogsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Activity log', href: '/platform/activity-logs' },
    ],
};
