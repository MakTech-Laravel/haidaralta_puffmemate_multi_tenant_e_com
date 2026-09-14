import { Check } from 'lucide-react';
import { aboutDelivery } from '@/lib/storefront-about';
import { cn } from '@/lib/utils';

type AboutDeliveryProps = {
    className?: string;
};

export function AboutDelivery({ className }: AboutDeliveryProps) {
    return (
        <section className={cn('bg-background py-16 sm:py-20', className)}>
            <div className="mx-auto container grid items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-14">
                <div>
                    <p className="text-primary text-xs font-extrabold tracking-[0.14em] uppercase">
                        {aboutDelivery.eyebrow}
                    </p>
                    <h2 className="text-foreground mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                        {aboutDelivery.heading}
                    </h2>
                    <div className="text-muted-foreground mt-4 max-w-xl space-y-3 text-sm leading-relaxed sm:text-base">
                        {aboutDelivery.paragraphs.map((paragraph) => (
                            <p key={paragraph}>{paragraph}</p>
                        ))}
                    </div>
                </div>

                <div className="border-border bg-background rounded-2xl border p-5 shadow-sm sm:p-6">
                    <p className="text-foreground text-sm font-bold">
                        Order status
                    </p>
                    <ol className="mt-5 space-y-0">
                        {aboutDelivery.statuses.map((status, index) => {
                            const isLast =
                                index === aboutDelivery.statuses.length - 1;

                            return (
                                <li
                                    key={status.label}
                                    className="relative flex gap-3 pb-5 last:pb-0"
                                >
                                    {!isLast ? (
                                        <span
                                            className={cn(
                                                'absolute top-7 left-[0.85rem] h-[calc(100%-0.75rem)] w-px',
                                                status.state === 'pending'
                                                    ? 'bg-border'
                                                    : 'bg-foreground/30',
                                            )}
                                            aria-hidden
                                        />
                                    ) : null}
                                    <span
                                        className={cn(
                                            'relative z-10 flex size-7 shrink-0 items-center justify-center rounded-full border',
                                            status.state === 'active' &&
                                                'border-primary bg-primary text-primary-foreground',
                                            status.state === 'done' &&
                                                'border-foreground bg-foreground text-background',
                                            status.state === 'pending' &&
                                                'border-border bg-background text-muted-foreground',
                                        )}
                                        aria-hidden
                                    >
                                        {status.state === 'pending' ? (
                                            <span className="bg-muted-foreground/40 size-1.5 rounded-full" />
                                        ) : (
                                            <Check className="size-3.5" />
                                        )}
                                    </span>
                                    <div className="pt-0.5">
                                        <p
                                            className={cn(
                                                'text-sm font-semibold',
                                                status.state === 'active'
                                                    ? 'text-primary'
                                                    : status.state === 'pending'
                                                      ? 'text-muted-foreground'
                                                      : 'text-foreground',
                                            )}
                                        >
                                            {status.label}
                                        </p>
                                    </div>
                                </li>
                            );
                        })}
                    </ol>
                </div>
            </div>
        </section>
    );
}
