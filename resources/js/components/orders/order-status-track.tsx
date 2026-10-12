import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

export type OrderStatusTrackStep = {
    key: string;
    label: string;
    state: 'completed' | 'current' | 'upcoming';
    actorName: string | null;
    detail: string | null;
    comment: string | null;
};

export function OrderStatusTrack({ steps }: { steps: OrderStatusTrackStep[] }) {
    return (
        <ol className="flex flex-col">
            {steps.map((step, index) => {
                const isLast = index === steps.length - 1;
                const completed = step.state === 'completed';
                const current = step.state === 'current';
                const meta = [step.actorName, step.detail]
                    .filter(Boolean)
                    .join(' · ');

                return (
                    <li
                        key={step.key}
                        className="relative flex gap-3 pb-5 last:pb-0"
                    >
                        {!isLast ? (
                            <span
                                aria-hidden
                                className="bg-foreground/25 absolute top-6 bottom-0 left-[11px] w-px"
                            />
                        ) : null}
                        <span
                            className={cn(
                                'border-foreground bg-background relative z-10 flex size-6 shrink-0 items-center justify-center rounded-full border-2',
                                completed &&
                                    'bg-foreground text-background',
                                current &&
                                    'border-primary bg-primary text-primary-foreground',
                            )}
                            aria-hidden
                        >
                            {completed || current ? (
                                <Check className="size-3.5 stroke-[3]" />
                            ) : null}
                        </span>
                        <div className="min-w-0 pt-0.5">
                            <p
                                className={cn(
                                    'text-sm font-semibold',
                                    step.state === 'upcoming' &&
                                        'text-muted-foreground font-medium',
                                )}
                            >
                                {step.label}
                            </p>
                            {meta ? (
                                <p className="text-muted-foreground text-xs">
                                    {meta}
                                </p>
                            ) : null}
                            {step.comment ? (
                                <p className="text-muted-foreground text-xs">
                                    {step.comment}
                                </p>
                            ) : null}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
