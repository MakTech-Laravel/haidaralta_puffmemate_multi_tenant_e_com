import { Head } from '@inertiajs/react';
import ActivityLogController from '@/actions/App/Http/Controllers/Branch/ActivityLogController';
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

export default function BranchActivityLogsIndex({
    branch,
    table: tablePayload,
}: {
    branch: { id: number; name: string; slug: string };
    table: DataTablePayload<ActivityRow>;
}) {
    const query = useDataTableQuery({
        indexUrl: ActivityLogController.index.url(branch.slug),
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
            <Head title="Branch activity" />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Activity log"
                    description={`Audit trail for ${branch.name}`}
                />

                <DataTableToolbar
                    search={query.search}
                    onSearchChange={query.setSearch}
                    searchPlaceholder="Search description, subject, or causer…"
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
                                    <SelectItem value="created">
                                        Created
                                    </SelectItem>
                                    <SelectItem value="updated">
                                        Updated
                                    </SelectItem>
                                    <SelectItem value="deleted">
                                        Deleted
                                    </SelectItem>
                                    <SelectItem value="login">Login</SelectItem>
                                    <SelectItem value="logout">
                                        Logout
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    }
                />

                <ActivityLogTable
                    payload={tablePayload}
                    perPage={query.perPage}
                    isLoading={query.processing}
                    onPerPageChange={query.changePerPage}
                    showUrl={(id) =>
                        ActivityLogController.show.url([branch.slug, id])
                    }
                />
            </div>
        </>
    );
}
