import { Check, Clock3 } from 'lucide-react';
import type { OrderStatusTrackStep } from '@/components/orders/order-status-track';
import { Badge } from '@/components/ui/badge';
import { orderStatusBadgeClass } from '@/lib/order-badges';
import { cn } from '@/lib/utils';

export type OrderStatusActivityItem = {
    key: string;
    label: string;
    actorName: string | null;
    detail: string | null;
    comment: string | null;
};

export type OrderStatusActivitySections = {
    current: OrderStatusActivityItem[];
    /** Newest closed attempt first; one activity list per previous rail. */
    previous: OrderStatusActivityItem[][];
};

export function ActivityList({
    items,
    orientation = 'vertical',
}: {
    items: OrderStatusActivityItem[];
    orientation?: 'vertical' | 'horizontal';
}) {
    if (items.length === 0) {
        return null;
    }

    const horizontal = orientation === 'horizontal';

    return (
        <ul
            className={cn(
                horizontal
                    ? 'flex gap-3 overflow-x-auto pb-1'
                    : 'space-y-2',
            )}
        >
            {items.map((step, index) => (
                <li
                    key={`${step.key}-${index}`}
                    className={cn(
                        'bg-muted/30 rounded-lg border px-4 py-3',
                        horizontal
                            ? 'flex min-w-[14rem] shrink-0 flex-col gap-2'
                            : 'flex flex-wrap items-start justify-between gap-x-4 gap-y-1',
                    )}
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
                        <span
                            className={cn(
                                'text-muted-foreground flex items-center gap-1.5 text-xs tabular-nums',
                                !horizontal && 'shrink-0',
                            )}
                        >
                            <Clock3 className="size-3.5" aria-hidden />
                            {step.detail}
                        </span>
                    ) : null}
                </li>
            ))}
        </ul>
    );
}

export function ProgressRail({
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

export function previousAttemptLabel(index: number, total: number): string {
    if (total === 1) {
        return 'Previous attempt';
    }

    return `Previous attempt ${index + 1}`;
}

export function OrderPreviousAttemptDetail({
    steps,
    activity,
    showActivity = true,
}: {
    steps: OrderStatusTrackStep[];
    activity?: OrderStatusActivityItem[];
    showActivity?: boolean;
}) {
    if (steps.length === 0) {
        return null;
    }

    return (
        <div className="space-y-3">
            <ProgressRail steps={steps} activeIndex={steps.length} />
            {showActivity && activity !== undefined && activity.length > 0 ? (
                <ActivityList items={activity} />
            ) : null}
        </div>
    );
}
