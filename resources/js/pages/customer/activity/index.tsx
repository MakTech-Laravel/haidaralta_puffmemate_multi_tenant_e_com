import { Head } from '@inertiajs/react';
import ActivityLogController from '@/actions/App/Http/Controllers/Customer/ActivityLogController';
import { ActivityLogTable } from '@/components/activity-log/activity-log-table';
import type { ActivityRow } from '@/components/activity-log/types';
import {
    DataTableToolbar,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export default function CustomerActivityIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<ActivityRow>;
}) {
    const query = useDataTableQuery({
        indexUrl: ActivityLogController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                event: tablePayload.filters.event ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    return (
        <>
            <Head title="My activity" />

            <div className="space-y-4 p-2">
                <Heading
                    title="My activity"
                    description="Actions recorded for your customer account"
                />

                <DataTableToolbar
                    search={query.search}
                    onSearchChange={query.setSearch}
                    searchPlaceholder="Search description…"
                    onClear={query.clearFilters}
                    filters={
                        <div className="grid gap-2">
                            <Label>Event</Label>
                            <Select
                                value={String(query.filters.event ?? 'all')}
                                onValueChange={(value) =>
                                    query.setFilter(
                                        'event',
                                        value === 'all' ? null : value,
                                    )
                                }
                            >
                                <SelectTrigger className="w-40">
                                    <SelectValue placeholder="Event" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All events
                                    </SelectItem>
                                    <SelectItem value="login">Login</SelectItem>
                                    <SelectItem value="logout">
                                        Logout
                                    </SelectItem>
                                    <SelectItem value="updated">
                                        Updated
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    }
                />

                <ActivityLogTable
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    showUrl={(id) => ActivityLogController.show.url(id)}
                />
            </div>
        </>
    );
}

CustomerActivityIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'My activity', href: '/activity' },
    ],
};
