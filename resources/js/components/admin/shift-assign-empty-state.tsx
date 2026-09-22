import type { ReactNode } from 'react';
import {
    CalendarDays,
    Repeat2,
    UserPlus,
    Umbrella,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export function ShiftAssignEmptyState({
    picker,
}: {
    picker: ReactNode;
}) {
    return (
        <section className="bg-card grid w-full overflow-hidden rounded-2xl border lg:grid-cols-[minmax(18rem,24rem)_minmax(0,1fr)]">
            <div className="flex flex-col gap-5 p-5 sm:p-6">
                <div className="flex items-start gap-3">
                    <div className="bg-primary text-primary-foreground flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <UserPlus className="size-5" />
                    </div>
                    <div className="min-w-0 space-y-1">
                        <h2 className="text-lg font-semibold tracking-tight">
                            Who is this assignment for?
                        </h2>
                        <p className="text-muted-foreground text-sm leading-relaxed">
                            Pick the store first. That loads this branch&apos;s
                            people and shifts onto a personal calendar — not a
                            shared roster.
                        </p>
                    </div>
                </div>

                <div className="w-full">{picker}</div>

                <ul className="grid gap-2">
                    {ASSIGN_FACTS.map((item) => (
                        <li
                            key={item.label}
                            className="flex items-center gap-2.5 text-sm"
                        >
                            <item.icon className="text-muted-foreground size-4 shrink-0" />
                            <span>
                                <span className="font-medium">{item.label}</span>
                                <span className="text-muted-foreground">
                                    {' · '}
                                    {item.hint}
                                </span>
                            </span>
                        </li>
                    ))}
                </ul>
            </div>

            <div className="bg-muted/15 flex flex-col border-t lg:border-t-0 lg:border-l">
                <AssignmentPreview />
                <div className="grid gap-px border-t sm:grid-cols-3">
                    {DAY_ACTIONS.map((action) => (
                        <div
                            key={action.title}
                            className="bg-card flex flex-col gap-1 px-4 py-3"
                        >
                            <p className="text-sm font-medium">{action.title}</p>
                            <p className="text-muted-foreground text-xs leading-relaxed">
                                {action.hint}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

const ASSIGN_FACTS = [
    {
        icon: UserPlus,
        label: 'One person',
        hint: 'Staff or a driver at this store',
    },
    {
        icon: CalendarDays,
        label: 'Their dates',
        hint: 'A start and end, not a roster period',
    },
    {
        icon: Repeat2,
        label: 'Cover someone',
        hint: 'A second assignment on the same day',
    },
] as const;

const DAY_ACTIONS = [
    {
        title: 'Keep',
        hint: 'Usual shift on a working day.',
    },
    {
        title: 'Leave',
        hint: 'They stay scheduled, that date is off.',
    },
    {
        title: 'Different shift',
        hint: 'One day on another window.',
    },
] as const;

const SAMPLE_WEEK = [
    { day: 'Mon', label: 'Floor', tone: 'work' },
    { day: 'Tue', label: 'Floor', tone: 'work' },
    { day: 'Wed', label: 'Floor', tone: 'work' },
    { day: 'Thu', label: 'Leave', tone: 'leave' },
    { day: 'Fri', label: 'Floor', tone: 'work' },
    { day: 'Sat', label: 'Off', tone: 'off' },
    { day: 'Sun', label: 'Dispatch', tone: 'cover' },
] as const;

function AssignmentPreview() {
    return (
        <div className="flex flex-1 flex-col gap-4 p-4 sm:p-5">
            <header className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-sm font-semibold tracking-tight">
                        Sample calendar
                    </p>
                    <p className="text-muted-foreground text-xs">
                        Real people and shifts appear after you pick a store.
                    </p>
                </div>
                <Badge variant="secondary" className="shrink-0 font-normal">
                    Sample
                </Badge>
            </header>

            <div className="bg-card flex items-center justify-between gap-3 rounded-xl border px-3 py-3">
                <div className="min-w-0">
                    <p className="truncate text-sm font-medium">
                        Someone at this store
                    </p>
                    <p className="text-muted-foreground truncate text-xs">
                        1 Sep – 30 Sep · Weekdays
                    </p>
                </div>
                <div className="flex shrink-0 gap-1.5">
                    <Badge variant="outline" className="font-normal">
                        Staff
                    </Badge>
                    <Badge variant="outline" className="font-normal">
                        Driver
                    </Badge>
                </div>
            </div>

            <div className="grid grid-cols-7 gap-1.5">
                {SAMPLE_WEEK.map((cell) => (
                    <div
                        key={cell.day}
                        className={cn(
                            'flex min-h-16 flex-col items-center justify-center gap-1 rounded-lg border px-1 py-2 text-center',
                            cell.tone === 'leave' && 'bg-primary/5 border-primary/20',
                            cell.tone === 'off' && 'bg-muted/40',
                            cell.tone === 'cover' && 'border-primary/30',
                        )}
                    >
                        <span className="text-muted-foreground text-[10px] font-medium tracking-wide uppercase">
                            {cell.day}
                        </span>
                        <span
                            className={cn(
                                'text-[11px] font-medium',
                                cell.tone === 'leave' && 'text-primary',
                                cell.tone === 'off' && 'text-muted-foreground',
                            )}
                        >
                            {cell.label}
                        </span>
                    </div>
                ))}
            </div>

            <p className="text-muted-foreground flex items-center gap-1.5 text-[11px]">
                <Umbrella className="size-3.5 shrink-0" />
                Thursday is leave. Sunday covers Dispatch.
            </p>
        </div>
    );
}
