import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useAppBrand } from '@/hooks/use-app-brand';
import { cn } from '@/lib/utils';
import { shop } from '@/routes';

type HomeDealsBannerProps = {
    className?: string;
};

export function HomeDealsBanner({ className }: HomeDealsBannerProps) {
    const brand = useAppBrand();

    return (
        <div
            id="home-deals"
            className={cn(
                'bg-primary flex scroll-mt-24 flex-col items-center gap-10 overflow-hidden rounded-4xl px-8 py-10 sm:scroll-mt-28 sm:px-12 sm:py-12 md:flex-row md:items-center md:justify-between md:gap-12 md:px-14 md:py-14',
                className,
            )}
        >
            <div className="max-w-xl flex-1 text-center md:text-left">
                <p className="text-primary-foreground/80 font-inter text-xs font-semibold tracking-[0.16em] uppercase sm:text-sm">
                    {brand.shortName} Deals
                </p>
                <h2 className="text-primary-foreground mt-3 text-3xl font-semibold tracking-tight sm:text-4xl md:text-5xl">
                    Bigger brands.
                    <br />
                    Better deals.
                </h2>
                <p className="text-primary-foreground/90 font-inter mt-4 text-sm leading-relaxed sm:text-base">
                    ASRV-style product discovery meets Apple&apos;s clean visual
                    approach. Find packs, bundles and weekend specials without
                    the clutter.
                </p>
                <Button
                    asChild
                    size="lg"
                    className="mt-8 h-11 rounded-md px-6 font-semibold transition hover:-translate-y-0.5"
                >
                    <Link href={shop()} prefetch>
                        Try Combo Pack
                    </Link>
                </Button>
            </div>

            <div className="flex shrink-0 items-center justify-center">
                <div
                    className="bg-secondary text-secondary-foreground flex size-40 rotate-12 items-center justify-center rounded-3xl text-center transition duration-300 hover:rotate-14 sm:size-48 md:size-52"
                    aria-hidden
                >
                    <p className="text-2xl leading-tight font-semibold tracking-tight uppercase sm:text-3xl md:text-4xl">
                        Save
                        <br />
                        More
                    </p>
                </div>
            </div>
        </div>
    );
}
