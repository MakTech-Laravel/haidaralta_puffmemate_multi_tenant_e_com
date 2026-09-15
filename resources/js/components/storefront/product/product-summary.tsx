import {
    Check,
    Clock,
    MapPin,
    Minus,
    Plus,
    ShoppingCart,
    Star,
} from "lucide-react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

type ProductSummaryProps = {
    name: string;
    description: string;
    badges: string[];
    price: string;
    compareAtPrice: string;
    stock: number;
    rating: number;
    reviewCount: number;
    about: string;
    branch: string;
    className?: string;
};

export function ProductSummary({
    name,
    description,
    badges,
    price,
    compareAtPrice,
    stock,
    rating,
    reviewCount,
    about,
    branch,
    className,
}: ProductSummaryProps) {
    const [quantity, setQuantity] = useState(1);

    return (
        <div className={cn("flex flex-col gap-5", className)}>
            <div className="flex flex-wrap gap-2">
                {badges.map((badge) => (
                    <span
                        key={badge}
                        className="bg-foreground text-background rounded-md px-3 py-2 text-xs font-semibold font-inter"
                    >
                        {badge}
                    </span>
                ))}
            </div>

            <div className="mt-5">
                <h1 className="text-foreground text-3xl font-semibold sm:text-5xl font-inter">
                    {name}
                </h1>
                <p className="text-muted-foreground mt-2 text-base font-normal font-inter">
                    {description}
                </p>
            </div>

            <div className="text-foreground flex items-center gap-2 text-sm">
                <div
                    className="flex items-center gap-0.5"
                    aria-label={`${rating} out of 5 stars`}
                >
                    {Array.from({ length: 5 }).map((_, index) => (
                        <Star
                            key={index}
                            className={cn(
                                "size-4",
                                index < Math.round(rating)
                                    ? "fill-review-star text-review-star"
                                    : "text-muted-foreground",
                            )}
                        />
                    ))}
                </div>
                <span className="text-foreground text-base font-normal font-inter">
                    {rating} · {reviewCount} reviews
                </span>
            </div>

            <div className="flex flex-wrap items-center gap-3">
                <span className="text-primary text-3xl font-bold">{price}</span>
                <span className="text-muted-foreground font-inter text-base font-normal line-through">
                    {compareAtPrice}
                </span>
                <span className="text-tertiary font-inter inline-flex items-center gap-1.5 text-sm font-medium">
                    <span
                        className="bg-tertiary size-1.5 shrink-0 rounded-full"
                        aria-hidden
                    />
                    {stock} available
                </span>
            </div>

            <div className="border-primary/25 bg-accent/40 flex items-center justify-between gap-3 border px-4 py-3 mt-5">
                <div className="flex min-w-0 items-center gap-3">
                    <span className="bg-accent text-primary flex size-10 shrink-0 items-center justify-center rounded-full">
                        <MapPin
                            className="size-5"
                            strokeWidth={2}
                            aria-hidden
                        />
                    </span>
                    <div className="min-w-0">
                        <p className="text-muted-foreground text-xs font-bold uppercase font-inter">
                            Selected branch
                        </p>
                        <p className="text-foreground truncate text-base font-bold font-inter">
                            {branch}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    className="text-primary shrink-0 text-base font-bold hover:underline font-inter"
                >
                    Change
                </button>
            </div>

            <div>
                <h2 className="text-foreground text-base font-bold font-inter mt-10">
                    About this product
                </h2>
                <p className="text-muted-foreground mt-2 text-sm leading-relaxed font-normal font-inter">
                    {about}
                </p>
            </div>

            <div className="flex w-full flex-col gap-3 sm:flex-row sm:items-center">
                <div className="border-border flex h-12 w-full items-center rounded-none border sm:w-auto">
                    <button
                        type="button"
                        className="text-foreground flex size-12 items-center justify-center"
                        onClick={() =>
                            setQuantity((current) => Math.max(1, current - 1))
                        }
                        aria-label="Decrease quantity"
                    >
                        <Minus className="size-4" />
                    </button>
                    <span className="text-foreground min-w-10 flex-1 text-center text-sm font-semibold sm:flex-none">
                        {quantity}
                    </span>
                    <button
                        type="button"
                        className="text-foreground flex size-12 items-center justify-center"
                        onClick={() => setQuantity((current) => current + 1)}
                        aria-label="Increase quantity"
                    >
                        <Plus className="size-4" />
                    </button>
                </div>
                <Button
                    type="button"
                    size="lg"
                    className="h-12 w-full rounded-none text-sm font-bold tracking-wide uppercase sm:flex-1"
                >
                    <ShoppingCart className="size-4" />
                    Add to cart
                </Button>
            </div>

            <div className="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:gap-x-10 mt-5">
                <div className="flex items-start gap-2.5">
                    <Clock className="text-primary mt-0.5 size-4 shrink-0" />
                    <div>
                        <p className="text-foreground font-inter text-base font-bold leading-snug">
                            Fast delivery from West Village
                        </p>
                        <p className="text-muted-foreground font-inter mt-0.5 text-xs font-normal">
                            20–28 min delivery estimate
                        </p>
                    </div>
                </div>
                <div className="flex items-start gap-2.5">
                    <Check className="text-primary mt-0.5 size-4 shrink-0" />
                    <div>
                        <p className="text-foreground font-inter text-base font-bold leading-snug">
                            Branch-accurate pricing
                        </p>
                        <p className="text-muted-foreground font-inter mt-0.5 text-xs font-normal">
                            Stock and price checked before checkout
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
