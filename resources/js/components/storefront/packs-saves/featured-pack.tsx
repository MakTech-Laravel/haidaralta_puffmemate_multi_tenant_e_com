import { Link } from '@inertiajs/react';
import { Star } from 'lucide-react';
import { PackBuyControls } from '@/components/storefront/packs-saves/pack-buy-controls';
import { PackInsideList } from '@/components/storefront/packs-saves/pack-inside-list';
import { show as packShow } from '@/routes/packs-saves';
import {
    formatStorefrontAud,
    type StorefrontFeaturedPack,
} from '@/types/storefront';
import { cn } from '@/lib/utils';

const FALLBACK_IMAGE = '/images/storefront/logo.png';

type FeaturedPackProps = {
    pack: StorefrontFeaturedPack;
    className?: string;
};

export function FeaturedPack({ pack, className }: FeaturedPackProps) {
    const detailHref = packShow.url({
        branch: pack.branchSlug,
        pack: pack.slug,
    });

    return (
        <section
            className={cn(
                'border-border bg-card relative rounded-2xl border p-5 shadow-sm sm:p-6',
                className,
            )}
        >
            <span className="bg-primary text-primary-foreground absolute -top-3 right-4 inline-flex items-center gap-1 rounded-full px-3 py-1 text-[10px] font-bold tracking-wider uppercase shadow-sm sm:right-6">
                <Star className="size-3 fill-current" />
                Weekly flagship saver
            </span>

            <div className="grid gap-6 md:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] md:gap-8">
                <Link
                    href={detailHref}
                    className="bg-muted relative block aspect-square overflow-hidden rounded-xl"
                >
                    <img
                        src={pack.image || FALLBACK_IMAGE}
                        alt={pack.name}
                        loading="lazy"
                        decoding="async"
                        className="absolute inset-0 size-full object-cover"
                        onError={(event) => {
                            event.currentTarget.src = FALLBACK_IMAGE;
                        }}
                    />
                </Link>

                <div className="flex min-w-0 flex-col">
                    <div className="flex flex-wrap items-center gap-2 text-xs">
                        {pack.discountPercent > 0 ? (
                            <span className="bg-primary text-primary-foreground rounded-md px-2 py-0.5 font-bold tracking-wide uppercase">
                                {pack.discountPercent}% instant discount
                            </span>
                        ) : null}
                        {pack.branchName ? (
                            <span className="text-muted-foreground">
                                {pack.branchName} live
                            </span>
                        ) : null}
                    </div>

                    <h2 className="text-foreground mt-3 text-2xl leading-tight font-extrabold tracking-tight sm:text-3xl">
                        <Link href={detailHref} className="hover:underline">
                            {pack.name}
                        </Link>
                    </h2>
                    {pack.description ? (
                        <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                            {pack.description}
                        </p>
                    ) : null}

                    <PackInsideList
                        title="What's inside the selection"
                        items={pack.items}
                        className="mt-4"
                    />

                    <div className="mt-5">
                        {pack.savings ? (
                            <p className="text-muted-foreground text-sm">
                                Regular price:{' '}
                                <span className="line-through">
                                    {formatStorefrontAud(pack.regularPrice)}
                                </span>
                            </p>
                        ) : null}
                        <div className="mt-1 flex flex-wrap items-center gap-3">
                            <p className="text-foreground text-3xl font-extrabold tabular-nums">
                                {formatStorefrontAud(pack.price)}
                            </p>
                            {pack.savings ? (
                                <span className="bg-primary/10 text-foreground rounded-md px-2 py-0.5 text-xs font-bold uppercase">
                                    Save {formatStorefrontAud(pack.savings)}
                                </span>
                            ) : null}
                        </div>
                    </div>

                    <PackBuyControls
                        stockLeft={pack.stockLeft}
                        className="mt-5"
                    />
                </div>
            </div>
        </section>
    );
}
