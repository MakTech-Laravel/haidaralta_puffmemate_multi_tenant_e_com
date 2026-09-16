import { router, useForm, usePage } from '@inertiajs/react';
import { Boxes, Package, Tags } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import { SimplePagination } from '@/components/simple-pagination';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export type BranchInventorySnapshot = {
    quantity: number;
};

export type BranchPriceSnapshot = {
    selling_price: string | number;
    discount_type?: string;
    discount_price: string | number | null;
    start_date: string | null;
    end_date: string | null;
    status?: boolean;
};

export type BranchProductVariantRow = {
    id: number;
    name: string;
    sku: string | null;
    status?: boolean;
};

export type BranchInventoryTransaction = {
    id: number;
    type: string;
    quantity: number;
    before_quantity: number;
    after_quantity: number;
    variant_id: number | null;
    created_at: string | null;
};

export type TransactionsPage = {
    data: BranchInventoryTransaction[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    links: { url: string | null; label: string; active: boolean }[];
    filters: {
        scope: string;
        from: string | null;
        to: string | null;
    };
};

type ProductInventoryPanelProps = {
    variants: BranchProductVariantRow[];
    inventory: BranchInventorySnapshot | null;
    price: BranchPriceSnapshot | null;
    variantInventories: Record<string, BranchInventorySnapshot>;
    variantPrices: Record<string, BranchPriceSnapshot>;
    transactions: TransactionsPage;
    transactionTypes: { value: string; label: string }[];
    discountTypes: { value: string; label: string }[];
    inventoryUrl: string;
    priceUrl: string;
    showUrl: string;
};

const BASE_SCOPE = 'base';

function resolveInventory(
    scope: string,
    inventory: BranchInventorySnapshot | null,
    variantInventories: Record<string, BranchInventorySnapshot>,
): BranchInventorySnapshot | null {
    if (scope === BASE_SCOPE) {
        return inventory;
    }

    return variantInventories[scope] ?? null;
}

function resolvePrice(
    scope: string,
    price: BranchPriceSnapshot | null,
    variantPrices: Record<string, BranchPriceSnapshot>,
): BranchPriceSnapshot | null {
    if (scope === BASE_SCOPE) {
        return price;
    }

    return variantPrices[scope] ?? null;
}

function formatWhen(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function resolveDiscountType(price: BranchPriceSnapshot | null): string {
    if (price?.discount_type) {
        return price.discount_type;
    }

    return price?.discount_price != null && price.discount_price !== ''
        ? 'fixed'
        : 'none';
}

export function ProductInventoryPanel({
    variants,
    inventory,
    price,
    variantInventories,
    variantPrices,
    transactions,
    transactionTypes,
    discountTypes,
    inventoryUrl,
    priceUrl,
    showUrl,
}: ProductInventoryPanelProps) {
    const { catalog } = usePage().props;
    const showVariants = catalog.show_product_variants;
    const [scope, setScope] = useState(
        transactions.filters.scope || BASE_SCOPE,
    );
    const [txnFrom, setTxnFrom] = useState(transactions.filters.from ?? '');
    const [txnTo, setTxnTo] = useState(transactions.filters.to ?? '');
    const skipTxnReload = useRef(true);

    const activeVariants = useMemo(
        () =>
            showVariants
                ? variants.filter((variant) => variant.status !== false)
                : [],
        [showVariants, variants],
    );

    const activeScope = showVariants ? scope : BASE_SCOPE;
    const scopedInventory = resolveInventory(
        activeScope,
        inventory,
        variantInventories,
    );
    const scopedPrice = resolvePrice(activeScope, price, variantPrices);
    const variantId = activeScope === BASE_SCOPE ? null : Number(activeScope);
    const discountType = resolveDiscountType(scopedPrice);

    const inventoryForm = useForm({
        type: 'stock_in',
        quantity: 1,
        variant_id: variantId as number | null,
    });

    const priceForm = useForm({
        selling_price: scopedPrice?.selling_price ?? '',
        discount_type: discountType,
        discount_price: scopedPrice?.discount_price ?? '',
        start_date: scopedPrice?.start_date ?? '',
        end_date: scopedPrice?.end_date ?? '',
        status: scopedPrice?.status ?? true,
        variant_id: variantId as number | null,
    });

    useEffect(() => {
        const nextScope = showVariants ? scope : BASE_SCOPE;
        const nextPrice = resolvePrice(nextScope, price, variantPrices);
        const nextVariantId =
            nextScope === BASE_SCOPE ? null : Number(nextScope);
        const nextDiscountType = resolveDiscountType(nextPrice);

        inventoryForm.setData({
            type: 'stock_in',
            quantity: 1,
            variant_id: nextVariantId,
        });

        priceForm.setData({
            selling_price: nextPrice?.selling_price ?? '',
            discount_type: nextDiscountType,
            discount_price: nextPrice?.discount_price ?? '',
            start_date: nextPrice?.start_date ?? '',
            end_date: nextPrice?.end_date ?? '',
            status: nextPrice?.status ?? true,
            variant_id: nextVariantId,
        });
        // Only re-sync when scope or server snapshots change.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [
        scope,
        showVariants,
        inventory,
        price,
        variantInventories,
        variantPrices,
    ]);

    useEffect(() => {
        setTxnFrom(transactions.filters.from ?? '');
        setTxnTo(transactions.filters.to ?? '');
    }, [transactions.filters.from, transactions.filters.to]);

    const reloadTransactions = (next: {
        scope?: string;
        from?: string;
        to?: string;
    }) => {
        router.get(
            showUrl,
            {
                txn_scope: next.scope ?? activeScope,
                ...(next.from || txnFrom
                    ? { txn_from: next.from ?? txnFrom }
                    : {}),
                ...(next.to || txnTo ? { txn_to: next.to ?? txnTo } : {}),
            },
            {
                only: ['transactions'],
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

    useEffect(() => {
        if (skipTxnReload.current) {
            skipTxnReload.current = false;

            return;
        }

        reloadTransactions({ scope: activeScope });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activeScope]);

    const scopeLabel =
        activeScope === BASE_SCOPE
            ? 'Base product'
            : (activeVariants.find(
                  (variant) => String(variant.id) === activeScope,
              )?.name ?? 'Variant');

    const showDiscountFields = priceForm.data.discount_type !== 'none';

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div className="space-y-1">
                    <div className="flex items-center gap-2">
                        <Package className="text-muted-foreground size-4" />
                        <h2 className="text-base font-semibold tracking-tight">
                            Inventory & pricing
                        </h2>
                    </div>
                    <p className="text-muted-foreground text-sm">
                        Manage stock and price for {scopeLabel}.
                    </p>
                </div>
                {showVariants ? (
                    <div className="grid w-full gap-2 sm:max-w-[16rem]">
                        <Label htmlFor="inventory-scope">Scope</Label>
                        <Select value={activeScope} onValueChange={setScope}>
                            <SelectTrigger
                                id="inventory-scope"
                                className="w-full"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={BASE_SCOPE}>
                                    Base product
                                </SelectItem>
                                {activeVariants.map((variant) => (
                                    <SelectItem
                                        key={variant.id}
                                        value={String(variant.id)}
                                    >
                                        {variant.name}
                                        {variant.sku
                                            ? ` · ${variant.sku}`
                                            : ''}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                ) : null}
                <DemoStatCard
                    icon={Boxes}
                    label="Stock"
                    value={String(scopedInventory?.quantity ?? 0)}
                    hint="On-hand quantity"
                />
            </div>

            {showVariants && activeVariants.length > 0 ? (
                <Card className="gap-4 py-5 shadow-none">
                    <CardHeader className="px-5">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Tags className="text-muted-foreground size-4" />
                            Variants
                        </CardTitle>
                        <CardDescription>
                            Switch scope to manage a specific variant.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="px-5">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Variant</TableHead>
                                    <TableHead>Stock</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {activeVariants.map((variant) => {
                                    const rowInventory =
                                        variantInventories[String(variant.id)];
                                    const rowPrice =
                                        variantPrices[String(variant.id)];

                                    return (
                                        <TableRow key={variant.id}>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {variant.name}
                                                </div>
                                                <div className="text-muted-foreground text-xs">
                                                    {variant.sku ?? '—'}
                                                </div>
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {rowInventory?.quantity ?? 0}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {rowPrice?.selling_price ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setScope(
                                                            String(variant.id),
                                                        )
                                                    }
                                                >
                                                    Manage
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            ) : null}

            <Card className="gap-0 overflow-hidden py-0 shadow-none">
                <div className="grid xl:grid-cols-2">
                    <section className="flex flex-col border-border/70 xl:border-r">
                        <CardHeader className="gap-1 px-5 pt-5 pb-0">
                            <CardTitle className="text-base">
                                Adjust inventory
                            </CardTitle>
                            <CardDescription>
                                Stock in, stock out, or adjust on-hand quantity.
                            </CardDescription>
                        </CardHeader>
                        <form
                            className="flex min-h-0 flex-1 flex-col"
                            onSubmit={(event) => {
                                event.preventDefault();
                                inventoryForm.transform((data) => ({
                                    ...data,
                                    variant_id:
                                        activeScope === BASE_SCOPE
                                            ? null
                                            : Number(activeScope),
                                }));
                                inventoryForm.post(inventoryUrl, {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            <CardContent className="grid content-start gap-4 px-5 py-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="inventory-type">Type</Label>
                                    <Select
                                        value={inventoryForm.data.type}
                                        onValueChange={(value) =>
                                            inventoryForm.setData('type', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="inventory-type"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {transactionTypes.map((type) => (
                                                <SelectItem
                                                    key={type.value}
                                                    value={type.value}
                                                >
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="quantity">Quantity</Label>
                                    <Input
                                        id="quantity"
                                        type="number"
                                        min={0}
                                        value={inventoryForm.data.quantity}
                                        onChange={(event) =>
                                            inventoryForm.setData(
                                                'quantity',
                                                Number(event.target.value),
                                            )
                                        }
                                    />
                                    <InputError
                                        message={inventoryForm.errors.quantity}
                                    />
                                </div>
                            </CardContent>
                            <CardFooter className="mt-auto border-t border-border/60 px-5 py-4">
                                <Button
                                    type="submit"
                                    disabled={inventoryForm.processing}
                                >
                                    Save inventory
                                </Button>
                            </CardFooter>
                        </form>
                    </section>

                    <Separator className="xl:hidden" />

                    <section className="flex flex-col">
                        <CardHeader className="gap-1 px-5 pt-5 pb-0">
                            <CardTitle className="text-base">Pricing</CardTitle>
                            <CardDescription>
                                Selling price, optional discount, and active
                                status.
                            </CardDescription>
                        </CardHeader>
                        <form
                            className="flex min-h-0 flex-1 flex-col"
                            onSubmit={(event) => {
                                event.preventDefault();
                                priceForm.transform((data) => {
                                    const none = data.discount_type === 'none';

                                    return {
                                        ...data,
                                        variant_id:
                                            activeScope === BASE_SCOPE
                                                ? null
                                                : Number(activeScope),
                                        discount_price: none
                                            ? null
                                            : data.discount_price === ''
                                              ? null
                                              : data.discount_price,
                                        start_date: none
                                            ? null
                                            : data.start_date === ''
                                              ? null
                                              : data.start_date,
                                        end_date: none
                                            ? null
                                            : data.end_date === ''
                                              ? null
                                              : data.end_date,
                                    };
                                });
                                priceForm.put(priceUrl, {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            <CardContent className="grid content-start gap-4 px-5 py-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="selling_price">
                                        Selling price
                                    </Label>
                                    <Input
                                        id="selling_price"
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        value={priceForm.data.selling_price}
                                        onChange={(event) =>
                                            priceForm.setData(
                                                'selling_price',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            priceForm.errors.selling_price
                                        }
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="discount_type">
                                        Discount
                                    </Label>
                                    <Select
                                        value={priceForm.data.discount_type}
                                        onValueChange={(value) => {
                                            priceForm.setData({
                                                ...priceForm.data,
                                                discount_type: value,
                                                ...(value === 'none'
                                                    ? {
                                                          discount_price: '',
                                                          start_date: '',
                                                          end_date: '',
                                                      }
                                                    : {}),
                                            });
                                        }}
                                    >
                                        <SelectTrigger
                                            id="discount_type"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {discountTypes.map((type) => (
                                                <SelectItem
                                                    key={type.value}
                                                    value={type.value}
                                                >
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={
                                            priceForm.errors.discount_type
                                        }
                                    />
                                </div>

                                {showDiscountFields ? (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="discount_price">
                                                {priceForm.data
                                                    .discount_type === 'percent'
                                                    ? 'Discount percent'
                                                    : 'Discount price'}
                                            </Label>
                                            <Input
                                                id="discount_price"
                                                type="number"
                                                step="0.01"
                                                min={0}
                                                max={
                                                    priceForm.data
                                                        .discount_type ===
                                                    'percent'
                                                        ? 100
                                                        : undefined
                                                }
                                                value={
                                                    priceForm.data
                                                        .discount_price
                                                }
                                                onChange={(event) =>
                                                    priceForm.setData(
                                                        'discount_price',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <p className="text-muted-foreground text-xs">
                                                {priceForm.data
                                                    .discount_type === 'percent'
                                                    ? 'Percentage off the selling price (0–100).'
                                                    : 'Fixed sale price (must be ≤ selling price).'}
                                            </p>
                                            <InputError
                                                message={
                                                    priceForm.errors
                                                        .discount_price
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="start_date">
                                                    Start date
                                                </Label>
                                                <DatePicker
                                                    id="start_date"
                                                    value={
                                                        priceForm.data
                                                            .start_date
                                                    }
                                                    onChange={(next) =>
                                                        priceForm.setData(
                                                            'start_date',
                                                            next,
                                                        )
                                                    }
                                                    max={
                                                        priceForm.data
                                                            .end_date ||
                                                        undefined
                                                    }
                                                    placeholder="Optional start"
                                                    aria-invalid={Boolean(
                                                        priceForm.errors
                                                            .start_date,
                                                    )}
                                                />
                                                <InputError
                                                    message={
                                                        priceForm.errors
                                                            .start_date
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="end_date">
                                                    End date
                                                </Label>
                                                <DatePicker
                                                    id="end_date"
                                                    value={
                                                        priceForm.data.end_date
                                                    }
                                                    onChange={(next) =>
                                                        priceForm.setData(
                                                            'end_date',
                                                            next,
                                                        )
                                                    }
                                                    min={
                                                        priceForm.data
                                                            .start_date ||
                                                        undefined
                                                    }
                                                    placeholder="Optional end"
                                                    aria-invalid={Boolean(
                                                        priceForm.errors
                                                            .end_date,
                                                    )}
                                                />
                                                <InputError
                                                    message={
                                                        priceForm.errors
                                                            .end_date
                                                    }
                                                />
                                            </div>
                                        </div>
                                    </>
                                ) : null}

                                <div className="flex items-start gap-3 rounded-xl border border-border/70 bg-muted/20 px-4 py-3">
                                    <Checkbox
                                        id="price_status"
                                        checked={priceForm.data.status}
                                        className="mt-0.5"
                                        onCheckedChange={(checked) =>
                                            priceForm.setData(
                                                'status',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <div className="space-y-0.5">
                                        <Label
                                            htmlFor="price_status"
                                            className="font-medium"
                                        >
                                            Price active
                                        </Label>
                                        <p className="text-muted-foreground text-xs">
                                            Inactive prices are ignored at this
                                            branch.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter className="mt-auto border-t border-border/60 px-5 py-4">
                                <Button
                                    type="submit"
                                    disabled={priceForm.processing}
                                >
                                    Save price
                                </Button>
                            </CardFooter>
                        </form>
                    </section>
                </div>
            </Card>

            <Card className="gap-4 py-5 shadow-none">
                <CardHeader className="px-5">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div className="space-y-1">
                            <CardTitle className="text-base">
                                Recent transactions
                            </CardTitle>
                            <CardDescription>
                                Stock movements for {scopeLabel}
                                {transactions.meta.total > 0
                                    ? ` · ${transactions.meta.total} total`
                                    : ''}
                                .
                            </CardDescription>
                        </div>
                        <div className="flex w-full flex-col gap-3 sm:flex-row sm:items-end lg:max-w-xl">
                            <div className="grid flex-1 gap-2">
                                <Label htmlFor="txn_from">From</Label>
                                <DatePicker
                                    id="txn_from"
                                    value={txnFrom}
                                    onChange={setTxnFrom}
                                    max={txnTo || undefined}
                                    placeholder="Any start"
                                />
                            </div>
                            <div className="grid flex-1 gap-2">
                                <Label htmlFor="txn_to">To</Label>
                                <DatePicker
                                    id="txn_to"
                                    value={txnTo}
                                    onChange={setTxnTo}
                                    min={txnFrom || undefined}
                                    placeholder="Any end"
                                />
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    onClick={() =>
                                        reloadTransactions({
                                            from: txnFrom,
                                            to: txnTo,
                                        })
                                    }
                                >
                                    Filter
                                </Button>
                                {(txnFrom ||
                                    txnTo ||
                                    transactions.filters.from ||
                                    transactions.filters.to) && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setTxnFrom('');
                                            setTxnTo('');
                                            router.get(
                                                showUrl,
                                                { txn_scope: activeScope },
                                                {
                                                    only: ['transactions'],
                                                    preserveScroll: true,
                                                    preserveState: true,
                                                    replace: true,
                                                },
                                            );
                                        }}
                                    >
                                        Clear
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4 px-5">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Type</TableHead>
                                <TableHead>Qty</TableHead>
                                <TableHead>Before</TableHead>
                                <TableHead>After</TableHead>
                                <TableHead>When</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {transactions.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="text-muted-foreground py-10 text-center"
                                    >
                                        No transactions for this scope
                                        {transactions.filters.from ||
                                        transactions.filters.to
                                            ? ' and date range'
                                            : ''}
                                        .
                                    </TableCell>
                                </TableRow>
                            ) : (
                                transactions.data.map((txn) => (
                                    <TableRow key={txn.id}>
                                        <TableCell className="font-medium capitalize">
                                            {txn.type.replaceAll('_', ' ')}
                                        </TableCell>
                                        <TableCell className="tabular-nums">
                                            {txn.quantity}
                                        </TableCell>
                                        <TableCell className="tabular-nums">
                                            {txn.before_quantity}
                                        </TableCell>
                                        <TableCell className="tabular-nums">
                                            {txn.after_quantity}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {formatWhen(txn.created_at)}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                    <SimplePagination links={transactions.links} />
                </CardContent>
            </Card>
        </div>
    );
}
