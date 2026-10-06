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
                'relative overflow-hidden rounded-3xl bg-neutral-950 text-white',
                className,
            )}
        >
            <div
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_85%_50%,rgba(220,38,38,0.35),transparent_55%)]"
                aria-hidden
            />
            <div className="relative grid items-center gap-8 px-6 py-10 sm:px-10 sm:py-12 lg:grid-cols-[minmax(0,1fr)_auto] lg:px-14">
                <div className="max-w-2xl">
                    <span className="bg-primary/15 text-primary-foreground inline-flex items-center gap-1.5 rounded-full border border-white/10 px-3 py-1 text-[11px] font-bold tracking-wider uppercase">
                        <Sparkles className="text-primary size-3.5" />
                        Puffonmate bundles &amp; savings
                    </span>
                    <h1 className="mt-4 text-4xl leading-[1.05] font-extrabold tracking-tight sm:text-5xl">
                        Bigger brands.
                        <br />
                        Massive savings.
                    </h1>
                    <p className="mt-4 max-w-xl text-sm leading-relaxed text-white/70 sm:text-base">
                        Curated multi-packs, carton drops, and mix-and-match
                        pairs delivered on-demand straight to your door. No
                        subscriptions, zero warehouse delays.
                    </p>
                    <ul className="mt-5 flex flex-wrap gap-2">
                        {heroChips.map(({ icon: Icon, label }) => (
                            <li
                                key={label}
                                className="inline-flex items-center gap-1.5 rounded-md border border-white/10 bg-white/5 px-2.5 py-1.5 text-xs text-white/85"
                            >
                                <Icon className="size-3.5" />
                                {label}
                            </li>
                        ))}
                    </ul>
                    <div className="mt-6 flex flex-wrap items-center gap-3">
                        <span className="inline-flex items-center gap-2 rounded-md bg-white/10 px-3 py-2 text-xs">
                            <Timer className="size-4" />
                            Weekend Volume Drop — Ends in{' '}
                            <span className="font-bold tabular-nums">
                                {remaining ?? '--h : --m : --s'}
                            </span>
                        </span>
                        <Link
                            href={shop()}
                            className="inline-flex items-center gap-1.5 text-xs font-semibold text-white/85 transition hover:text-white"
                        >
                            Build custom pack
                            <ArrowRight className="size-3.5" />
                        </Link>
                    </div>
                </div>

                <div className="flex justify-center lg:justify-end">
                    <div className="bg-primary text-primary-foreground flex w-56 rotate-3 flex-col items-center gap-2 rounded-2xl px-6 py-7 text-center shadow-[0_20px_60px_-15px_rgba(220,38,38,0.7)] sm:w-60">
                        <p className="text-[10px] font-semibold tracking-[0.2em] uppercase opacity-80">
                            Bundle deals
                        </p>
                        <p className="text-4xl leading-none font-extrabold tracking-tight uppercase">
                            Save
                            <br />
                            more
                        </p>
                        {maxSavings ? (
                            <span className="text-foreground rounded-md bg-white px-3 py-1 text-xs font-extrabold uppercase">
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
