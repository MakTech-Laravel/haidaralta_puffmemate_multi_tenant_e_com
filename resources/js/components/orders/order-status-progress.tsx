import { History } from 'lucide-react';
import { useState } from 'react';
import { OrderAttemptHistorySheet } from '@/components/orders/order-attempt-history-sheet';
import {
    ActivityList,
    ProgressRail,
    previousAttemptLabel,
    type OrderStatusActivityItem,
    type OrderStatusActivitySections,
} from '@/components/orders/order-status-progress-parts';
import type { OrderStatusTrackStep } from '@/components/orders/order-status-track';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { orderStatusBadgeClass } from '@/lib/order-badges';

const PROBLEM_STATUSES = [
    'cancelled',
    'delivery_failed',
    'returning',
    'returned',
];

export type {
    OrderStatusActivityItem,
    OrderStatusActivitySections,
} from '@/components/orders/order-status-progress-parts';

export {
    OrderPreviousAttemptDetail,
    previousAttemptLabel,
} from '@/components/orders/order-status-progress-parts';

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
    previousAttempts = [],
    activity,
    showActivity = true,
    previousAttemptsLayout = 'inline',
}: {
    steps: OrderStatusTrackStep[];
    previousAttempts?: OrderStatusTrackStep[][];
    activity?: OrderStatusActivitySections;
    showActivity?: boolean;
    previousAttemptsLayout?: 'inline' | 'sheet';
}) {
    const [historyOpen, setHistoryOpen] = useState(false);

    if (steps.length === 0) {
        return null;
    }

    const activeIndex = resolveActiveIndex(steps);
    const activeStep = steps[activeIndex];
    const isProblem = PROBLEM_STATUSES.includes(activeStep.key);
    const hasPreviousRails = previousAttempts.length > 0;
    const hasSplit =
        activity !== undefined && activity.previous.length > 0;
    const useSheetPrevious = previousAttemptsLayout === 'sheet';
    const currentActivityOrientation = useSheetPrevious
        ? 'horizontal'
        : 'vertical';
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
                <div className="flex flex-wrap items-center justify-between gap-3">
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
                    {useSheetPrevious && hasPreviousRails ? (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setHistoryOpen(true)}
                        >
                            <History className="size-4" aria-hidden />
                            Attempt history
                        </Button>
                    ) : null}
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
                        <ActivityList
                            items={activity.current}
                            orientation={currentActivityOrientation}
                        />
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
                            orientation={currentActivityOrientation}
                        />
                    </div>
                ) : null}
            </div>

            {hasPreviousRails && !useSheetPrevious
                ? previousAttempts.map((rail, index) => (
                      <div
                          key={`previous-attempt-${index}`}
                          className="space-y-3"
                      >
                          <h3 className="text-sm font-semibold">
                              {previousAttemptLabel(
                                  index,
                                  previousAttempts.length,
                              )}
                          </h3>
                          <ProgressRail
                              steps={rail}
                              activeIndex={rail.length}
                          />
                          {showActivity &&
                          activity !== undefined &&
                          activity.previous[index] !== undefined ? (
                              <ActivityList items={activity.previous[index]} />
                          ) : null}
                      </div>
                  ))
                : null}

            {useSheetPrevious && hasPreviousRails ? (
                <OrderAttemptHistorySheet
                    open={historyOpen}
                    onOpenChange={setHistoryOpen}
                    previousAttempts={previousAttempts}
                    activity={activity}
                />
            ) : null}
        </div>
    );
}
