import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import ActivityLogController from '@/actions/App/Http/Controllers/Branch/ActivityLogController';
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

export default function BranchActivityLogsIndex({
    branch,
    activities,
    filters,
}: {
    branch: { id: number; name: string; slug: string };
    activities: PaginatedActivities;
    filters: { search: string; event: string };
}) {
    const [search, setSearch] = useState(filters.search);
    const [event, setEvent] = useState(filters.event || 'all');

    const applyFilters = (nextEvent = event) => {
        router.get(
            ActivityLogController.index.url(branch.slug, {
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
            <Head title="Branch activity" />

            <div className="space-y-6 p-2">
                <Heading
                    title="Activity log"
                    description={`Audit trail for ${branch.name}`}
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

                <ActivityLogTable
                    activities={activities}
                    showUrl={(id) =>
                        ActivityLogController.show.url([branch.slug, id])
                    }
                />
            </div>
        </>
    );
}

BranchActivityLogsIndex.layout = (props: {
    branch: { slug: string };
}) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: `/branch/${props.branch.slug}/dashboard`,
        },
        {
            title: 'Activity log',
            href: `/branch/${props.branch.slug}/activity-logs`,
        },
    ],
});
