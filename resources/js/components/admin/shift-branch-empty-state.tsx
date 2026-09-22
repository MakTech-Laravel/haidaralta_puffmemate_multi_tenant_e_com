import type { ReactNode } from 'react';
import {
    CalendarRange,
    Clock3,
    Store,
    Users,
} from 'lucide-react';

export function ShiftBranchEmptyState({
    picker,
}: {
    picker: ReactNode;
}) {
    return (
        <section className="bg-card grid min-h-[32rem] w-full flex-1 overflow-hidden rounded-2xl border lg:grid-cols-[minmax(20rem,28rem)_minmax(0,1fr)]">
            <div className="flex flex-col justify-start gap-8 p-6 sm:p-8 lg:p-10 xl:p-12">
                <div className="space-y-4">
                    <div className="bg-primary text-primary-foreground flex size-11 items-center justify-center rounded-xl">
                        <Store className="size-5" />
                    </div>
                    <div className="space-y-2">
                        <h2 className="text-2xl font-semibold tracking-tight">
                            Select a store
                        </h2>
                        <p className="text-muted-foreground max-w-sm text-sm leading-relaxed">
                            Open one branch at a time. You will see who is on
                            duty, the day&apos;s coverage, and that store&apos;s
                            shifts.
                        </p>
                    </div>
                </div>

                <div className="w-full">{picker}</div>

                <ul className="text-muted-foreground grid gap-3 text-sm">
                    {WHAT_YOU_GET.map((item) => (
                        <li
                            key={item.label}
                            className="flex items-start gap-3"
                        >
                            <item.icon className="mt-0.5 size-4 shrink-0" />
                            <span>
                                <span className="text-foreground font-medium">
                                    {item.label}
                                </span>
                                {' · '}
                                {item.hint}
                            </span>
                        </li>
                    ))}
                </ul>
            </div>

            <div
                className="bg-muted/40 relative hidden border-l lg:block"
                aria-hidden
            >
                <BoardPreview />
            </div>
        </section>
    );
}

const WHAT_YOU_GET = [
    {
        icon: Users,
        label: 'On duty now',
        hint: 'People inside a live window',
    },
    {
        icon: Clock3,
        label: 'Shifts',
        hint: 'Named start and end times',
    },
    {
        icon: CalendarRange,
        label: 'Scheduled people',
        hint: 'Personal dates and working days',
    },
] as const;

function BoardPreview() {
    return (
        <div className="flex h-full flex-col gap-5 p-6 xl:p-8">
            <div className="grid gap-3 sm:grid-cols-3">
                {WHAT_YOU_GET.map((item) => (
                    <div
                        key={item.label}
                        className="bg-card rounded-xl border px-4 py-3"
                    >
                        <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                            {item.label}
                        </p>
                        <p className="text-muted-foreground/40 mt-2 text-2xl font-semibold tracking-tight">
                            —
                        </p>
                    </div>
                ))}
            </div>

            <div className="bg-card flex flex-col gap-4 rounded-xl border p-5">
                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm font-medium">Today&apos;s coverage</p>
                    <span className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                        Preview
                    </span>
                </div>
                <div className="relative h-2.5 overflow-hidden rounded-full bg-black/5">
                    <span className="bg-primary/40 absolute inset-y-0 left-[18%] w-[22%] rounded-full" />
                    <span className="bg-primary/20 absolute inset-y-0 left-[38%] w-[28%] rounded-full" />
                </div>
                <div className="text-muted-foreground flex justify-between text-[10px] font-medium tracking-wide uppercase">
                    <span>12a</span>
                    <span>6a</span>
                    <span>12p</span>
                    <span>6p</span>
                    <span>12a</span>
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                    <GhostShift name="Floor open" hours="9:00 AM – 5:00 PM" />
                    <GhostShift name="Late trade" hours="12:00 PM – 9:00 PM" />
                </div>
            </div>

            <ol className="mt-auto grid gap-3 xl:grid-cols-3">
                {HOW_IT_WORKS.map((step, index) => (
                    <li
                        key={step.title}
                        className="bg-card rounded-xl border px-4 py-3"
                    >
                        <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                            Step {index + 1}
                        </p>
                        <p className="mt-1.5 text-sm font-medium">
                            {step.title}
                        </p>
                        <p className="text-muted-foreground mt-1 text-xs leading-relaxed">
                            {step.hint}
                        </p>
                    </li>
                ))}
            </ol>
        </div>
    );
}

const HOW_IT_WORKS = [
    {
        title: 'Choose a branch',
        hint: 'Only that store loads. Other branches stay out of the way.',
    },
    {
        title: 'See who is on duty',
        hint: 'Live coverage and named shifts for this store today.',
    },
    {
        title: 'Add a shift or assign staff',
        hint: 'Set hours first, then assign people to their own dates.',
    },
] as const;

function GhostShift({ name, hours }: { name: string; hours: string }) {
    return (
        <div className="rounded-xl border px-4 py-3">
            <p className="text-sm font-medium">{name}</p>
            <p className="text-muted-foreground mt-1 text-xs">{hours}</p>
            <div className="relative mt-3 h-1.5 overflow-hidden rounded-full bg-black/5">
                <span className="bg-primary/30 absolute inset-y-0 left-[22%] w-[40%] rounded-full" />
            </div>
        </div>
    );
}
