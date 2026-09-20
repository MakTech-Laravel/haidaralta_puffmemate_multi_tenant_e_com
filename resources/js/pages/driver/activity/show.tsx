import { Head, Link } from '@inertiajs/react';
import type { ActivityDetail } from '@/components/activity-log/types';
import {
    driverActivityEventIcon,
    driverActivityEventLabel,
    driverActivitySubjectLabel,
    driverActivityTitle,
} from '@/components/driver/driver-activity';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { eventBadgeVariant } from '@/lib/activity-log';
import { dashboard } from '@/routes/driver';
import { index as activityIndex, show as activityShow } from '@/routes/driver/activity';

function formatWhen(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '—';
    }

    if (typeof value === 'string') {
        if (value === '') {
            return '—';
        }

        if (/^\d{4}-\d{2}-\d{2}T/.test(value)) {
            return formatWhen(value);
        }

        return value;
    }

    if (typeof value === 'boolean' || typeof value === 'number') {
        return String(value);
    }

    return JSON.stringify(value);
}

function visibleChangeKeys(
    changes: ActivityDetail['attribute_changes'],
): string[] {
    const hidden = new Set([
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ]);

    return Array.from(
        new Set([
            ...Object.keys(changes.attributes),
            ...Object.keys(changes.old),
        ]),
    )
        .filter((key) => !hidden.has(key))
        .sort();
}

export default function DriverActivityShow({
    activity,
}: {
    activity: ActivityDetail;
}) {
    const Icon = driverActivityEventIcon(activity.event);
    const keys = visibleChangeKeys(activity.attribute_changes);
    const title = driverActivityTitle(activity);
    const subjectLabel = driverActivitySubjectLabel(activity.subject);

    return (
        <>
            <Head title={title} />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex items-start gap-3">
                        <span className="bg-accent mt-0.5 flex size-12 shrink-0 items-center justify-center rounded-full">
                            <Icon className="size-5" aria-hidden />
                        </span>
                        <div className="space-y-1">
                            <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl">
                                {title}
                            </h1>
                            <p className="text-muted-foreground text-base">
                                {formatWhen(activity.created_at)}
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={activityIndex.url()}>Back</Link>
                    </Button>
                </div>

                <section className="bg-accent space-y-3 rounded-2xl p-4 sm:p-5">
                    <div className="flex flex-wrap items-center gap-2">
                        {activity.event ? (
                            <Badge
                                variant={eventBadgeVariant(activity.event)}
                            >
                                {driverActivityEventLabel(activity.event)}
                            </Badge>
                        ) : null}
                    </div>
                    {subjectLabel ? (
                        <p className="text-foreground text-base">
                            {subjectLabel}
                        </p>
                    ) : null}
                </section>

                {keys.length > 0 ? (
                    <section className="border-border space-y-3 rounded-2xl border p-4 sm:p-5">
                        <h2 className="text-foreground text-lg font-semibold">
                            What changed
                        </h2>
                        <ul className="flex flex-col gap-3">
                            {keys.map((key) => (
                                <li key={key} className="space-y-1">
                                    <p className="text-muted-foreground text-sm font-medium">
                                        {key.replaceAll('_', ' ')}
                                    </p>
                                    <p className="text-muted-foreground text-sm line-through">
                                        {formatValue(
                                            activity.attribute_changes.old[key],
                                        )}
                                    </p>
                                    <p className="text-foreground text-base">
                                        {formatValue(
                                            activity.attribute_changes
                                                .attributes[key],
                                        )}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}
            </div>
        </>
    );
}

DriverActivityShow.layout = (props: { activity: ActivityDetail }) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard.url() },
        { title: 'Activity', href: activityIndex.url() },
        {
            title: props.activity.description,
            href: activityShow.url(props.activity.id),
        },
    ],
});
