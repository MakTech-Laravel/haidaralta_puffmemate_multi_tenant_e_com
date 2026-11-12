import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    BadgePercent,
    ShieldCheck,
    Sparkles,
    Timer,
    Truck,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';
import { shop } from '@/routes';

const heroChips = [
    { icon: BadgePercent, label: 'Save up to 30% on bulk units' },
    { icon: Truck, label: 'Free express dispatch over $95 AUD' },
    { icon: ShieldCheck, label: '100% Australian licensed stock' },
];

function endOfWeek(now: Date): Date {
    const end = new Date(now);
    end.setDate(now.getDate() + ((7 - now.getDay()) % 7));
    end.setHours(23, 59, 59, 999);

    return end;
}

function formatRemaining(milliseconds: number): string {
    const totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${pad(hours)}h : ${pad(minutes)}m : ${pad(seconds)}s`;
}

function useWeekendCountdown(): string | null {
    const [remaining, setRemaining] = useState<string | null>(null);

    useEffect(() => {
        const tick = () => {
            const now = new Date();
            setRemaining(
                formatRemaining(endOfWeek(now).getTime() - now.getTime()),
            );
        };

        tick();
        const interval = window.setInterval(tick, 1000);

        return () => window.clearInterval(interval);
    }, []);

    return remaining;
}

type PacksSavesHeroProps = {
    maxSavings?: string | null;
    className?: string;
};

export function PacksSavesHero({ maxSavings, className }: PacksSavesHeroProps) {
    const remaining = useWeekendCountdown();

    return (
        <section
            className={cn(
                'bg-ink-surface text-ink-surface-foreground relative overflow-hidden rounded-3xl',
                className,
            )}
        >
            <div className="relative grid items-center gap-8 px-6 py-10 sm:px-10 sm:py-12 lg:grid-cols-[minmax(0,1fr)_auto] lg:px-14">
                <div className="max-w-2xl">
                    <span className="bg-ink-surface-foreground/10 text-ink-surface-foreground inline-flex items-center gap-1.5 rounded-full border border-ink-surface-foreground/15 px-3 py-1 text-[11px] font-semibold tracking-wider uppercase">
                        <Sparkles className="size-3.5" />
                        Puffonmate bundles &amp; savings
                    </span>
                    <h1 className="mt-4 text-4xl leading-[1.05] font-semibold tracking-tight sm:text-5xl">
                        Bigger brands.
                        <br />
                        Massive savings.
                    </h1>
                    <p className="text-ink-surface-foreground/75 mt-4 max-w-xl text-sm leading-relaxed sm:text-base">
                        Curated multi-packs, carton drops, and mix-and-match
                        pairs delivered on-demand straight to your door. No
                        subscriptions, zero warehouse delays.
                    </p>
                    <ul className="mt-5 flex flex-wrap gap-2">
                        {heroChips.map(({ icon: Icon, label }) => (
                            <li
                                key={label}
                                className="text-ink-surface-foreground/85 inline-flex items-center gap-1.5 rounded-full border border-ink-surface-foreground/15 bg-ink-surface-foreground/10 px-2.5 py-1.5 text-xs"
                            >
                                <Icon className="size-3.5" />
                                {label}
                            </li>
                        ))}
                    </ul>
                    <div className="mt-6 flex flex-wrap items-center gap-3">
                        <span className="inline-flex items-center gap-2 rounded-full bg-ink-surface-foreground/10 px-3 py-2 text-xs">
                            <Timer className="size-4" />
                            Weekend Volume Drop — Ends in{' '}
                            <span className="font-semibold tabular-nums">
                                {remaining ?? '--h : --m : --s'}
                            </span>
                        </span>
                        <Link
                            href={shop()}
                            className="text-ink-surface-foreground/85 hover:text-ink-surface-foreground inline-flex items-center gap-1.5 text-xs font-semibold transition"
                        >
                            Build custom pack
                            <ArrowRight className="size-3.5" />
                        </Link>
                    </div>
                </div>

                <div className="flex justify-center lg:justify-end">
                    <div className="bg-secondary text-secondary-foreground flex w-56 rotate-3 flex-col items-center gap-2 rounded-2xl px-6 py-7 text-center shadow-sm sm:w-60">
                        <p className="text-[10px] font-semibold tracking-[0.2em] uppercase opacity-80">
                            Bundle deals
                        </p>
                        <p className="text-4xl leading-none font-semibold tracking-tight uppercase">
                            Save
                            <br />
                            more
                        </p>
                        {maxSavings ? (
                            <span className="text-foreground rounded-full bg-card px-3 py-1 text-xs font-semibold uppercase">
                                Up to ${maxSavings} off
                            </span>
                        ) : null}
                        <p className="text-[10px] font-medium tracking-wide uppercase opacity-80">
                            Mix, match &amp; save
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}
