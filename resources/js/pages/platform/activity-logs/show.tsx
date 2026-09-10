import { Head, Link } from '@inertiajs/react';
import ActivityLogController from '@/actions/App/Http/Controllers/Platform/ActivityLogController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { eventBadgeVariant } from '@/lib/activity-log';

type ActivityDetail = {
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
    attribute_changes: {
        attributes: Record<string, unknown>;
        old: Record<string, unknown>;
    };
    properties: Record<string, unknown>;
    created_at: string | null;
};

function formatWhen(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
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

export default function PlatformActivityLogsShow({
    activity,
}: {
    activity: ActivityDetail;
}) {
    const keys = changeKeys(activity.attribute_changes);
    const propertyEntries = Object.entries(activity.properties);

    return (
        <>
            <Head title={`Activity #${activity.id}`} />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={`Activity #${activity.id}`}
                        description={activity.description}
                    />
                    <Button variant="secondary" asChild>
                        <Link href={ActivityLogController.index.url()}>
                            Back
                        </Link>
                    </Button>
                </div>

                <section className="border-border bg-card grid gap-4 rounded-xl border p-4 sm:grid-cols-2">
                    <div>
                        <h2 className="text-muted-foreground text-sm font-medium">
                            When
                        </h2>
                        <p className="text-foreground mt-1 text-sm">
                            {formatWhen(activity.created_at)}
                        </p>
                    </div>
                    <div>
                        <h2 className="text-muted-foreground text-sm font-medium">
                            Event
                        </h2>
                        <div className="mt-1">
                            {activity.event ? (
                                <Badge
                                    variant={eventBadgeVariant(activity.event)}
                                >
                                    {activity.event}
                                </Badge>
                            ) : (
                                <span className="text-muted-foreground text-sm">
                                    —
                                </span>
                            )}
                        </div>
                    </div>
                    <div>
                        <h2 className="text-muted-foreground text-sm font-medium">
                            Log name
                        </h2>
                        <p className="text-foreground mt-1 text-sm">
                            {activity.log_name ?? '—'}
                        </p>
                    </div>
                    <div>
                        <h2 className="text-muted-foreground text-sm font-medium">
                            Description
                        </h2>
                        <p className="text-foreground mt-1 text-sm">
                            {activity.description}
                        </p>
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-2">
                    <div className="border-border bg-card rounded-xl border p-4">
                        <h2 className="text-foreground text-sm font-medium">
                            Causer
                        </h2>
                        {activity.causer ? (
                            <dl className="mt-3 space-y-2 text-sm">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Name
                                    </dt>
                                    <dd className="text-foreground">
                                        {activity.causer.name}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Email
                                    </dt>
                                    <dd className="text-foreground">
                                        {activity.causer.email ?? '—'}
                                    </dd>
                                </div>
                            </dl>
                        ) : (
                            <p className="text-muted-foreground mt-3 text-sm">
                                System / unauthenticated
                            </p>
                        )}
                    </div>

                    <div className="border-border bg-card rounded-xl border p-4">
                        <h2 className="text-foreground text-sm font-medium">
                            Subject
                        </h2>
                        {activity.subject ? (
                            <dl className="mt-3 space-y-2 text-sm">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Type
                                    </dt>
                                    <dd className="text-foreground">
                                        {activity.subject.type}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Label
                                    </dt>
                                    <dd className="text-foreground">
                                        {activity.subject.label ?? '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        ID
                                    </dt>
                                    <dd className="text-foreground">
                                        {activity.subject.id ?? '—'}
                                    </dd>
                                </div>
                            </dl>
                        ) : (
                            <p className="text-muted-foreground mt-3 text-sm">
                                No subject
                            </p>
                        )}
                    </div>
                </section>

                <section className="space-y-3">
                    <h2 className="text-foreground text-sm font-medium">
                        Attribute changes
                    </h2>
                    {keys.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No attribute changes recorded.
                        </p>
                    ) : (
                        <div className="border-border bg-card overflow-hidden rounded-xl border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/60">
                                    <tr className="border-border border-b text-left">
                                        <th className="text-foreground px-4 py-2 font-medium">
                                            Attribute
                                        </th>
                                        <th className="text-foreground px-4 py-2 font-medium">
                                            Old
                                        </th>
                                        <th className="text-foreground px-4 py-2 font-medium">
                                            New
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {keys.map((key) => (
                                        <tr
                                            key={key}
                                            className="border-border border-b last:border-0"
                                        >
                                            <td className="text-foreground px-4 py-2 font-medium">
                                                {key}
                                            </td>
                                            <td className="bg-muted/40 text-muted-foreground px-4 py-2">
                                                {formatValue(
                                                    activity.attribute_changes
                                                        .old[key],
                                                )}
                                            </td>
                                            <td className="bg-accent/50 text-foreground px-4 py-2">
                                                {formatValue(
                                                    activity.attribute_changes
                                                        .attributes[key],
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="text-foreground text-sm font-medium">
                        Properties
                    </h2>
                    {propertyEntries.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No extra properties.
                        </p>
                    ) : (
                        <pre className="border-border bg-muted/40 text-foreground overflow-x-auto rounded-xl border p-4 text-xs">
                            {JSON.stringify(activity.properties, null, 2)}
                        </pre>
                    )}
                </section>
            </div>
        </>
    );
}

PlatformActivityLogsShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Activity log', href: '/platform/activity-logs' },
        { title: 'Show', href: '#' },
    ],
};
