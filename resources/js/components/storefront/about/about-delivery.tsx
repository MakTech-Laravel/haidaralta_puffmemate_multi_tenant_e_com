import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

type AboutDeliveryData = {
    eyebrow: string;
    heading: string;
    paragraphs: string[];
    statuses: {
        label: string;
        state: 'done' | 'active' | 'pending';
    }[];
};

type AboutDeliveryProps = {
    data: AboutDeliveryData;
    className?: string;
};

export function AboutDelivery({ data, className }: AboutDeliveryProps) {
    return (
        <section className={cn('bg-background py-16 sm:py-20', className)}>
            <div className="container mx-auto grid items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-14">
                <div>
                    <p className="text-primary text-xs font-semibold tracking-[0.14em] uppercase">
                        {data.eyebrow}
                    </p>
                    <h2 className="text-foreground mt-3 text-3xl font-bold tracking-tight sm:text-5xl">
                        {data.heading}
                    </h2>
                    <div className="text-muted-foreground font-inter mt-4 max-w-xl space-y-3 text-sm leading-relaxed font-normal sm:text-lg">
                        {data.paragraphs.map((paragraph) => (
                            <p key={paragraph}>{paragraph}</p>
                        ))}
                    </div>
                </div>

                <div className="border-border bg-background rounded-2xl border p-5 shadow-sm sm:p-6">
                    <div className="flex items-center justify-between">
                        <div className="">
                            <p className="text-muted-foreground font-inter text-sm font-bold">
                                Order status
                            </p>
                            <p className="text-foreground font-inter text-lg leading-relaxed font-semibold tracking-[0.10em] uppercase">
                                #PMM-28491
                            </p>
                        </div>
                        <div className="">
                            <button className="text-primary bg-primary/10 font-inter rounded-md px-4 py-2 text-sm font-semibold">
                                Live
                            </button>
                        </div>
                    </div>
                    <ol className="mt-5 space-y-0">
                        {data.statuses.map((status, index) => {
                            const isLast = index === data.statuses.length - 1;

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
