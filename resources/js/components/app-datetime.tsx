import { usePage } from '@inertiajs/react';
import {
    formatAppDate,
    formatAppDateTime,
    formatAppTime,
    getApplicationDateTime,
    type ApplicationDateTimeProps,
} from '@/lib/datetime';
import { cn } from '@/lib/utils';

type DateValue = string | Date | number | null | undefined;

type AppDateProps = {
    value: DateValue;
    fallback?: string;
    className?: string;
};

function useSharedApplication(): ApplicationDateTimeProps | null {
    const { application } = usePage().props;

    return application ?? null;
}

/**
 * Renders a date using Application Settings timezone + date_format.
 */
export function AppDate({
    value,
    fallback = '—',
    className,
}: AppDateProps) {
    const application = useSharedApplication();
    const formatted = formatAppDate(value, application);

    return (
        <time
            className={cn(className)}
            dateTime={typeof value === 'string' ? value : undefined}
        >
            {formatted || fallback}
        </time>
    );
}

/**
 * Renders a time using Application Settings timezone + time_format.
 */
export function AppTime({
    value,
    fallback = '—',
    className,
}: AppDateProps) {
    const application = useSharedApplication();
    const formatted = formatAppTime(value, application);

    return (
        <time
            className={cn(className)}
            dateTime={typeof value === 'string' ? value : undefined}
        >
            {formatted || fallback}
        </time>
    );
}

/**
 * Renders date + time using Application Settings.
 */
export function AppDateTime({
    value,
    fallback = '—',
    className,
}: AppDateProps) {
    const application = useSharedApplication();
    const formatted = formatAppDateTime(value, application);

    return (
        <time
            className={cn(className)}
            dateTime={typeof value === 'string' ? value : undefined}
        >
            {formatted || fallback}
        </time>
    );
}

/**
 * Formats a date-only range (e.g. shift periods) with settings date_format.
 */
export function AppDateRange({
    start,
    end,
    fallback = '—',
    className,
    separator = ' – ',
}: {
    start: DateValue;
    end: DateValue;
    fallback?: string;
    className?: string;
    separator?: string;
}) {
    const application = useSharedApplication();
    const startLabel = formatAppDate(start, application);
    const endLabel = formatAppDate(end, application);

    if (!startLabel && !endLabel) {
        return <span className={cn(className)}>{fallback}</span>;
    }

    return (
        <span className={cn(className)}>
            {startLabel || fallback}
            {separator}
            {endLabel || fallback}
        </span>
    );
}

export { formatAppDate, formatAppTime, formatAppDateTime, getApplicationDateTime };
