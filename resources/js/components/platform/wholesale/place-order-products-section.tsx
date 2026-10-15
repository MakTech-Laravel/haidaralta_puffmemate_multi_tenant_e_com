import { Minus, Plus, Search, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import InputError from '@/components/input-error';
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
    /** When set, each line gets an editable unit price. */
    onPriceChange?: (key: string, price: number) => void;
    /** Server errors keyed by line key. */
    lineErrors?: Record<string, string | undefined>;
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
    onPriceChange,
    lineErrors = {},
    disabled = false,
}: PlaceOrderProductsSectionProps) {
    const [productQuery, setProductQuery] = useState('');
    const [visibleCount, setVisibleCount] = useState(PAGE_SIZE);
    const sentinelRef = useRef<HTMLDivElement | null>(null);

    const selectedProductIds = useMemo(
        () => new Set(lines.map((line) => line.key)),
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
        <div className="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] xl:items-start">
            <section className="border-border bg-card w-full min-w-0 space-y-4 rounded-2xl border p-4 shadow-sm">
                <div className="space-y-1.5">
                    <Label htmlFor="wholesale-product-search">
                        Add products
                    </Label>
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
                                                {product.is_wholesale_price
                                                    ? ' wholesale'
                                                    : ''}
                                                {product.min_quantity
                                                    ? ` · min ${product.min_quantity}`
                                                    : ''}
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

            <aside className="border-border bg-card flex min-w-0 flex-col gap-4 rounded-2xl border p-4 shadow-sm xl:sticky xl:top-4">
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
                                const belowMinimum =
                                    line.minQuantity != null &&
                                    line.quantity < line.minQuantity;
                                const lineError = lineErrors[line.key];

                                return (
                                    <li
                                        key={line.key}
                                        className="border-border flex min-w-0 flex-wrap items-center justify-between gap-2 rounded-2xl border p-3"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="text-foreground truncate text-sm font-semibold">
                                                {line.name}
                                            </p>
                                            <p
                                                className={cn(
                                                    'truncate text-xs',
                                                    short
                                                        ? 'text-destructive'
                                                        : 'text-muted-foreground',
                                                )}
                                            >
                                                {line.sku
                                                    ? `${line.sku} · `
                                                    : ''}
                                                {line.stock > 0
                                                    ? `${line.stock} available`
                                                    : 'Not stocked at this branch'}
                                            </p>
                                            {belowMinimum ? (
                                                <p className="text-xs font-medium text-amber-600 dark:text-amber-400">
                                                    Below wholesale minimum of{' '}
                                                    {line.minQuantity}
                                                </p>
                                            ) : null}
                                        </div>
                                        {onPriceChange ? (
                                            <div className="relative w-28 shrink-0">
                                                <span className="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-xs">
                                                    $
                                                </span>
                                                <Input
                                                    type="number"
                                                    inputMode="decimal"
                                                    step="0.01"
                                                    min={0}
                                                    aria-label={`${line.name} unit price`}
                                                    value={line.price}
                                                    onChange={(event) =>
                                                        onPriceChange(
                                                            line.key,
                                                            Number(
                                                                event.target
                                                                    .value,
                                                            ),
                                                        )
                                                    }
                                                    className="h-9 rounded-xl pl-5 text-right tabular-nums"
                                                />
                                            </div>
                                        ) : null}
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
                                            <input
                                                type="number"
                                                inputMode="numeric"
                                                min={1}
                                                max={Math.max(1, line.stock)}
                                                aria-label={`${line.name} quantity`}
                                                value={line.quantity}
                                                onChange={(event) =>
                                                    onQuantityChange(
                                                        line.key,
                                                        Number(
                                                            event.target.value,
                                                        ) || 1,
                                                    )
                                                }
                                                className="text-foreground w-12 [appearance:textfield] bg-transparent text-center text-sm font-bold tabular-nums outline-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                            />
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
                                        <InputError
                                            className="basis-full"
                                            message={lineError}
                                        />
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
