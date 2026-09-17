import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowDownToLine,
    Building2,
    Layers,
    Minus,
    Package,
    Plus,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import {
    InfiniteScrollMultiCombobox,
    type MultiComboboxOption,
} from '@/components/stock/infinite-scroll-multi-combobox';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    isBranchLocked,
    isProductLocked,
    type StockOption,
    type StockPageContext,
} from '@/lib/stock-query';

function parseQuantity(value: string): number {
    if (value.trim() === '') {
        return 0;
    }

    const parsed = Number.parseInt(value, 10);

    return Number.isFinite(parsed) && parsed >= 0 ? parsed : 0;
}

function toOption(row: StockOption): MultiComboboxOption {
    return {
        value: String(row.id),
        label: row.name,
        description: row.sku ?? null,
    };
}

function toggleOption(
    items: MultiComboboxOption[],
    option: MultiComboboxOption,
    selected: boolean,
): MultiComboboxOption[] {
    if (selected) {
        if (items.some((item) => item.value === option.value)) {
            return items;
        }

        return [...items, option];
    }

    return items.filter((item) => item.value !== option.value);
}

function LockedValue({ children }: { children: string }) {
    return (
        <div className="bg-muted/60 flex h-9 items-center rounded-md border px-3 text-sm font-medium">
            <span className="truncate">{children}</span>
        </div>
    );
}

export function ImportStockPage({
    context,
    createProductUrl,
    storeUrl,
    productOptionsUrl,
    branchOptionsUrl,
    lockBranch = false,
}: {
    context: StockPageContext;
    createProductUrl: string;
    storeUrl: string;
    productOptionsUrl: string;
    branchOptionsUrl?: string;
    lockBranch?: boolean;
}) {
    const branchLocked = lockBranch || isBranchLocked(context.locked);
    const productLocked = isProductLocked(context.locked);
    const [branches, setBranches] = useState<MultiComboboxOption[]>(
        context.branch ? [toOption(context.branch)] : [],
    );
    const [products, setProducts] = useState<MultiComboboxOption[]>(
        context.product ? [toOption(context.product)] : [],
    );
    const form = useForm({
        branch_ids: context.branch ? [context.branch.id] : ([] as number[]),
        product_ids: context.product ? [context.product.id] : ([] as number[]),
        quantity: '1',
    });
    const quantityEmpty = form.data.quantity.trim() === '';
    const quantityValue = parseQuantity(form.data.quantity);
    const pairCount = form.data.branch_ids.length * form.data.product_ids.length;
    const saveDisabled =
        form.processing ||
        quantityEmpty ||
        quantityValue < 1 ||
        form.data.branch_ids.length === 0 ||
        form.data.product_ids.length === 0;

    const setQuantity = (next: number) => {
        form.setData('quantity', String(Math.max(0, next)));
    };

    const toggleBranches = (
        option: MultiComboboxOption,
        selected: boolean,
    ): void => {
        const nextItems = toggleOption(branches, option, selected);
        setBranches(nextItems);
        form.setData(
            'branch_ids',
            nextItems.map((item) => Number(item.value)),
        );
    };

    const toggleProducts = (
        option: MultiComboboxOption,
        selected: boolean,
    ): void => {
        const nextItems = toggleOption(products, option, selected);
        setProducts(nextItems);
        form.setData(
            'product_ids',
            nextItems.map((item) => Number(item.value)),
        );
    };

    return (
        <>
            <Head title="Import stock" />
            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Import stock"
                    description={
                        lockBranch
                            ? 'Add the same New entry quantity to many products at this branch'
                            : 'Add the same New entry quantity to many branch and product pairs'
                    }
                />

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(17.5rem,20rem)]">
                    <section className="self-start rounded-2xl border border-border/80 bg-card p-5 shadow-sm sm:p-6">
                        <form
                            className="flex flex-col gap-5"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.transform((data) => ({
                                    ...data,
                                    quantity:
                                        data.quantity.trim() === ''
                                            ? ''
                                            : parseQuantity(data.quantity),
                                }));
                                form.post(storeUrl, { preserveScroll: true });
                            }}
                        >
                            <div className="space-y-1">
                                <h2 className="text-base font-semibold tracking-tight">
                                    {lockBranch
                                        ? 'Products'
                                        : 'Branches and products'}
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    {lockBranch
                                        ? 'Select one or more products. Each receives the same New entry quantity.'
                                        : 'Select one or more of each. Every pair receives the same New entry quantity.'}
                                </p>
                            </div>

                            <div
                                className={
                                    lockBranch
                                        ? 'grid gap-4'
                                        : 'grid gap-4 md:grid-cols-2'
                                }
                            >
                                {lockBranch ? null : (
                                    <div className="grid gap-2">
                                        <Label htmlFor="import-branch">
                                            Branches
                                        </Label>
                                        {branchLocked && context.branch ? (
                                            <LockedValue>
                                                {context.branch.name}
                                            </LockedValue>
                                        ) : branchOptionsUrl ? (
                                            <InfiniteScrollMultiCombobox
                                                id="import-branch"
                                                optionsUrl={branchOptionsUrl}
                                                values={form.data.branch_ids.map(
                                                    String,
                                                )}
                                                selectedItems={branches}
                                                onToggle={toggleBranches}
                                                placeholder="Select branches"
                                                searchPlaceholder="Search branches…"
                                                emptyText="No branches found."
                                                descriptionKey={null}
                                            />
                                        ) : null}
                                        <InputError
                                            message={form.errors.branch_ids}
                                        />
                                    </div>
                                )}
                                <div className="grid gap-2">
                                    <div className="flex items-center justify-between gap-2">
                                        <Label htmlFor="import-product">
                                            Products
                                        </Label>
                                        <Link
                                            href={createProductUrl}
                                            className="text-primary text-xs font-medium hover:underline"
                                        >
                                            Add product
                                        </Link>
                                    </div>
                                    {productLocked && context.product ? (
                                        <LockedValue>
                                            {context.product.sku
                                                ? `${context.product.name} · ${context.product.sku}`
                                                : context.product.name}
                                        </LockedValue>
                                    ) : (
                                        <InfiniteScrollMultiCombobox
                                            id="import-product"
                                            optionsUrl={productOptionsUrl}
                                            values={form.data.product_ids.map(
                                                String,
                                            )}
                                            selectedItems={products}
                                            onToggle={toggleProducts}
                                            placeholder="Select products"
                                            searchPlaceholder="Search products…"
                                            emptyText="No products found."
                                        />
                                    )}
                                    <InputError
                                        message={form.errors.product_ids}
                                    />
                                </div>
                            </div>

                            <div className="flex flex-col gap-5 border-t border-border/70 pt-6">
                                <div className="space-y-1">
                                    <h2 className="text-base font-semibold tracking-tight">
                                        New entry
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        Import only adds stock. Use Catalog
                                        Manage to reduce or set a counted total.
                                    </p>
                                </div>

                                <div className="grid max-w-sm gap-2">
                                    <Label htmlFor="quantity">Quantity</Label>
                                    <div className="flex items-stretch overflow-hidden rounded-xl border border-border/80 bg-background shadow-xs">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="text-foreground h-12 w-12 shrink-0 rounded-none border-r border-border/80"
                                            aria-label="Decrease quantity"
                                            disabled={quantityValue <= 0}
                                            onClick={() =>
                                                setQuantity(quantityValue - 1)
                                            }
                                        >
                                            <Minus className="size-4" />
                                        </Button>
                                        <Input
                                            id="quantity"
                                            type="text"
                                            inputMode="numeric"
                                            autoComplete="off"
                                            placeholder="0"
                                            aria-label="Quantity"
                                            className="h-12 flex-1 rounded-none border-0 bg-transparent text-center text-xl font-semibold tabular-nums shadow-none focus-visible:ring-0"
                                            value={form.data.quantity}
                                            onChange={(event) => {
                                                const next = event.target.value;

                                                if (
                                                    next === '' ||
                                                    /^\d+$/.test(next)
                                                ) {
                                                    form.setData(
                                                        'quantity',
                                                        next,
                                                    );
                                                }
                                            }}
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="text-foreground h-12 w-12 shrink-0 rounded-none border-l border-border/80"
                                            aria-label="Increase quantity"
                                            onClick={() =>
                                                setQuantity(quantityValue + 1)
                                            }
                                        >
                                            <Plus className="size-4" />
                                        </Button>
                                    </div>
                                    <p className="text-muted-foreground text-xs">
                                        {quantityEmpty
                                            ? 'Enter how many units to add to each pair.'
                                            : pairCount === 0
                                              ? 'Added to on-hand stock on every selected pair.'
                                              : `${form.data.branch_ids.length} × ${form.data.product_ids.length} → ${pairCount} new ${pairCount === 1 ? 'entry' : 'entries'} of ${quantityValue}`}
                                    </p>
                                    <InputError message={form.errors.quantity} />
                                </div>

                                <div>
                                    <Button
                                        type="submit"
                                        disabled={saveDisabled}
                                    >
                                        Import stock
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </section>

                    <aside className="grid gap-4 sm:grid-cols-3 xl:grid-cols-1">
                        <DemoStatCard
                            label="Type"
                            value="New entry"
                            hint="Always added to on-hand stock"
                            icon={ArrowDownToLine}
                            valueClassName="text-lg"
                        />
                        <DemoStatCard
                            label="Pairs"
                            value={pairCount === 0 ? '—' : String(pairCount)}
                            hint={
                                lockBranch
                                    ? `${form.data.product_ids.length} products at this branch`
                                    : `${form.data.branch_ids.length} branches × ${form.data.product_ids.length} products`
                            }
                            icon={Layers}
                        />
                        {lockBranch ? null : (
                            <DemoStatCard
                                label="Branches"
                                value={
                                    form.data.branch_ids.length === 0
                                        ? 'Not selected'
                                        : String(form.data.branch_ids.length)
                                }
                                hint={
                                    branches
                                        .map((branch) => branch.label)
                                        .join(', ') ||
                                    'Stock is held at these locations'
                                }
                                icon={Building2}
                                valueClassName="truncate text-lg"
                            />
                        )}
                        <DemoStatCard
                            label="Products"
                            value={
                                form.data.product_ids.length === 0
                                    ? 'Not selected'
                                    : String(form.data.product_ids.length)
                            }
                            hint={
                                products
                                    .map((product) => product.label)
                                    .join(', ') || 'Catalog listings'
                            }
                            icon={Package}
                            valueClassName="truncate text-lg"
                        />
                    </aside>
                </div>
            </div>
        </>
    );
}
