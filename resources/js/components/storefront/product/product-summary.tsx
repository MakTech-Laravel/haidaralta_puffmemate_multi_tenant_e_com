import { router } from '@inertiajs/react';
import { Check, Clock, Minus, Plus, ShoppingCart, Star } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type ProductSummaryProps = {
    slug: string;
    name: string;
    description: string;
    badges: string[];
    price: string;
    compareAtPrice: string;
    stock: number;
    rating: number;
    reviewCount: number;
    about: string;
    className?: string;
};

export function ProductSummary({
    slug,
    name,
    description,
    badges,
    price,
    compareAtPrice,
    stock,
    rating,
    reviewCount,
    about,
    className,
}: ProductSummaryProps) {
    const [quantity, setQuantity] = useState(1);

    return (
        <div
            className={cn(
                'flex w-full max-w-full min-w-0 flex-col gap-5',
                className,
            )}
        >
            <div className="mt-5 flex flex-wrap gap-2">
                {badges.map((badge) => (
                    <span
                        key={badge}
                        className="bg-foreground text-background font-inter rounded-md px-3 py-2 text-xs font-semibold"
                    >
                        {badge}
                    </span>
                ))}
            </div>

            <div className="mt-5 min-w-0">
                <h1 className="text-foreground font-inter text-3xl font-semibold break-words sm:text-5xl">
                    {name}
                </h1>
                <p className="text-muted-foreground font-inter mt-2 text-base font-normal break-words">
                    {description}
                </p>
            </div>

            <div className="text-foreground flex min-w-0 flex-wrap items-center gap-2 text-sm">
                <div
                    className="flex shrink-0 items-center gap-0.5"
                    aria-label={`${rating} out of 5 stars`}
                >
                    {Array.from({ length: 5 }).map((_, index) => (
                        <Star
                            key={index}
                            className={cn(
                                'size-4',
                                index < Math.round(rating)
                                    ? 'fill-review-star text-review-star'
                                    : 'text-muted-foreground',
                            )}
                        />
                    ))}
                </div>
                <span className="text-foreground font-inter text-base font-normal">
                    {rating} · {reviewCount} reviews
                </span>
            </div>

            <div className="flex min-w-0 flex-wrap items-center gap-3">
                <span className="text-primary text-3xl font-bold">{price}</span>
                {compareAtPrice ? (
                    <span className="text-muted-foreground font-inter text-base font-normal line-through">
                        {compareAtPrice}
                    </span>
                ) : null}
                <span className="text-tertiary font-inter inline-flex items-center gap-1.5 text-sm font-medium">
                    <span
                        className="bg-tertiary size-1.5 shrink-0 rounded-full"
                        aria-hidden
                    />
                    {stock} available
                </span>
            </div>

            <div className="min-w-0">
                <h2 className="text-foreground font-inter mt-10 text-base font-bold">
                    About this product
                </h2>
                <p className="text-muted-foreground font-inter mt-2 text-sm leading-relaxed font-normal break-words">
                    {about}
                </p>
            </div>

            <div className="flex w-full max-w-full min-w-0 flex-col gap-3 sm:flex-row sm:items-center">
                <div className="border-border flex h-12 w-full max-w-full min-w-0 items-center rounded-none border sm:w-auto sm:max-w-none">
                    <button
                        type="button"
                        className="text-foreground flex size-12 shrink-0 items-center justify-center"
                        onClick={() =>
                            setQuantity((current) => Math.max(1, current - 1))
                        }
                        aria-label="Decrease quantity"
                    >
                        <Minus className="size-4" />
                    </button>
                    <span className="text-foreground min-w-0 flex-1 text-center text-sm font-semibold sm:min-w-10 sm:flex-none">
                        {quantity}
                    </span>
                    <button
                        type="button"
                        className="text-foreground flex size-12 shrink-0 items-center justify-center"
                        onClick={() => setQuantity((current) => current + 1)}
                        aria-label="Increase quantity"
                    >
                        <Plus className="size-4" />
                    </button>
                </div>
                <Button
                    type="button"
                    size="lg"
                    className="h-12 w-full max-w-full min-w-0 shrink rounded-none text-sm font-bold tracking-wide uppercase sm:flex-1"
                    onClick={() =>
                        router.post(
                            '/cart',
                            { product: slug, quantity },
                            {
                                preserveScroll: true,
                                onSuccess: () =>
                                    window.dispatchEvent(
                                        new Event('storefront:cart-open'),
                                    ),
                            },
                        )
                    }
                >
                    <ShoppingCart className="size-4" />
                    Add to cart
                </Button>
            </div>

            <div className="mt-5 flex min-w-0 flex-col gap-4 sm:flex-row sm:flex-wrap sm:gap-x-10">
                <div className="flex min-w-0 items-start gap-2.5">
                    <Clock className="text-primary mt-0.5 size-4 shrink-0" />
                    <div className="min-w-0">
                        <p className="text-foreground font-inter text-base leading-snug font-bold break-words">
                            Fast delivery from West Village
                        </p>
                        <p className="text-muted-foreground font-inter mt-0.5 text-xs font-normal">
                            20–28 min delivery estimate
                        </p>
                    </div>
                </div>
                <div className="flex min-w-0 items-start gap-2.5">
                    <Check className="text-primary mt-0.5 size-4 shrink-0" />
                    <div className="min-w-0">
                        <p className="text-foreground font-inter text-base leading-snug font-bold break-words">
                            Catalog pricing
                        </p>
                        <p className="text-muted-foreground font-inter mt-0.5 text-xs font-normal">
                            Stock confirmed at checkout
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
