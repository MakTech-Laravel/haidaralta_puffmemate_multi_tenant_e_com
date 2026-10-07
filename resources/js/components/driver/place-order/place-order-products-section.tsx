import { useEffect, useMemo, useRef, useState } from 'react';
import { Minus, Plus, Search } from 'lucide-react';
import type { DriverPlaceOrderProduct } from '@/components/driver/place-order/place-order-types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const FALLBACK_IMAGE = '/images/storefront/logo.png';
const PAGE_SIZE = 3;
/** ~3 full ProductRow heights (padding + image + qty controls + gaps). */
const RESULTS_VIEWPORT_CLASS = 'max-h-[26rem]';

type PlaceOrderProductsSectionProps = {
    products: DriverPlaceOrderProduct[];
    quantities: Record<string, number>;
    onQuantityChange: (productId: string, quantity: number) => void;
};

function productMatchesQuery(
    product: DriverPlaceOrderProduct,
    query: string,
): boolean {
    if (query === '') {
        return true;
    }

    const haystack = [
        product.name,
        product.sku,
        product.description,
        product.category,
        product.priceLabel,
    ]
        .join(' ')
        .toLowerCase();

    return haystack.includes(query);
}

function ProductRow({
    product,
    quantity,
    onQuantityChange,
    compact = false,
}: {
    product: DriverPlaceOrderProduct;
    quantity: number;
    onQuantityChange: (productId: string, quantity: number) => void;
    compact?: boolean;
}) {
    const outOfStock = product.stock < 1;
    const selected = quantity > 0;
    const subtitle = product.sku || product.description;

    return (
        <li
            className={cn(
                'border-border flex gap-2 rounded-2xl border transition-colors',
                compact ? 'p-2' : 'gap-3 p-3',
                outOfStock && 'opacity-55',
                selected && !outOfStock && 'border-primary bg-primary/5',
            )}
        >
            <div
                className={cn(
                    'bg-muted relative shrink-0 overflow-hidden rounded-xl',
                    compact ? 'size-12' : 'size-16 sm:size-18',
                )}
            >
                <img
                    src={product.image || FALLBACK_IMAGE}
                    alt=""
                    className="absolute inset-0 size-full object-cover"
                    onError={(event) => {
                        event.currentTarget.src = FALLBACK_IMAGE;
                    }}
                />
            </div>

            <div
                className={cn(
                    'min-w-0 flex-1',
                    compact ? 'space-y-1.5' : 'space-y-2.5',
                )}
            >
                <div className="flex items-start justify-between gap-1.5">
                    <div className="min-w-0 space-y-0.5">
                        <p
                            className={cn(
                                'text-foreground truncate font-semibold',
                                compact ? 'text-xs leading-tight' : 'text-sm',
                            )}
                        >
                            {product.name}
                        </p>
                        {subtitle ? (
                            <p
                                className={cn(
                                    'text-muted-foreground line-clamp-1',
                                    compact ? 'text-[10px]' : 'text-xs',
                                )}
                            >
                                {product.sku
                                    ? `SKU ${product.sku}`
                                    : product.description}
                            </p>
                        ) : null}
                        <div className="flex flex-wrap items-center gap-1">
                            <span
                                className={cn(
                                    'bg-muted text-muted-foreground inline-flex rounded-full font-bold tracking-wide uppercase',
                                    compact
                                        ? 'px-1.5 py-0.5 text-[9px]'
                                        : 'px-2 py-0.5 text-[10px]',
                                )}
                            >
                                {product.category}
                            </span>
                            <span
                                className={cn(
                                    'inline-flex rounded-full font-bold tracking-wide uppercase',
                                    compact
                                        ? 'px-1.5 py-0.5 text-[9px]'
                                        : 'px-2 py-0.5 text-[10px]',
                                    outOfStock
                                        ? 'bg-destructive/10 text-destructive'
                                        : 'bg-muted text-muted-foreground',
                                )}
                            >
                                {outOfStock
                                    ? 'Out of stock'
                                    : `${product.stock} left`}
                            </span>
                        </div>
                    </div>
                    <p
                        className={cn(
                            'text-foreground shrink-0 font-bold tabular-nums',
                            compact ? 'text-xs' : 'text-sm',
                        )}
                    >
                        {product.priceLabel}
                    </p>
                </div>

                {outOfStock ? (
                    <p
                        className={cn(
                            'text-muted-foreground font-semibold',
                            compact ? 'text-[10px]' : 'text-xs',
                        )}
                    >
                        Unavailable
                    </p>
                ) : (
                    <div
                        className={cn(
                            'bg-muted/70 inline-flex items-center rounded-full',
                            compact ? 'gap-0.5 p-0.5' : 'gap-1 p-1',
                        )}
                    >
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            className={cn(
                                'bg-background rounded-full shadow-sm',
                                compact ? 'size-7' : 'size-8',
                            )}
                            disabled={quantity < 1}
                            aria-label={`Decrease ${product.name}`}
                            onClick={() =>
                                onQuantityChange(
                                    product.id,
                                    Math.max(0, quantity - 1),
                                )
                            }
                        >
                            <Minus
                                className={compact ? 'size-3' : 'size-3.5'}
                            />
                        </Button>
                        <span
                            className={cn(
                                'text-foreground text-center font-bold tabular-nums',
                                compact ? 'w-5 text-xs' : 'w-7 text-sm',
                            )}
                        >
                            {quantity}
                        </span>
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            className={cn(
                                'bg-background rounded-full shadow-sm',
                                compact ? 'size-7' : 'size-8',
                            )}
                            disabled={quantity >= product.stock}
                            aria-label={`Increase ${product.name}`}
                            onClick={() =>
                                onQuantityChange(
                                    product.id,
                                    Math.min(product.stock, quantity + 1),
                                )
                            }
                        >
                            <Plus
                                className={compact ? 'size-3' : 'size-3.5'}
                            />
                        </Button>
                    </div>
                )}
            </div>
        </li>
    );
}

export function PlaceOrderProductsSection({
    products,
    quantities,
    onQuantityChange,
}: PlaceOrderProductsSectionProps) {
    const [query, setQuery] = useState('');
    const [category, setCategory] = useState('All');
    const [visibleCount, setVisibleCount] = useState(PAGE_SIZE);
    const sentinelRef = useRef<HTMLDivElement | null>(null);

    const categories = useMemo(() => {
        const unique = Array.from(
            new Set(products.map((product) => product.category)),
        ).sort((a, b) => a.localeCompare(b));

        return ['All', ...unique];
    }, [products]);

    const selectedProducts = useMemo(
        () =>
            products.filter((product) => (quantities[product.id] ?? 0) > 0),
        [products, quantities],
    );

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        const categoryActive = category !== 'All';

        return products.filter((product) => {
            if (categoryActive && product.category !== category) {
                return false;
            }

            return productMatchesQuery(product, q);
        });
    }, [products, query, category]);

    useEffect(() => {
        setVisibleCount(PAGE_SIZE);
    }, [query, category]);

    useEffect(() => {
        const sentinel = sentinelRef.current;

        if (sentinel === null || filtered.length === 0) {
            return;
        }

        const root = sentinel.parentElement;

        if (root === null) {
            return;
        }

        const loadMoreIfNeeded = (): void => {
            const rootRect = root.getBoundingClientRect();
            const sentinelRect = sentinel.getBoundingClientRect();

            if (sentinelRect.top > rootRect.bottom + 40) {
                return;
            }

            setVisibleCount((current) => {
                if (current >= filtered.length) {
                    return current;
                }

                return Math.min(current + PAGE_SIZE, filtered.length);
            });
        };

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    loadMoreIfNeeded();
                }
            },
            { root, rootMargin: '40px', threshold: 0 },
        );

        observer.observe(sentinel);
        loadMoreIfNeeded();

        return () => observer.disconnect();
    }, [filtered.length, query, category, visibleCount]);

    const visibleResults = filtered.slice(0, visibleCount);
    const hasMoreResults = visibleCount < filtered.length;

    return (
        <div className="space-y-4">
            <div className="space-y-1.5">
                <Label htmlFor="place-order-product-search">Find product</Label>
                <div className="relative">
                    <Search
                        className="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2"
                        aria-hidden
                    />
                    <Input
                        id="place-order-product-search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Filter by name, SKU, or category"
                        autoComplete="off"
                        className="h-12 rounded-2xl pl-10"
                    />
                </div>
            </div>

            <div
                className="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1"
                role="tablist"
                aria-label="Product category"
            >
                {categories.map((item) => {
                    const active = category === item;

                    return (
                        <button
                            key={item}
                            type="button"
                            role="tab"
                            aria-selected={active}
                            className={cn(
                                'shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold transition-colors',
                                active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground hover:text-foreground',
                            )}
                            onClick={() => setCategory(item)}
                        >
                            {item}
                        </button>
                    );
                })}
            </div>

            {products.length === 0 ? (
                <div className="border-border/70 text-muted-foreground rounded-2xl border border-dashed px-4 py-6 text-center text-sm">
                    No assigned stock to sell.
                </div>
            ) : filtered.length === 0 ? (
                <div className="border-border/70 text-muted-foreground rounded-2xl border border-dashed px-4 py-6 text-center text-sm">
                    No assigned products match.
                </div>
            ) : (
                <div
                    className={cn(
                        'overflow-y-auto overscroll-contain',
                        RESULTS_VIEWPORT_CLASS,
                    )}
                >
                    <ul className="space-y-2.5">
                        {visibleResults.map((product) => (
                            <ProductRow
                                key={product.id}
                                product={product}
                                quantity={quantities[product.id] ?? 0}
                                onQuantityChange={onQuantityChange}
                            />
                        ))}
                    </ul>
                    {hasMoreResults ? (
                        <div
                            ref={sentinelRef}
                            className="h-4 w-full"
                            aria-hidden
                        />
                    ) : null}
                </div>
            )}

            {selectedProducts.length > 0 ? (
                <div className="space-y-2">
                    <p className="text-foreground text-xs font-bold tracking-wide uppercase">
                        In this order
                    </p>
                    <ul className="space-y-2">
                        {selectedProducts.map((product) => (
                            <ProductRow
                                key={`selected-${product.id}`}
                                product={product}
                                quantity={quantities[product.id] ?? 0}
                                onQuantityChange={onQuantityChange}
                                compact
                            />
                        ))}
                    </ul>
                </div>
            ) : null}
        </div>
    );
}
