import { Check } from 'lucide-react';
import type { OrderTimelineStep } from '@/components/customer/order-track-types';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

type OrderDeliveryTimelineProps = {
    steps: OrderTimelineStep[];
};

export function OrderDeliveryTimeline({ steps }: OrderDeliveryTimelineProps) {
    return (
        <Card className="gap-4 py-5 shadow-none">
            <CardHeader className="px-5 pb-0">
                <CardTitle className="text-base font-semibold">
                    Delivery Timeline
                </CardTitle>
            </CardHeader>
            <CardContent className="px-5">
                <ol className="flex flex-col">
                    {steps.map((step, index) => {
                        const isLast = index === steps.length - 1;
                        const isCompleted = step.state === 'completed';
                        const isCurrent = step.state === 'current';

                        return (
                            <li
                                key={step.key}
                                className="relative flex gap-3 pb-6 last:pb-0"
                            >
                                {!isLast ? (
                                    <span
                                        aria-hidden
                                        className={cn(
                                            'absolute top-7 bottom-0 left-[11px] w-px',
                                            isCompleted
                                                ? 'bg-foreground'
                                                : 'bg-border',
                                        )}
                                    />
                                ) : null}

                                <span
                                    className={cn(
                                        'relative z-10 mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border-2',
                                        isCompleted &&
                                            'bg-foreground border-foreground text-background',
                                        isCurrent &&
                                            'border-primary bg-primary text-primary-foreground',
                                        !isCompleted &&
                                            !isCurrent &&
                                            'border-muted-foreground/40 bg-background',
                                    )}
                                    aria-hidden
                                >
                                    {isCompleted ? (
                                        <Check className="size-3.5 stroke-[3]" />
                                    ) : isCurrent ? (
                                        <span className="bg-primary-foreground size-2 rounded-full" />
                                    ) : null}
                                </span>

                                <div className="min-w-0 pt-0.5">
                                    <p
                                        className={cn(
                                            'text-sm font-semibold',
                                            isCurrent
                                                ? 'text-foreground'
                                                : 'text-foreground',
                                            !isCompleted &&
                                                !isCurrent &&
                                                'text-muted-foreground',
                                        )}
                                    >
                                        {step.label}
                                    </p>
                                    <p
                                        className={cn(
                                            'text-xs',
                                            isCurrent
                                                ? 'text-primary'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {step.detail}
                                    </p>
                                </div>
                            </li>
                        );
                    })}
                </ol>
            </CardContent>
        </Card>
    );
}
