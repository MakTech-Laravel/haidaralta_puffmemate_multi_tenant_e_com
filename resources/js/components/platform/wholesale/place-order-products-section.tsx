import { Minus, Plus, Search, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type {
    WholesalePlaceOrderLine,
    WholesalePlaceOrderProduct,
} from '@/components/platform/wholesale/place-order-types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const PAGE_SIZE = 8;

type PlaceOrderProductsSectionProps = {
    products: WholesalePlaceOrderProduct[];
    lines: WholesalePlaceOrderLine[];
    onAdd: (product: WholesalePlaceOrderProduct) => void;
    onQuantityChange: (key: string, quantity: number) => void;
    onRemove: (key: string) => void;
    disabled?: boolean;
};

function formatMoney(amount: number): string {
    return `$${amount.toFixed(2)}`;
}

export function PlaceOrderProductsSection({
    products,
    lines,
    onAdd,
    onQuantityChange,
    onRemove,
    disabled = false,
}: PlaceOrderProductsSectionProps) {
    const [productQuery, setProductQuery] = useState('');
    const [visibleCount, setVisibleCount] = useState(PAGE_SIZE);
    const sentinelRef = useRef<HTMLDivElement | null>(null);

    const selectedProductIds = useMemo(
        () => new Set(lines.map((line) => line.productId)),
        [lines],
    );

    const filteredProducts = useMemo(() => {
        const q = productQuery.trim().toLowerCase();

        return products.filter((product) => {
            if (selectedProductIds.has(product.id)) {
                return false;
            }

            if (product.stock < 1) {
                return false;
            }

            if (q === '') {
                return true;
            }

            return (
                product.name.toLowerCase().includes(q) ||
                product.sku.toLowerCase().includes(q)
            );
        });
    }, [products, productQuery, selectedProductIds]);

    useEffect(() => {
        setVisibleCount(PAGE_SIZE);
    }, [productQuery, selectedProductIds, disabled]);

    useEffect(() => {
        const sentinel = sentinelRef.current;

        if (sentinel === null || filteredProducts.length === 0 || disabled) {
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
                if (current >= filteredProducts.length) {
                    return current;
                }

                return Math.min(current + PAGE_SIZE, filteredProducts.length);
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
    }, [disabled, filteredProducts.length, productQuery, visibleCount]);

    const visibleProducts = filteredProducts.slice(0, visibleCount);
    const hasMoreProducts = visibleCount < filteredProducts.length;

    return (
        <div className="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_65rem] lg:items-start">
            <section className="border-border bg-card w-full min-w-0 space-y-4 rounded-2xl border p-4 shadow-sm">
                <div className="space-y-1.5">
                    <Label htmlFor="wholesale-product-search">Add products</Label>
                    <div className="relative">
                        <Search
                            className="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2"
                            aria-hidden
                        />
                        <Input
                            id="wholesale-product-search"
                            value={productQuery}
                            onChange={(event) =>
                                setProductQuery(event.target.value)
                            }
                            placeholder="Search by name or SKU"
                            disabled={disabled}
                            className="h-12 rounded-2xl pl-10"
                        />
                    </div>
                </div>

                <div className="max-h-96 min-w-0 overflow-x-hidden overflow-y-auto overscroll-contain">
                    {disabled ? (
                        <div className="text-muted-foreground rounded-2xl border border-dashed px-4 py-6 text-center text-sm">
                            Select a branch to browse stock.
                        </div>
                    ) : filteredProducts.length === 0 ? (
                        <div className="text-muted-foreground rounded-2xl border border-dashed px-4 py-6 text-center text-sm">
                            No matching products with available stock.
                        </div>
                    ) : (
                        <>
                            <ul className="space-y-2">
                                {visibleProducts.map((product) => (
                                    <li
                                        key={product.id}
                                        className="border-border flex min-w-0 items-center gap-3 rounded-2xl border p-3"
                                    >
                                        <div className="bg-muted relative size-12 shrink-0 overflow-hidden rounded-xl">
                                            {product.image ? (
                                                <img
                                                    src={product.image}
                                                    alt=""
                                                    className="absolute inset-0 size-full object-cover"
                                                />
                                            ) : null}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-foreground truncate text-sm font-semibold">
                                                {product.name}
                                            </p>
                                            <p className="text-muted-foreground truncate text-xs">
                                                {product.sku
                                                    ? `${product.sku} · `
                                                    : ''}
                                                {product.stock} available ·{' '}
                                                {formatMoney(product.price)}
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            className="shrink-0 rounded-xl"
                                            onClick={() => onAdd(product)}
                                        >
                                            Add
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                            {hasMoreProducts ? (
                                <div
                                    ref={sentinelRef}
                                    className="h-4 w-full"
                                    aria-hidden
                                />
                            ) : null}
                        </>
                    )}
                </div>
            </section>

            <aside className="border-border bg-card flex min-w-0 flex-col gap-4 rounded-2xl border p-4 shadow-sm lg:sticky lg:top-4">
                <div className="min-w-0 space-y-2">
                    <p className="text-foreground text-xs font-bold tracking-wide uppercase">
                        To order
                        {lines.length > 0 ? ` · ${lines.length}` : ''}
                    </p>

                    {lines.length === 0 ? (
                        <div className="text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                            Add products from the list to build this order.
                        </div>
                    ) : (
                        <ul className="max-h-96 space-y-2 overflow-y-auto overscroll-contain">
                            {lines.map((line) => {
                                const lineTotal = line.price * line.quantity;
                                const short = line.quantity > line.stock;

                                return (
                                    <li
                                        key={line.key}
                                        className="border-border flex min-w-0 flex-wrap items-center justify-between gap-2 rounded-2xl border p-3"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="text-foreground truncate text-sm font-semibold">
                                                {line.name}
                                            </p>
                                            <p className="text-muted-foreground truncate text-xs">
                                                {line.sku
                                                    ? `${line.sku} · `
                                                    : ''}
                                                {line.stock} available
                                            </p>
                                        </div>
                                        <div className="bg-muted/70 inline-flex shrink-0 items-center gap-1 rounded-full p-1">
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                className="bg-background size-8 rounded-full shadow-sm"
                                                disabled={line.quantity <= 1}
                                                onClick={() =>
                                                    onQuantityChange(
                                                        line.key,
                                                        line.quantity - 1,
                                                    )
                                                }
                                            >
                                                <Minus className="size-3.5" />
                                            </Button>
                                            <span className="text-foreground w-8 text-center text-sm font-bold tabular-nums">
                                                {line.quantity}
                                            </span>
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                className="bg-background size-8 rounded-full shadow-sm"
                                                disabled={
                                                    line.quantity >= line.stock
                                                }
                                                onClick={() =>
                                                    onQuantityChange(
                                                        line.key,
                                                        line.quantity + 1,
                                                    )
                                                }
                                            >
                                                <Plus className="size-3.5" />
                                            </Button>
                                        </div>
                                        <span
                                            className={cn(
                                                'shrink-0 rounded-full px-2.5 py-1 text-xs font-bold tabular-nums',
                                                short
                                                    ? 'bg-destructive/10 text-destructive'
                                                    : 'bg-muted text-muted-foreground',
                                            )}
                                        >
                                            {formatMoney(lineTotal)}
                                        </span>
                                        <div className="shrink-0">
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                className="text-destructive"
                                                onClick={() =>
                                                    onRemove(line.key)
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </div>
            </aside>
        </div>
    );
}
