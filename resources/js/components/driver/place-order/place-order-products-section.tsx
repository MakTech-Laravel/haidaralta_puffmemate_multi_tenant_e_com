import { useEffect, useMemo, useRef, useState } from 'react';
import { Minus, Plus, Search } from 'lucide-react';
import type { DriverPlaceOrderProduct } from '@/components/driver/place-order/place-order-types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

const FALLBACK_IMAGE = '/images/storefront/logo.png';
const PAGE_SIZE = 12;
const FILTER_DEBOUNCE_MS = 200;
const RESULTS_VIEWPORT_CLASS = 'max-h-[min(28rem,55vh)]';

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

function ProductLine({
    product,
    quantity,
    onQuantityChange,
}: {
    product: DriverPlaceOrderProduct;
    quantity: number;
    onQuantityChange: (productId: string, quantity: number) => void;
}) {
    const outOfStock = product.stock < 1;
    const selected = quantity > 0;
    const meta = [
        product.sku ? product.sku : null,
        outOfStock ? 'Out of stock' : `${product.stock} left`,
    ]
        .filter(Boolean)
        .join(' · ');

    return (
        <li
            className={cn(
                'flex items-center gap-2.5 px-2 py-2 transition-colors',
                selected && !outOfStock && 'bg-primary/5',
                outOfStock && 'opacity-50',
            )}
        >
            <div className="bg-muted relative size-10 shrink-0 overflow-hidden rounded-lg">
                <img
                    src={product.image || FALLBACK_IMAGE}
                    alt=""
                    className="absolute inset-0 size-full object-cover"
                    onError={(event) => {
                        event.currentTarget.src = FALLBACK_IMAGE;
                    }}
                />
            </div>

            <div className="min-w-0 flex-1">
                <div className="flex items-baseline justify-between gap-2">
                    <p className="text-foreground truncate text-sm font-semibold leading-tight">
                        {product.name}
                    </p>
                    <p className="text-foreground shrink-0 text-sm font-bold tabular-nums">
                        {product.priceLabel}
                    </p>
                </div>
                {meta ? (
                    <p className="text-muted-foreground truncate text-[11px] leading-tight">
                        {meta}
                    </p>
                ) : null}
            </div>

            {outOfStock ? (
                <span className="text-muted-foreground shrink-0 text-[11px] font-semibold">
                    —
                </span>
            ) : (
                <div className="bg-muted/80 inline-flex shrink-0 items-center rounded-full p-0.5">
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="bg-background size-7 rounded-full shadow-sm"
                        disabled={quantity < 1}
                        aria-label={`Decrease ${product.name}`}
                        onClick={() =>
                            onQuantityChange(
                                product.id,
                                Math.max(0, quantity - 1),
                            )
                        }
                    >
                        <Minus className="size-3" />
                    </Button>
                    <span className="text-foreground w-6 text-center text-sm font-bold tabular-nums">
                        {quantity}
                    </span>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="bg-background size-7 rounded-full shadow-sm"
                        disabled={quantity >= product.stock}
                        aria-label={`Increase ${product.name}`}
                        onClick={() =>
                            onQuantityChange(
                                product.id,
                                Math.min(product.stock, quantity + 1),
                            )
                        }
                    >
                        <Plus className="size-3" />
                    </Button>
                </div>
            )}
        </li>
    );
}

export function PlaceOrderProductsSection({
    products,
    quantities,
    onQuantityChange,
}: PlaceOrderProductsSectionProps) {
    const [query, setQuery] = useState('');
    const [debouncedQuery, setDebouncedQuery] = useState('');
    const [selectedOnly, setSelectedOnly] = useState(false);
    const [visibleCount, setVisibleCount] = useState(PAGE_SIZE);
    const sentinelRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        const timer = window.setTimeout(() => {
            setDebouncedQuery(query.trim());
        }, FILTER_DEBOUNCE_MS);

        return () => window.clearTimeout(timer);
    }, [query]);

    const isFiltering = query.trim() !== debouncedQuery;

    const selectedCount = useMemo(
        () =>
            products.reduce(
                (count, product) =>
                    count + ((quantities[product.id] ?? 0) > 0 ? 1 : 0),
                0,
            ),
        [products, quantities],
    );

    const filtered = useMemo(() => {
        const q = debouncedQuery.toLowerCase();

        const rows = products.filter((product) => {
            if (selectedOnly && (quantities[product.id] ?? 0) < 1) {
                return false;
            }

            return productMatchesQuery(product, q);
        });

        return rows.sort((a, b) => {
            const aSelected = (quantities[a.id] ?? 0) > 0 ? 0 : 1;
            const bSelected = (quantities[b.id] ?? 0) > 0 ? 0 : 1;

            if (aSelected !== bSelected) {
                return aSelected - bSelected;
            }

            return a.name.localeCompare(b.name);
        });
    }, [products, debouncedQuery, selectedOnly, quantities]);

    useEffect(() => {
        if (selectedCount === 0) {
            setSelectedOnly(false);
        }
    }, [selectedCount]);

    useEffect(() => {
        setVisibleCount(PAGE_SIZE);
    }, [debouncedQuery, selectedOnly]);

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
    }, [filtered.length, debouncedQuery, selectedOnly, visibleCount]);

    const visibleResults = filtered.slice(0, visibleCount);
    const hasMoreResults = visibleCount < filtered.length;

    return (
        <div className="space-y-3">
            <div className="space-y-1">
                <div className="relative">
                    <Search
                        className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                        aria-hidden
                    />
                    <Input
                        id="place-order-product-search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Filter name, SKU…"
                        autoComplete="off"
                        aria-label="Filter products"
                        aria-busy={isFiltering}
                        className="h-10 rounded-xl pr-9 pl-9"
                    />
                    {isFiltering ? (
                        <Spinner className="text-primary absolute top-1/2 right-3 size-4 -translate-y-1/2" />
                    ) : null}
                </div>
                {isFiltering ? (
                    <p className="text-muted-foreground text-[11px]">
                        Filtering…
                    </p>
                ) : null}
            </div>

            {selectedCount > 0 ? (
                <div className="flex">
                    <button
                        type="button"
                        aria-pressed={selectedOnly}
                        className={cn(
                            'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold transition-colors',
                            selectedOnly
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground hover:text-foreground',
                        )}
                        onClick={() => setSelectedOnly((current) => !current)}
                    >
                        In order ({selectedCount})
                    </button>
                </div>
            ) : null}

            {products.length === 0 ? (
                <div className="border-border/70 text-muted-foreground rounded-xl border border-dashed px-3 py-5 text-center text-sm">
                    No assigned stock to sell.
                </div>
            ) : filtered.length === 0 ? (
                <div className="border-border/70 text-muted-foreground rounded-xl border border-dashed px-3 py-5 text-center text-sm">
                    No products match.
                </div>
            ) : (
                <div
                    className={cn(
                        'border-border/80 overflow-y-auto overscroll-contain rounded-xl border',
                        RESULTS_VIEWPORT_CLASS,
                    )}
                >
                    <ul className="divide-border/70 divide-y">
                        {visibleResults.map((product) => (
                            <ProductLine
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
                            className="h-3 w-full"
                            aria-hidden
                        />
                    ) : (
                        <p className="text-muted-foreground px-2 py-1.5 text-center text-[10px]">
                            {filtered.length} product
                            {filtered.length === 1 ? '' : 's'}
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
