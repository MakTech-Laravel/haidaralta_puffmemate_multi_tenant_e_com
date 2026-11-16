import {
    Check,
    CircleX,
    CirclePause,
    ClipboardList,
    Clock3,
    MapPin,
    Package,
    PackageCheck,
    PackageOpen,
    PackageX,
    PartyPopper,
    Truck,
    TriangleAlert,
    Undo2,
    UserCheck,
    type LucideIcon,
} from 'lucide-react';
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
    /** Oldest closed attempt first; one activity list per previous rail. */
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

/** Icon for each order status step; unknown keys fall back to a clipboard. */
const STEP_ICONS: Record<string, LucideIcon> = {
    pending: ClipboardList,
    placed: ClipboardList,
    hold: CirclePause,
    processing: PackageOpen,
    ready: PackageCheck,
    assigned: UserCheck,
    picked_up: Package,
    out_for_delivery: Truck,
    arrived: MapPin,
    delivered: PartyPopper,
    delivery_failed: TriangleAlert,
    returning: Undo2,
    returned: PackageX,
    cancelled: CircleX,
};

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
        <div className="-mx-2 -my-2 overflow-x-auto px-2 py-2">
            <ol className="flex flex-col sm:min-w-[36rem] sm:flex-row">
                {steps.map((step, index) => {
                    const completed = index < activeIndex;
                    const isActive = index === activeIndex;
                    const Icon = STEP_ICONS[step.key] ?? ClipboardList;

                    return (
                        <li
                            key={`${step.key}-${index}`}
                            className="relative flex items-start gap-4 pb-6 text-left last:pb-0 sm:flex-1 sm:flex-col sm:items-center sm:gap-2.5 sm:pb-0 sm:text-center"
                        >
                            {index > 0 ? (
                                <span
                                    aria-hidden
                                    className="bg-border absolute -top-6 left-[21px] h-6 w-0.5 sm:top-[21px] sm:right-1/2 sm:left-auto sm:h-0.5 sm:w-full sm:-translate-y-1/2"
                                />
                            ) : null}
                            <span className="relative z-10 flex size-11 shrink-0 items-center justify-center">
                                <span
                                    aria-hidden
                                    className={cn(
                                        'flex items-center justify-center rounded-full',
                                        completed &&
                                            'bg-foreground text-background size-10',
                                        isActive &&
                                            'size-11 ring-[6px]',
                                        isActive &&
                                            !isProblem &&
                                            'bg-primary text-primary-foreground ring-primary/15',
                                        isActive &&
                                            isProblem &&
                                            'bg-destructive text-destructive-foreground ring-destructive/15',
                                        !completed &&
                                            !isActive &&
                                            'border-border bg-background text-muted-foreground size-10 border',
                                    )}
                                >
                                    <Icon
                                        className="size-[18px]"
                                        strokeWidth={1.75}
                                    />
                                </span>
                                {completed ? (
                                    <span
                                        aria-hidden
                                        className="border-foreground bg-background text-foreground absolute right-0 bottom-0 flex size-4 items-center justify-center rounded-full border-[1.5px]"
                                    >
                                        <Check
                                            className="size-2.5"
                                            strokeWidth={3.5}
                                        />
                                    </span>
                                ) : null}
                            </span>
                            <span
                                className={cn(
                                    'text-sm leading-[44px] sm:px-1 sm:leading-tight',
                                    isActive
                                        ? 'text-foreground font-semibold'
                                        : completed
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
