import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { eventBadgeVariant } from '@/lib/activity-log';
import { formatAppDateTime } from '@/lib/datetime';
import type { ActivityDetail } from '@/components/activity-log/types';

function formatWhen(value: string | null): string {
    if (!value) {
        return '—';
    }

    return formatAppDateTime(value) || '—';
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '—';
    }

    if (typeof value === 'string') {
        return value === '' ? '—' : value;
    }

    if (typeof value === 'boolean' || typeof value === 'number') {
        return String(value);
    }

    return JSON.stringify(value);
}

function changeKeys(changes: ActivityDetail['attribute_changes']): string[] {
    return Array.from(
        new Set([
            ...Object.keys(changes.attributes),
            ...Object.keys(changes.old),
        ]),
    ).sort();
}

export function ActivityLogDetail({
    activity,
    backUrl,
    titlePrefix = 'Activity',
}: {
    activity: ActivityDetail;
    backUrl: string;
    titlePrefix?: string;
}) {
    const keys = changeKeys(activity.attribute_changes);
    const propertyEntries = Object.entries(activity.properties);

    return (
        <>
            <Head title={`${titlePrefix} #${activity.id}`} />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={`${titlePrefix} #${activity.id}`}
                        description={activity.description}
                    />
                    <Button variant="secondary" asChild>
                        <Link href={backUrl}>Back</Link>
                    </Button>
                </div>

                <div className="border-border bg-card grid gap-4 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p className="text-muted-foreground text-xs uppercase">
                            When
                        </p>
                        <p className="text-foreground mt-1">
                            {formatWhen(activity.created_at)}
                        </p>
                    </div>
                    <div>
                        <p className="text-muted-foreground text-xs uppercase">
                            Event
                        </p>
                        <div className="mt-1">
                            {activity.event ? (
                                <Badge
                                    variant={eventBadgeVariant(activity.event)}
                                >
                                    {activity.event}
                                </Badge>
                            ) : (
                                '—'
                            )}
                        </div>
                    </div>
                    <div>
                        <p className="text-muted-foreground text-xs uppercase">
                            Causer
                        </p>
                        <p className="text-foreground mt-1">
                            {activity.causer?.name ?? 'System'}
                        </p>
                        {activity.causer?.email ? (
                            <p className="text-muted-foreground text-xs">
                                {activity.causer.email}
                            </p>
                        ) : null}
                    </div>
                    <div>
                        <p className="text-muted-foreground text-xs uppercase">
                            Subject
                        </p>
                        <p className="text-foreground mt-1">
                            {activity.subject?.label ??
                                (activity.subject
                                    ? `#${activity.subject.id}`
                                    : '—')}
                        </p>
                        {activity.subject ? (
                            <p className="text-muted-foreground text-xs">
                                {activity.subject.type}
                            </p>
                        ) : null}
                    </div>
                </div>

                {keys.length > 0 ? (
                    <div className="border-border bg-card space-y-3 rounded-xl border p-4">
                        <h2 className="text-foreground text-sm font-semibold">
                            Attribute changes
                        </h2>
                        <dl className="space-y-2">
                            {keys.map((key) => (
                                <div
                                    key={key}
                                    className="grid gap-1 sm:grid-cols-[8rem_1fr_1fr]"
                                >
                                    <dt className="text-muted-foreground text-sm">
                                        {key}
                                    </dt>
                                    <dd className="text-muted-foreground text-sm line-through">
                                        {formatValue(
                                            activity.attribute_changes.old[key],
                                        )}
                                    </dd>
                                    <dd className="text-foreground text-sm">
                                        {formatValue(
                                            activity.attribute_changes
                                                .attributes[key],
                                        )}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                ) : null}

                {propertyEntries.length > 0 ? (
                    <div className="border-border bg-card space-y-3 rounded-xl border p-4">
                        <h2 className="text-foreground text-sm font-semibold">
                            Properties
                        </h2>
                        <dl className="space-y-2">
                            {propertyEntries.map(([key, value]) => (
                                <div
                                    key={key}
                                    className="grid gap-1 sm:grid-cols-[8rem_1fr]"
                                >
                                    <dt className="text-muted-foreground text-sm">
                                        {key}
                                    </dt>
                                    <dd className="text-foreground text-sm break-all">
                                        {formatValue(value)}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                ) : null}
            </div>
        </>
    );
}
