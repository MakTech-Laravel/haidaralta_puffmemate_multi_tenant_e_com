import { Check, Clock3 } from 'lucide-react';
import type { OrderStatusTrackStep } from '@/components/orders/order-status-track';
import { Badge } from '@/components/ui/badge';
import { orderStatusBadgeClass } from '@/lib/order-badges';
import { cn } from '@/lib/utils';

const PROBLEM_STATUSES = [
    'cancelled',
    'delivery_failed',
    'returning',
    'returned',
];

export type OrderStatusActivityItem = {
    key: string;
    label: string;
    actorName: string | null;
    detail: string | null;
    comment: string | null;
};

export type OrderStatusActivitySections = {
    current: OrderStatusActivityItem[];
    previous: OrderStatusActivityItem[];
};

function ActivityList({ items }: { items: OrderStatusActivityItem[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <ul className="space-y-2">
            {items.map((step, index) => (
                <li
                    key={`${step.key}-${index}`}
                    className="bg-muted/30 flex flex-wrap items-start justify-between gap-x-4 gap-y-1 rounded-lg border px-4 py-3"
                >
                    <div className="min-w-0 space-y-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge
                                variant="outline"
                                className={orderStatusBadgeClass(step.key)}
                            >
                                {step.label}
                            </Badge>
                            {step.actorName ? (
                                <span className="text-muted-foreground text-xs">
                                    by{' '}
                                    <span className="text-foreground font-medium">
                                        {step.actorName}
                                    </span>
                                </span>
                            ) : null}
                        </div>
                        {step.comment ? (
                            <p className="text-muted-foreground text-sm">
                                {step.comment}
                            </p>
                        ) : null}
                    </div>
                    {step.detail ? (
                        <span className="text-muted-foreground flex shrink-0 items-center gap-1.5 text-xs tabular-nums">
                            <Clock3 className="size-3.5" aria-hidden />
                            {step.detail}
                        </span>
                    ) : null}
                </li>
            ))}
        </ul>
    );
}

function ProgressRail({
    steps,
    activeIndex,
    isProblem = false,
}: {
    steps: OrderStatusTrackStep[];
    activeIndex: number;
    isProblem?: boolean;
}) {
    return (
        <div className="-mx-1 overflow-x-auto px-1 pb-1">
            <ol className="flex min-w-[36rem]">
                {steps.map((step, index) => {
                    const reached = index <= activeIndex;
                    const isActive = index === activeIndex;

                    return (
                        <li
                            key={`${step.key}-${index}`}
                            className="relative flex flex-1 flex-col items-center gap-2 text-center"
                        >
                            {index > 0 ? (
                                <span
                                    aria-hidden
                                    className={cn(
                                        'absolute top-3 right-1/2 h-0.5 w-full -translate-y-1/2',
                                        reached
                                            ? isProblem && isActive
                                                ? 'bg-rose-500'
                                                : 'bg-primary'
                                            : 'bg-border',
                                    )}
                                />
                            ) : null}
                            <span
                                aria-hidden
                                className={cn(
                                    'relative z-10 flex size-6 items-center justify-center rounded-full',
                                    reached &&
                                        !isActive &&
                                        'bg-primary text-primary-foreground',
                                    isActive &&
                                        (isProblem
                                            ? 'bg-rose-500 text-white ring-4 ring-rose-500/20'
                                            : 'bg-primary text-primary-foreground ring-primary/20 ring-4'),
                                    !reached &&
                                        'border-border bg-background border-2',
                                )}
                            >
                                {reached && !isActive ? (
                                    <Check className="size-3.5 stroke-[3]" />
                                ) : null}
                                {isActive ? (
                                    <span className="size-2 rounded-full bg-current" />
                                ) : null}
                            </span>
                            <span
                                className={cn(
                                    'px-1 text-xs leading-tight',
                                    isActive
                                        ? 'text-foreground font-semibold'
                                        : reached
                                          ? 'text-foreground font-medium'
                                          : 'text-muted-foreground',
                                )}
                            >
                                {step.label}
                            </span>
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}

function resolveActiveIndex(steps: OrderStatusTrackStep[]): number {
    const currentIndex = steps.findIndex((step) => step.state === 'current');

    if (currentIndex !== -1) {
        return currentIndex;
    }

    return Math.max(
        steps.filter((step) => step.state === 'completed').length - 1,
        0,
    );
}

export function OrderStatusProgress({
    steps,
    previousSteps = [],
    activity,
    showActivity = true,
}: {
    steps: OrderStatusTrackStep[];
    previousSteps?: OrderStatusTrackStep[];
    activity?: OrderStatusActivitySections;
    showActivity?: boolean;
}) {
    if (steps.length === 0) {
        return null;
    }

    const activeIndex = resolveActiveIndex(steps);
    const activeStep = steps[activeIndex];
    const isProblem = PROBLEM_STATUSES.includes(activeStep.key);
    const hasPreviousRail = previousSteps.length > 0;
    const hasSplit =
        activity !== undefined && activity.previous.length > 0;
    const fallbackEvents = showActivity
        ? steps
              .filter((step) => step.state !== 'upcoming')
              .reverse()
              .map(
                  (step): OrderStatusActivityItem => ({
                      key: step.key,
                      label: step.label,
                      actorName: step.actorName,
                      detail: step.detail,
                      comment: step.comment,
                  }),
              )
        : [];

    return (
        <div className="space-y-6">
            <div className="space-y-6">
                <div className="flex items-center gap-2 text-sm">
                    <span className="text-muted-foreground">
                        Step {activeIndex + 1} of {steps.length}
                    </span>
                    <Badge
                        variant="outline"
                        className={orderStatusBadgeClass(activeStep.key)}
                    >
                        {activeStep.label}
                    </Badge>
                </div>

                <ProgressRail
                    steps={steps}
                    activeIndex={activeIndex}
                    isProblem={isProblem}
                />

                {showActivity && hasSplit ? (
                    <div className="space-y-3">
                        <h3 className="text-sm font-semibold">
                            Current attempt
                        </h3>
                        <ActivityList items={activity.current} />
                    </div>
                ) : null}

                {showActivity && !hasSplit && fallbackEvents.length > 0 ? (
                    <div className="space-y-3">
                        <h3 className="text-sm font-semibold">Activity</h3>
                        <ActivityList
                            items={
                                activity !== undefined &&
                                activity.current.length > 0
                                    ? activity.current
                                    : fallbackEvents
                            }
                        />
                    </div>
                ) : null}
            </div>

            {hasPreviousRail ? (
                <div className="space-y-3">
                    <h3 className="text-sm font-semibold">Previous attempt</h3>
                    <ProgressRail
                        steps={previousSteps}
                        activeIndex={previousSteps.length}
                    />
                    {showActivity && hasSplit ? (
                        <ActivityList items={activity.previous} />
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}
