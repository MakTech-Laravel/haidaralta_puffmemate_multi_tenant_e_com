import { Head } from '@inertiajs/react';
import ActivityLogController from '@/actions/App/Http/Controllers/Platform/ActivityLogController';
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

export default function PlatformActivityLogsIndex({
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
                portal: tablePayload.filters.portal ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    return (
        <>
            <Head title="Activity log" />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Activity log"
                    description="Site-wide audit trail across all portals"
                />

                <DataTableToolbar
                    search={query.search}
                    onSearchChange={query.setSearch}
                    searchPlaceholder="Search description, subject, or causer…"
                    onClear={query.clearFilters}
                    filters={
                        <>
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
                                        <SelectItem value="created">
                                            Created
                                        </SelectItem>
                                        <SelectItem value="updated">
                                            Updated
                                        </SelectItem>
                                        <SelectItem value="deleted">
                                            Deleted
                                        </SelectItem>
                                        <SelectItem value="login">
                                            Login
                                        </SelectItem>
                                        <SelectItem value="logout">
                                            Logout
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label>Portal</Label>
                                <Select
                                    value={String(
                                        query.filters.portal ?? 'all',
                                    )}
                                    onValueChange={(value) =>
                                        query.setFilter(
                                            'portal',
                                            value === 'all' ? null : value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-40">
                                        <SelectValue placeholder="Portal" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All portals
                                        </SelectItem>
                                        <SelectItem value="platform">
                                            Platform
                                        </SelectItem>
                                        <SelectItem value="branch">
                                            Branch
                                        </SelectItem>
                                        <SelectItem value="driver">
                                            Driver
                                        </SelectItem>
                                        <SelectItem value="customer">
                                            Customer
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </>
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

PlatformActivityLogsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Activity log', href: '/platform/activity-logs' },
    ],
};
