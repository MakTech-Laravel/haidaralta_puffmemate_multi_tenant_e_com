import { CircleCheck, Minus, Plus, ShoppingCart, Star } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import type { StorefrontFeaturedPack } from '@/types/storefront';
import { cn } from '@/lib/utils';

const FALLBACK_IMAGE = '/images/storefront/logo.png';

type FeaturedPackProps = {
    pack: StorefrontFeaturedPack;
    className?: string;
};

export function FeaturedPack({ pack, className }: FeaturedPackProps) {
    const currency = pack.currency ?? 'AUD';
    const maxQuantity = Math.max(1, pack.stockLeft ?? 1);
    const [quantity, setQuantity] = useState(1);

    const changeQuantity = (next: number) => {
        setQuantity(Math.min(maxQuantity, Math.max(1, next)));
    };

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
                <div className="bg-muted relative aspect-square overflow-hidden rounded-xl">
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
                </div>

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
                        {pack.name}
                    </h2>
                    {pack.description ? (
                        <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                            {pack.description}
                        </p>
                    ) : null}

                    {pack.items.length > 0 ? (
                        <div className="bg-muted/60 mt-4 rounded-lg p-4">
                            <p className="text-foreground text-[11px] font-bold tracking-wider uppercase">
                                What's inside the selection
                            </p>
                            <ul className="mt-3 grid gap-2 sm:grid-cols-2">
                                {pack.items.map((item) => (
                                    <li
                                        key={item.name}
                                        className="text-foreground flex items-start gap-2 text-sm"
                                    >
                                        <CircleCheck className="text-primary mt-0.5 size-4 shrink-0" />
                                        <span className="min-w-0">
                                            {item.quantity}x {item.name}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}

                    <div className="mt-5">
                        {pack.savings ? (
                            <p className="text-muted-foreground text-sm">
                                Regular price:{' '}
                                <span className="line-through">
                                    AUD {pack.regularPrice} {currency}
                                </span>
                            </p>
                        ) : null}
                        <div className="mt-1 flex flex-wrap items-center gap-3">
                            <p className="text-foreground text-3xl font-extrabold tabular-nums">
                                AUD {pack.price}{' '}
                                <span className="text-muted-foreground text-base font-semibold">
                                    {currency}
                                </span>
                            </p>
                            {pack.savings ? (
                                <span className="bg-primary/10 text-foreground rounded-md px-2 py-0.5 text-xs font-bold uppercase">
                                    Save {currency} {pack.savings}
                                </span>
                            ) : null}
                        </div>
                    </div>

                    <div className="mt-5 flex items-center gap-3">
                        <div className="border-border flex h-11 shrink-0 items-center rounded-md border">
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-11 rounded-none"
                                onClick={() => changeQuantity(quantity - 1)}
                                disabled={quantity <= 1}
                                aria-label="Decrease quantity"
                            >
                                <Minus className="size-4" />
                            </Button>
                            <span className="w-8 text-center text-sm font-semibold tabular-nums">
                                {quantity}
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-11 rounded-none"
                                onClick={() => changeQuantity(quantity + 1)}
                                disabled={quantity >= maxQuantity}
                                aria-label="Increase quantity"
                            >
                                <Plus className="size-4" />
                            </Button>
                        </div>
                        <Button
                            type="button"
                            className="h-11 flex-1 rounded-md text-sm font-bold tracking-wide uppercase"
                        >
                            <ShoppingCart className="size-4" />
                            Buy now
                        </Button>
                    </div>
                </div>
            </div>
        </section>
    );
}
