import { Head, Link, router } from '@inertiajs/react';
import { ScrollText, Search } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import type { ActivityRow } from '@/components/activity-log/types';
import type { DataTablePayload } from '@/components/data-table';
import {
    driverActivityEventFilters,
    driverActivityEventIcon,
    driverActivityEventLabel,
    driverActivitySubjectLabel,
    driverActivityTimeLabel,
    driverActivityTitle,
    groupDriverActivitiesByDay,
    type DriverActivityEventFilter,
} from '@/components/driver/driver-activity';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    ToggleGroup,
    ToggleGroupItem,
} from '@/components/ui/toggle-group';
import { index as activityIndex, show as activityShow } from '@/routes/driver/activity';
import { index as profileIndex } from '@/routes/driver/profile';

function visitActivity(options: {
    event: DriverActivityEventFilter;
    search: string;
    page?: number;
}): void {
    const filter: Record<string, string> = {};

    if (options.search !== '') {
        filter.search = options.search;
    }

    if (options.event !== 'all') {
        filter.event = options.event;
    }

    router.get(
        activityIndex.url(),
        {
            ...(Object.keys(filter).length > 0 ? { filter } : {}),
            ...(options.page && options.page > 1 ? { page: options.page } : {}),
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            async: true,
            showProgress: false,
        },
    );
}

export default function DriverActivityIndex({
    table,
}: {
    table: DataTablePayload<ActivityRow>;
}) {
    const eventFilter =
        table.filters.event === 'login' ||
        table.filters.event === 'logout' ||
        table.filters.event === 'updated'
            ? table.filters.event
            : 'all';
    const [search, setSearch] = useState(String(table.filters.search ?? ''));
    const groups = groupDriverActivitiesByDay(table.data);
    const currentPage = table.meta.current_page;
    const lastPage = table.meta.last_page;

    const submitSearch = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        visitActivity({ event: eventFilter, search: search.trim() });
    };

    return (
        <>
            <Head title="Activity" />

            <div className="flex flex-col gap-6">
                <div className="space-y-1">
                    <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                        Activity
                    </h1>
                    <p className="text-muted-foreground text-base">
                        Sign-ins and account changes on this device
                    </p>
                </div>

                <form
                    onSubmit={submitSearch}
                    className="flex items-center gap-2"
                >
                    <div className="relative min-w-0 flex-1">
                        <Search
                            className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                            aria-hidden
                        />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search activity"
                            aria-label="Search activity"
                            className="h-12 pl-10"
                        />
                    </div>
                    <Button type="submit" size="lg" className="h-12 shrink-0">
                        Search
                    </Button>
                </form>

                <ToggleGroup
                    type="single"
                    value={eventFilter}
                    onValueChange={(value) => {
                        if (
                            value === 'all' ||
                            value === 'login' ||
                            value === 'logout' ||
                            value === 'updated'
                        ) {
                            visitActivity({
                                event: value,
                                search: search.trim(),
                            });
                        }
                    }}
                    variant="outline"
                    size="lg"
                    className="-mx-1 flex-nowrap justify-start overflow-x-auto px-1"
                    aria-label="Filter activity"
                >
                    {driverActivityEventFilters.map((filter) => (
                        <ToggleGroupItem
                            key={filter}
                            value={filter}
                            className="h-11 shrink-0 px-3 text-sm"
                            data-test={`driver-activity-filter-${filter}`}
                        >
                            {filter === 'all'
                                ? 'All'
                                : driverActivityEventLabel(filter)}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>

                {table.data.length === 0 ? (
                    <DriverEmptyState
                        icon={ScrollText}
                        title="No activity yet"
                        description="Deliveries, sign-ins, and profile changes will show up here."
                    />
                ) : (
                    <div className="flex flex-col gap-8">
                        {groups.map((group) => (
                            <section
                                key={group.label}
                                className="flex flex-col gap-3"
                            >
                                <h2 className="text-muted-foreground text-sm font-medium tracking-wide uppercase">
                                    {group.label}
                                </h2>
                                <ul className="flex flex-col gap-2">
                                    {group.items.map((item) => {
                                        const Icon = driverActivityEventIcon(
                                            item.event,
                                        );
                                        const title = driverActivityTitle(item);
                                        const eventLabel =
                                            driverActivityEventLabel(
                                                item.event,
                                            );
                                        const subjectLabel =
                                            driverActivitySubjectLabel(
                                                item.subject,
                                            );
                                        const meta = [
                                            title === eventLabel
                                                ? null
                                                : eventLabel,
                                            subjectLabel,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ');

                                        return (
                                            <li key={item.id}>
                                                <Link
                                                    href={activityShow.url(
                                                        item.id,
                                                    )}
                                                    prefetch
                                                    className="bg-accent hover:bg-accent/80 flex items-start gap-3 rounded-xl p-4 transition-colors"
                                                >
                                                    <span className="bg-background mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-full">
                                                        <Icon
                                                            className="size-4"
                                                            aria-hidden
                                                        />
                                                    </span>
                                                    <span className="min-w-0 flex-1 space-y-1">
                                                        <span className="text-foreground block font-medium">
                                                            {title}
                                                        </span>
                                                        {meta ? (
                                                            <span className="text-muted-foreground block text-sm">
                                                                {meta}
                                                            </span>
                                                        ) : null}
                                                    </span>
                                                    <span className="text-muted-foreground shrink-0 text-sm">
                                                        {driverActivityTimeLabel(
                                                            item.created_at,
                                                        )}
                                                    </span>
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </section>
                        ))}
                    </div>
                )}

                {lastPage > 1 ? (
                    <div className="flex items-center justify-between gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            disabled={currentPage <= 1}
                            onClick={() =>
                                visitActivity({
                                    event: eventFilter,
                                    search: search.trim(),
                                    page: currentPage - 1,
                                })
                            }
                        >
                            Newer
                        </Button>
                        <p className="text-muted-foreground text-sm">
                            {currentPage} of {lastPage}
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={currentPage >= lastPage}
                            onClick={() =>
                                visitActivity({
                                    event: eventFilter,
                                    search: search.trim(),
                                    page: currentPage + 1,
                                })
                            }
                        >
                            Older
                        </Button>
                    </div>
                ) : null}
            </div>
        </>
    );
}

DriverActivityIndex.layout = {
    breadcrumbs: [
        { title: 'Profile', href: profileIndex.url() },
        { title: 'Activity', href: activityIndex.url() },
    ],
};
