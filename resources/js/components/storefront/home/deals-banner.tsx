import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { shop } from '@/routes';

type HomeDealsBannerProps = {
    className?: string;
};

export function HomeDealsBanner({ className }: HomeDealsBannerProps) {
    return (
        <div
            id="home-deals"
            className={cn(
                'flex scroll-mt-24 flex-col items-center gap-10 overflow-hidden rounded-4xl bg-black px-8 py-10 sm:scroll-mt-28 sm:px-12 sm:py-12 md:flex-row md:items-center md:justify-between md:gap-12 md:px-14 md:py-14',
                className,
            )}
        >
            <div className="max-w-xl flex-1 text-center md:text-left">
                <p className="text-primary font-inter text-xs font-semibold tracking-[0.16em] uppercase sm:text-sm">
                    PuffMeMate Deals
                </p>
                <h2 className="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl md:text-5xl">
                    Bigger brands.
                    <br />
                    Better deals.
                </h2>
                <p className="font-inter mt-4 text-sm leading-relaxed text-white/90 sm:text-base">
                    ASRV-style product discovery meets Apple&apos;s clean visual
                    approach. Find packs, bundles and weekend specials without
                    the clutter.
                </p>
                <Button
                    asChild
                    size="lg"
                    className="mt-8 h-11 rounded-md px-6 font-semibold shadow-[0_8px_24px_color-mix(in_oklab,var(--primary)_45%,transparent)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_color-mix(in_oklab,var(--primary)_55%,transparent)]"
                >
                    <Link href={shop()} prefetch>
                        Try Combo Pack
                    </Link>
                </Button>
            </div>

            <div className="flex shrink-0 items-center justify-center">
                <div
                    className="bg-primary flex size-40 rotate-12 items-center justify-center rounded-3xl text-center transition duration-300 hover:rotate-14 sm:size-48 md:size-52"
                    aria-hidden
                >
                    <p className="text-2xl leading-tight font-extrabold tracking-tight text-white uppercase sm:text-3xl md:text-4xl">
                        Save
                        <br />
                        More
                    </p>
                </div>
            </div>
        </div>
    );
}
