import { Head, Link, setLayoutProps, useForm } from "@inertiajs/react";
import { Minus, Plus, Search, Trash2 } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";
import DriverController from "@/actions/App/Http/Controllers/Branch/DriverController";
import DriverProductAssignController from "@/actions/App/Http/Controllers/Branch/DriverProductAssignController";
import Heading from "@/components/heading";
import {
    InfiniteScrollCombobox,
    type ComboboxOption,
} from "@/components/stock/infinite-scroll-combobox";
import {
    InfiniteScrollMultiCombobox,
    type MultiComboboxOption,
} from "@/components/stock/infinite-scroll-multi-combobox";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group";
import { cn } from "@/lib/utils";

const PAGE_SIZE = 8;

type Branch = { id: number; name: string; slug: string };

type ProductOption = {
    id: number;
    inventoryId: number;
    variantId: number | null;
    name: string;
    sku: string | null;
    image: string | null;
    available: number;
    priceLabel: string;
};

type LineDraft = {
    key: string;
    productId: number;
    variantId: number | null;
    name: string;
    available: number;
    quantity: number;
};

type Mode = "single" | "multiple";

type SelectedDriver = {
    id: number;
    name: string;
    phone: string | null;
};

export default function BranchDriversAssignProducts({
    branch,
    driverOptionsUrl,
    products,
}: {
    branch: Branch;
    driverOptionsUrl: string;
    products: ProductOption[];
}) {
    const [mode, setMode] = useState<Mode>("single");
    const [selectedDrivers, setSelectedDrivers] = useState<SelectedDriver[]>(
        [],
    );
    const [lines, setLines] = useState<LineDraft[]>([]);
    const [productQuery, setProductQuery] = useState("");
    const [visibleCount, setVisibleCount] = useState(PAGE_SIZE);
    const sentinelRef = useRef<HTMLDivElement | null>(null);

    const {
        post,
        processing,
        errors,
        setError,
        clearErrors,
        transform,
        reset,
    } = useForm({
        mode: "single" as Mode,
        driver_ids: [] as number[],
        items: [] as Array<{
            product_id: number;
            variant_id: number | null;
            quantity: number;
        }>,
    });

    setLayoutProps({
        breadcrumbs: [
            {
                title: "Dashboard",
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: "Drivers",
                href: DriverController.index.url(branch.slug),
            },
            {
                title: "Assign Product",
                href: DriverProductAssignController.create.url(branch.slug),
            },
        ],
    });

    const driverIds = selectedDrivers.map((driver) => driver.id);
    const driverCount = driverIds.length;
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

            if (q === "") {
                return true;
            }

            return (
                product.name.toLowerCase().includes(q) ||
                (product.sku?.toLowerCase().includes(q) ?? false)
            );
        });
    }, [products, productQuery, selectedProductIds]);

    useEffect(() => {
        setVisibleCount(PAGE_SIZE);
    }, [productQuery, selectedProductIds]);

    useEffect(() => {
        const sentinel = sentinelRef.current;

        if (sentinel === null || filteredProducts.length === 0) {
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
            { root, rootMargin: "40px", threshold: 0 },
        );

        observer.observe(sentinel);
        loadMoreIfNeeded();

        return () => observer.disconnect();
    }, [filteredProducts.length, productQuery, visibleCount]);

    const visibleProducts = filteredProducts.slice(0, visibleCount);
    const hasMoreProducts = visibleCount < filteredProducts.length;

    const canSubmit =
        driverCount > 0 &&
        lines.length > 0 &&
        lines.every(
            (line) =>
                line.quantity >= 1 &&
                line.quantity * Math.max(driverCount, 1) <= line.available,
        ) &&
        (mode === "multiple" || driverCount === 1);

    const multiSelectedItems: MultiComboboxOption[] = selectedDrivers.map(
        (driver) => ({
            value: String(driver.id),
            label: driver.name,
            description: driver.phone,
        }),
    );

    const handleModeChange = (next: string): void => {
        if (next !== "single" && next !== "multiple") {
            return;
        }

        setMode(next);
        setSelectedDrivers([]);
        clearErrors();
    };

    const handleSingleDriverChange = (
        value: string | null,
        option?: ComboboxOption,
    ): void => {
        if (value === null || option === undefined) {
            setSelectedDrivers([]);

            return;
        }

        setSelectedDrivers([
            {
                id: Number(value),
                name: option.label,
                phone: option.description ?? null,
            },
        ]);
    };

    const handleMultiToggle = (
        option: MultiComboboxOption,
        selected: boolean,
    ): void => {
        const id = Number(option.value);

        setSelectedDrivers((current) => {
            if (selected) {
                if (current.some((driver) => driver.id === id)) {
                    return current;
                }

                return [
                    ...current,
                    {
                        id,
                        name: option.label,
                        phone: option.description ?? null,
                    },
                ];
            }

            return current.filter((driver) => driver.id !== id);
        });
    };

    const addProduct = (product: ProductOption): void => {
        setLines((current) => [
            ...current,
            {
                key: `${product.id}-${product.variantId ?? "null"}`,
                productId: product.id,
                variantId: product.variantId,
                name: product.name,
                available: product.available,
                quantity: 1,
            },
        ]);
    };

    const updateQuantity = (key: string, quantity: number): void => {
        setLines((current) =>
            current.map((line) =>
                line.key === key
                    ? {
                          ...line,
                          quantity: Math.max(1, quantity),
                      }
                    : line,
            ),
        );
    };

    const removeLine = (key: string): void => {
        setLines((current) => current.filter((line) => line.key !== key));
    };

    const submit = (): void => {
        clearErrors();

        if (!canSubmit) {
            setError("items", "Select drivers and products with enough stock.");

            return;
        }

        transform(() => ({
            mode,
            driver_ids: driverIds,
            items: lines.map((line) => ({
                product_id: line.productId,
                variant_id: line.variantId,
                quantity: line.quantity,
            })),
        }));

        post(DriverProductAssignController.store.url(branch.slug), {
            preserveScroll: true,
            onSuccess: () => {
                setSelectedDrivers([]);
                setLines([]);
                setProductQuery("");
                reset();
            },
        });
    };

    return (
        <>
            <Head title="Assign Product" />

            <div className="flex w-full min-w-0 max-w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Assign Product"
                    description={`Move branch stock onto ${branch.name} drivers. Each selected driver receives the same quantity.`}
                />

                <section className="border-border bg-card space-y-4 rounded-2xl border p-4 shadow-sm">
                    <div className="space-y-2">
                        <Label>Assignment mode</Label>
                        <ToggleGroup
                            type="single"
                            value={mode}
                            onValueChange={handleModeChange}
                            className="bg-muted/80 grid w-full grid-cols-2 gap-1 rounded-2xl p-1.5"
                        >
                            <ToggleGroupItem
                                value="single"
                                className={cn(
                                    "rounded-xl px-3 py-3 text-sm font-semibold transition-colors",
                                    mode === "single"
                                        ? "bg-primary text-primary-foreground shadow-sm data-[state=on]:bg-primary data-[state=on]:text-primary-foreground"
                                        : "text-muted-foreground hover:text-foreground",
                                )}
                            >
                                Single driver
                            </ToggleGroupItem>
                            <ToggleGroupItem
                                value="multiple"
                                className={cn(
                                    "rounded-xl px-3 py-3 text-sm font-semibold transition-colors",
                                    mode === "multiple"
                                        ? "bg-primary text-primary-foreground shadow-sm data-[state=on]:bg-primary data-[state=on]:text-primary-foreground"
                                        : "text-muted-foreground hover:text-foreground",
                                )}
                            >
                                Multiple drivers
                            </ToggleGroupItem>
                        </ToggleGroup>
                        {errors.driver_ids ? (
                            <p className="text-destructive text-sm">
                                {errors.driver_ids}
                            </p>
                        ) : null}
                    </div>

                    {mode === "single" ? (
                        <div className="space-y-2">
                            <Label htmlFor="assign-driver-single">Driver</Label>
                            <InfiniteScrollCombobox
                                id="assign-driver-single"
                                optionsUrl={driverOptionsUrl}
                                value={
                                    selectedDrivers[0] !== undefined
                                        ? String(selectedDrivers[0].id)
                                        : null
                                }
                                selectedLabel={
                                    selectedDrivers[0] !== undefined
                                        ? selectedDrivers[0].phone
                                            ? `${selectedDrivers[0].name} · ${selectedDrivers[0].phone}`
                                            : selectedDrivers[0].name
                                        : null
                                }
                                onValueChange={handleSingleDriverChange}
                                placeholder="Search and select a driver"
                                searchPlaceholder="Search by name or phone…"
                                emptyText="No drivers found on this branch."
                                loadErrorText="Could not load drivers."
                                descriptionKey="sku"
                                className="h-12 rounded-2xl"
                            />
                        </div>
                    ) : (
                        <div className="space-y-2">
                            <Label htmlFor="assign-driver-multi">Drivers</Label>
                            <InfiniteScrollMultiCombobox
                                id="assign-driver-multi"
                                optionsUrl={driverOptionsUrl}
                                values={driverIds.map(String)}
                                selectedItems={multiSelectedItems}
                                onToggle={handleMultiToggle}
                                placeholder="Search and select drivers"
                                searchPlaceholder="Search by name or phone…"
                                emptyText="No drivers found on this branch."
                                className="min-h-12 rounded-2xl"
                            />
                        </div>
                    )}
                </section>

                <div className="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_35rem] lg:items-start">
                    <section className="border-border bg-card w-full min-w-0 space-y-4 rounded-2xl border p-4 shadow-sm">
                        <div className="space-y-1.5">
                            <Label htmlFor="assign-product-search">
                                Add products
                            </Label>
                            <div className="relative">
                                <Search
                                    className="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2"
                                    aria-hidden
                                />
                                <Input
                                    id="assign-product-search"
                                    value={productQuery}
                                    onChange={(event) =>
                                        setProductQuery(event.target.value)
                                    }
                                    placeholder="Search branch stock"
                                    className="h-12 rounded-2xl pl-10"
                                />
                            </div>
                        </div>

                        <div className="max-h-96 min-w-0 overflow-x-hidden overflow-y-auto overscroll-contain">
                            {filteredProducts.length === 0 ? (
                                <div className="text-muted-foreground rounded-2xl border border-dashed px-4 py-6 text-center text-sm">
                                    No matching products with available stock.
                                </div>
                            ) : (
                                <>
                                    <ul className="space-y-2">
                                        {visibleProducts.map((product) => (
                                            <li
                                                key={`${product.id}-${product.variantId ?? "null"}`}
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
                                                        {product.available}{" "}
                                                        available ·{" "}
                                                        {product.priceLabel}
                                                    </p>
                                                </div>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    className="shrink-0 rounded-xl"
                                                    onClick={() =>
                                                        addProduct(product)
                                                    }
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
                                To assign
                                {lines.length > 0
                                    ? ` · ${lines.length}`
                                    : ""}
                            </p>

                            {lines.length === 0 ? (
                                <div className="text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    Add products from the list to build this
                                    assignment.
                                </div>
                            ) : (
                                <ul className="max-h-96 space-y-2 overflow-y-auto overscroll-contain">
                                    {lines.map((line) => {
                                        const total =
                                            line.quantity *
                                            Math.max(driverCount, 1);
                                        const short = total > line.available;

                                        return (
                                            <li
                                                key={line.key}
                                                className="border-border flex min-w-0 flex-wrap items-center justify-between gap-2 rounded-2xl border p-3"
                                            >
                                                <div className="min-w-0 flex-1">
                                                    <p className="text-foreground truncate text-sm font-semibold">
                                                        {line.name}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {line.available}{" "}
                                                        available
                                                    </p>
                                                </div>
                                                <div className="bg-muted/70 inline-flex shrink-0 items-center gap-1 rounded-full p-1">
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        className="bg-background size-8 rounded-full shadow-sm"
                                                        disabled={
                                                            line.quantity <= 1
                                                        }
                                                        onClick={() =>
                                                            updateQuantity(
                                                                line.key,
                                                                line.quantity -
                                                                    1,
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
                                                        onClick={() =>
                                                            updateQuantity(
                                                                line.key,
                                                                line.quantity +
                                                                    1,
                                                            )
                                                        }
                                                    >
                                                        <Plus className="size-3.5" />
                                                    </Button>
                                                </div>
                                                <span
                                                    className={cn(
                                                        "shrink-0 rounded-full px-2.5 py-1 text-xs font-bold tabular-nums",
                                                        short
                                                            ? "bg-destructive/10 text-destructive"
                                                            : "bg-muted text-muted-foreground",
                                                    )}
                                                    title={
                                                        driverCount > 1
                                                            ? `${line.quantity} × ${driverCount} drivers`
                                                            : undefined
                                                    }
                                                >
                                                    −{total}
                                                </span>
                                                <div className="shrink-0">
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        className="text-destructive"
                                                        onClick={() =>
                                                            removeLine(
                                                                line.key,
                                                            )
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

                            {errors.items ? (
                                <p className="text-destructive text-sm">
                                    {errors.items}
                                </p>
                            ) : null}
                        </div>

                        <div className="flex flex-wrap items-center justify-end gap-3 border-t pt-4">
                            <Button
                                type="button"
                                variant="outline"
                                className="rounded-2xl"
                                asChild
                            >
                                <Link
                                    href={DriverController.index.url(
                                        branch.slug,
                                    )}
                                >
                                    Cancel
                                </Link>
                            </Button>
                            <Button
                                type="button"
                                className="rounded-2xl"
                                disabled={!canSubmit || processing}
                                onClick={submit}
                            >
                                Assign products
                            </Button>
                        </div>
                    </aside>
                </div>
            </div>
        </>
    );
}
