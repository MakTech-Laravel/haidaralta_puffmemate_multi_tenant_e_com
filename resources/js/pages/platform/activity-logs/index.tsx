import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import ActivityLogController from '@/actions/App/Http/Controllers/Platform/ActivityLogController';
import { ActivityLogTable } from '@/components/activity-log/activity-log-table';
import type { PaginatedActivities } from '@/components/activity-log/types';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export default function PlatformActivityLogsIndex({
    activities,
    filters,
}: {
    activities: PaginatedActivities;
    filters: { search: string; event: string; portal: string };
}) {
    const [search, setSearch] = useState(filters.search);
    const [event, setEvent] = useState(filters.event || 'all');
    const [portal, setPortal] = useState(filters.portal || 'all');

    const applyFilters = (nextEvent = event, nextPortal = portal) => {
        router.get(
            ActivityLogController.index.url({
                query: {
                    search: search || undefined,
                    event: nextEvent === 'all' ? undefined : nextEvent,
                    portal: nextPortal === 'all' ? undefined : nextPortal,
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
                    description="Site-wide audit trail across all portals"
                />

                <form
                    className="flex max-w-3xl flex-wrap gap-2"
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
                            applyFilters(value, portal);
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
                            <SelectItem value="login">Login</SelectItem>
                            <SelectItem value="logout">Logout</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        value={portal}
                        onValueChange={(value) => {
                            setPortal(value);
                            applyFilters(event, value);
                        }}
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="Portal" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All portals</SelectItem>
                            <SelectItem value="platform">Platform</SelectItem>
                            <SelectItem value="branch">Branch</SelectItem>
                            <SelectItem value="driver">Driver</SelectItem>
                            <SelectItem value="customer">Customer</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <ActivityLogTable
                    activities={activities}
                    showUrl={(id) => ActivityLogController.show.url(id)}
                />
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
