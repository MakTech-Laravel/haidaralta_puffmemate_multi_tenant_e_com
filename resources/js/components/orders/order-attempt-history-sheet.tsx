import { ChevronDown, History } from 'lucide-react';
import { useState } from 'react';
import {
    OrderPreviousAttemptDetail,
    previousAttemptLabel,
    type OrderStatusActivityItem,
    type OrderStatusActivitySections,
} from '@/components/orders/order-status-progress-parts';
import type { OrderStatusTrackStep } from '@/components/orders/order-status-track';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { orderStatusBadgeClass } from '@/lib/order-badges';
import { cn } from '@/lib/utils';

type OrderAttemptHistorySheetProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    previousAttempts: OrderStatusTrackStep[][];
    activity?: OrderStatusActivitySections;
};

function PreviousAttemptCollapsible({
    index,
    total,
    steps,
    activity,
    open,
    onOpenChange,
}: {
    index: number;
    total: number;
    steps: OrderStatusTrackStep[];
    activity?: OrderStatusActivityItem[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const lastStep = steps[steps.length - 1];

    return (
        <Collapsible
            open={open}
            onOpenChange={onOpenChange}
            className="bg-muted/20 rounded-xl border"
        >
            <CollapsibleTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    className="h-auto w-full justify-between gap-3 rounded-xl px-4 py-3"
                >
                    <span className="flex min-w-0 flex-1 flex-wrap items-center gap-2 text-left">
                        <span className="font-semibold">
                            {previousAttemptLabel(index, total)}
                        </span>
                        {lastStep ? (
                            <Badge
                                variant="outline"
                                className={orderStatusBadgeClass(lastStep.key)}
                            >
                                {lastStep.label}
                            </Badge>
                        ) : null}
                    </span>
                    <ChevronDown
                        className={cn(
                            'size-4 shrink-0 transition-transform',
                            open && 'rotate-180',
                        )}
                        aria-hidden
                    />
                </Button>
            </CollapsibleTrigger>
            <CollapsibleContent className="border-t px-4 py-3">
                <OrderPreviousAttemptDetail
                    steps={steps}
                    activity={activity}
                />
            </CollapsibleContent>
        </Collapsible>
    );
}

export function OrderAttemptHistorySheet({
    open,
    onOpenChange,
    previousAttempts,
    activity,
}: OrderAttemptHistorySheetProps) {
    const [expandedIndexes, setExpandedIndexes] = useState<Set<number>>(
        () => new Set(),
    );
    const hasExpandedAttempt = expandedIndexes.size > 0;

    const handleOpenChange = (nextOpen: boolean) => {
        if (!nextOpen) {
            setExpandedIndexes(new Set());
        }

        onOpenChange(nextOpen);
    };

    const setAttemptExpanded = (index: number, expanded: boolean) => {
        setExpandedIndexes((current) => {
            const next = new Set(current);

            if (expanded) {
                next.add(index);
            } else {
                next.delete(index);
            }

            return next;
        });
    };

    return (
        <Sheet open={open} onOpenChange={handleOpenChange}>
            <SheetContent
                side="right"
                className={cn(
                    'inset-y-3 right-3 h-auto max-h-[calc(100%-1.5rem)] w-[calc(100%-1.5rem)] gap-0 overflow-hidden rounded-2xl border p-0 transition-[max-width] duration-300',
                    hasExpandedAttempt ? 'sm:max-w-2xl' : 'sm:max-w-md',
                )}
            >
                <SheetHeader className="border-b p-4 text-left">
                    <SheetTitle className="flex items-center gap-2">
                        <History className="size-4" aria-hidden />
                        Attempt history
                    </SheetTitle>
                    <SheetDescription>
                        Previous delivery attempts and who updated each status.
                    </SheetDescription>
                </SheetHeader>

                <div className="flex-1 space-y-3 overflow-y-auto p-4">
                    {previousAttempts.map((rail, index) => (
                        <PreviousAttemptCollapsible
                            key={`attempt-history-${index}`}
                            index={index}
                            total={previousAttempts.length}
                            steps={rail}
                            activity={activity?.previous[index]}
                            open={expandedIndexes.has(index)}
                            onOpenChange={(expanded) =>
                                setAttemptExpanded(index, expanded)
                            }
                        />
                    ))}
                </div>
            </SheetContent>
        </Sheet>
    );
}
