import { Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowDownToLine,
    ArrowUpFromLine,
    Minus,
    Package,
    Plus,
    SlidersHorizontal,
    Tags,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { DataTableTruncated } from '@/components/data-table';
import InputError from '@/components/input-error';
import { AdjustmentReasonNote } from '@/components/stock/adjustment-reason-note';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

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

type ProductInventoryPanelProps = {
    variants: BranchProductVariantRow[];
    inventory: BranchInventorySnapshot | null;
    price: BranchPriceSnapshot | null;
    variantInventories: Record<string, BranchInventorySnapshot>;
    variantPrices: Record<string, BranchPriceSnapshot>;
    transactionTypes: { value: string; label: string }[];
    adjustmentReasons: { value: string; label: string }[];
    discountTypes: { value: string; label: string }[];
    inventoryUrl: string;
    priceUrl: string;
    transactionsUrl: string;
};

function parseQuantity(value: string): number {
    if (value.trim() === '') {
        return 0;
    }

    const parsed = Number.parseInt(value, 10);

    return Number.isFinite(parsed) && parsed >= 0 ? parsed : 0;
}

function countedPreview(
    current: number,
    counted: number,
    empty: boolean,
): string {
    if (empty) {
        return 'Enter the counted on-hand quantity.';
    }

    if (counted === current) {
        return 'Counted quantity matches current stock.';
    }

    if (counted > current) {
        return `Current ${current} → ${counted} (add ${counted - current})`;
    }

    return `Current ${current} → ${counted} (remove ${current - counted})`;
}

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

function resolveDiscountType(price: BranchPriceSnapshot | null): string {
    if (price?.discount_type === 'fixed' || price?.discount_type === 'percent') {
        return price.discount_type;
    }

    return 'none';
}

function formatMoney(value: string | number | null | undefined): string {
    if (value == null || value === '') {
        return '—';
    }

    const amount = Number(value);

    return Number.isFinite(amount) ? amount.toFixed(2) : String(value);
}

function effectivePrice(price: BranchPriceSnapshot | null): string {
    if (!price) {
        return '—';
    }

    const selling = Number(price.selling_price);
    const type = resolveDiscountType(price);

    if (
        type === 'none' ||
        price.discount_price == null ||
        price.discount_price === '' ||
        !Number.isFinite(selling)
    ) {
        return formatMoney(price.selling_price);
    }

    const discount = Number(price.discount_price);

    if (!Number.isFinite(discount)) {
        return formatMoney(price.selling_price);
    }

    if (type === 'percent') {
        return formatMoney(
            Math.max(0, Math.round(selling * (1 - discount / 100) * 100) / 100),
        );
    }

    return formatMoney(Math.max(0, discount));
}

function SnapshotStat({
    label,
    value,
    hint,
}: {
    label: string;
    value: string;
    hint?: string;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-0.5 px-5 py-3">
            <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                {label}
            </p>
            <p className="text-lg font-semibold tracking-tight tabular-nums">
                {value}
            </p>
            {hint ? (
                <p className="text-muted-foreground truncate text-xs">{hint}</p>
            ) : null}
        </div>
    );
}

const INVENTORY_TYPE_ICONS: Record<string, LucideIcon> = {
    stock_in: ArrowDownToLine,
    stock_out: ArrowUpFromLine,
    adjustment: SlidersHorizontal,
};

export function ProductInventoryPanel({
    variants,
    inventory,
    price,
    variantInventories,
    variantPrices,
    transactionTypes,
    adjustmentReasons,
    discountTypes,
    inventoryUrl,
    priceUrl,
    transactionsUrl,
}: ProductInventoryPanelProps) {
    const { catalog } = usePage().props;
    const showVariants = catalog.show_product_variants;
    const [scope, setScope] = useState(BASE_SCOPE);

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
        quantity: '1',
        reason: '',
        reason_note: '',
        variant_id: variantId as number | null,
    });

    const priceForm = useForm({
        selling_price: scopedPrice?.selling_price ?? '',
        discount_type: discountType,
        discount_price: scopedPrice?.discount_price ?? '',
        start_date: scopedPrice?.start_date ?? '',
        end_date: scopedPrice?.end_date ?? '',
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
            quantity: '1',
            reason: '',
            reason_note: '',
            variant_id: nextVariantId,
        });

        priceForm.setData({
            selling_price: nextPrice?.selling_price ?? '',
            discount_type: nextDiscountType,
            discount_price: nextPrice?.discount_price ?? '',
            start_date: nextPrice?.start_date ?? '',
            end_date: nextPrice?.end_date ?? '',
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

    const scopeLabel =
        activeScope === BASE_SCOPE
            ? 'Base product'
            : (activeVariants.find(
                  (variant) => String(variant.id) === activeScope,
              )?.name ?? 'Variant');

    const showDiscountFields = priceForm.data.discount_type !== 'none';
    const sellingDisplay = formatMoney(scopedPrice?.selling_price);
    const effectiveDisplay = effectivePrice(scopedPrice);
    const hasLiveDiscount =
        scopedPrice != null &&
        resolveDiscountType(scopedPrice) !== 'none' &&
        effectiveDisplay !== sellingDisplay;
    const isAdjustment = inventoryForm.data.type === 'adjustment';
    const isOtherReason = isAdjustment && inventoryForm.data.reason === 'other';
    const currentStock = scopedInventory?.quantity ?? 0;
    const quantityEmpty = inventoryForm.data.quantity.trim() === '';
    const quantityValue = parseQuantity(inventoryForm.data.quantity);
    const variance = quantityValue - currentStock;
    const inventorySaveDisabled =
        inventoryForm.processing ||
        quantityEmpty ||
        (!isAdjustment && quantityValue < 1) ||
        (isAdjustment &&
            (inventoryForm.data.reason === '' ||
                variance === 0 ||
                (isOtherReason &&
                    inventoryForm.data.reason_note.trim() === '')));

    const selectInventoryType = (next: string) => {
        if (next === 'adjustment') {
            inventoryForm.setData({
                ...inventoryForm.data,
                type: next,
                quantity: String(currentStock),
            });

            return;
        }

        if (inventoryForm.data.type === 'adjustment') {
            inventoryForm.setData({
                ...inventoryForm.data,
                type: next,
                quantity: '1',
                reason: '',
                reason_note: '',
            });

            return;
        }

        inventoryForm.setData('type', next);
    };

    return (
        <div className="space-y-4">
            <Card className="gap-0 overflow-hidden py-0 shadow-sm">
                <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-4 border-b px-5 py-4">
                    <div className="min-w-0 space-y-1">
                        <CardTitle className="flex items-center gap-2">
                            <Package className="text-muted-foreground size-4" />
                            Inventory & pricing
                        </CardTitle>
                        <CardDescription>
                            Stock and selling price for {scopeLabel}
                        </CardDescription>
                    </div>
                    <div className="flex flex-wrap items-start gap-3">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={transactionsUrl}>
                                View transactions
                            </Link>
                        </Button>
                        {showVariants ? (
                        <div className="grid w-full min-w-0 gap-1.5 sm:max-w-[16rem]">
                            <Label htmlFor="inventory-scope">Scope</Label>
                            <Select
                                value={activeScope}
                                onValueChange={setScope}
                            >
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
                    </div>
                </CardHeader>

                <div className="grid divide-y border-b sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    <SnapshotStat
                        label="Stock"
                        value={String(scopedInventory?.quantity ?? 0)}
                        hint="On-hand units"
                    />
                    <SnapshotStat
                        label="Selling"
                        value={sellingDisplay}
                        hint={
                            scopedPrice == null ? 'No price on file' : 'AUD'
                        }
                    />
                    <SnapshotStat
                        label="Effective"
                        value={effectiveDisplay}
                        hint={
                            hasLiveDiscount
                                ? 'After discount'
                                : scopedPrice == null
                                  ? 'Set a selling price'
                                  : 'Same as selling'
                        }
                    />
                </div>

                {showVariants && activeVariants.length > 0 ? (
                    <div className="border-b px-5 py-4">
                        <div className="mb-3 flex items-center gap-2">
                            <Tags className="text-muted-foreground size-4" />
                            <p className="text-sm font-medium">Variants</p>
                        </div>
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
                                        variantInventories[
                                            String(variant.id)
                                        ];
                                    const rowPrice =
                                        variantPrices[String(variant.id)];

                                    return (
                                        <TableRow key={variant.id}>
                                            <TableCell className="max-w-48">
                                                <DataTableTruncated className="font-medium">
                                                    {variant.name}
                                                </DataTableTruncated>
                                                <DataTableTruncated className="text-muted-foreground text-xs">
                                                    {variant.sku ?? '—'}
                                                </DataTableTruncated>
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {rowInventory?.quantity ?? 0}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {rowPrice?.selling_price ??
                                                    '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setScope(
                                                            String(
                                                                variant.id,
                                                            ),
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
                    </div>
                ) : null}

                <div className="grid items-start lg:grid-cols-2">
                    <form
                        className="grid content-start gap-4 border-b p-5 lg:border-r lg:border-b-0"
                        onSubmit={(event) => {
                                event.preventDefault();
                                inventoryForm.transform((data) => ({
                                    ...data,
                                    variant_id:
                                        activeScope === BASE_SCOPE
                                            ? null
                                            : Number(activeScope),
                                    quantity:
                                        data.quantity.trim() === ''
                                            ? ''
                                            : parseQuantity(data.quantity),
                                    reason:
                                        data.type === 'adjustment'
                                            ? data.reason
                                            : null,
                                    reason_note:
                                        data.type === 'adjustment' &&
                                        data.reason === 'other'
                                            ? data.reason_note
                                            : null,
                                }));
                                inventoryForm.post(inventoryUrl, {
                                    preserveScroll: true,
                                });
                            }}
                    >
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0">
                                <h3 className="text-sm font-semibold">
                                    Stock
                                </h3>
                                <p className="text-muted-foreground text-xs">
                                    New entry, reduce, or counted on-hand
                                </p>
                            </div>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={inventorySaveDisabled}
                            >
                                Save
                            </Button>
                        </div>

                        <div className="grid gap-2">
                            <Label>Type</Label>
                            <div className="grid grid-cols-3 gap-2">
                                {transactionTypes.map((type) => {
                                    const Icon =
                                        INVENTORY_TYPE_ICONS[type.value] ??
                                        Package;
                                    const selected =
                                        inventoryForm.data.type === type.value;

                                    return (
                                        <Button
                                            key={type.value}
                                            type="button"
                                            variant={
                                                selected ? 'default' : 'outline'
                                            }
                                            className={cn(
                                                'h-auto min-h-9 flex-col gap-1 px-1 py-2 text-[11px] sm:h-9 sm:flex-row sm:gap-1.5 sm:px-2 sm:py-0 sm:text-xs',
                                                !selected && 'text-foreground',
                                            )}
                                            onClick={() =>
                                                selectInventoryType(type.value)
                                            }
                                        >
                                            <Icon className="size-3.5" />
                                            {type.label}
                                        </Button>
                                    );
                                })}
                            </div>
                        </div>

                        <div
                            className={cn(
                                'grid items-start gap-4',
                                isAdjustment ? 'sm:grid-cols-2' : 'grid-cols-1',
                            )}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="quantity">
                                    {isAdjustment
                                        ? 'Counted quantity'
                                        : 'Quantity'}
                                </Label>
                                <div className="flex items-stretch overflow-hidden rounded-md border border-border/80 bg-background">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="text-foreground h-9 w-10 shrink-0 rounded-none border-r border-border/80"
                                        aria-label="Decrease quantity"
                                        disabled={quantityValue <= 0}
                                        onClick={() =>
                                            inventoryForm.setData(
                                                'quantity',
                                                String(
                                                    Math.max(
                                                        0,
                                                        quantityValue - 1,
                                                    ),
                                                ),
                                            )
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
                                        className="h-9 flex-1 rounded-none border-0 bg-transparent text-center font-semibold tabular-nums shadow-none focus-visible:ring-0"
                                        value={inventoryForm.data.quantity}
                                        onChange={(event) => {
                                            const next = event.target.value;

                                            if (
                                                next === '' ||
                                                /^\d+$/.test(next)
                                            ) {
                                                inventoryForm.setData(
                                                    'quantity',
                                                    next,
                                                );
                                            }
                                        }}
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="text-foreground h-9 w-10 shrink-0 rounded-none border-l border-border/80"
                                        aria-label="Increase quantity"
                                        onClick={() =>
                                            inventoryForm.setData(
                                                'quantity',
                                                String(quantityValue + 1),
                                            )
                                        }
                                    >
                                        <Plus className="size-4" />
                                    </Button>
                                </div>
                                <p className="text-muted-foreground text-xs">
                                    {isAdjustment
                                        ? countedPreview(
                                              currentStock,
                                              quantityValue,
                                              quantityEmpty,
                                          )
                                        : inventoryForm.data.type ===
                                            'stock_out'
                                          ? 'Removed from on-hand stock'
                                          : 'Added to on-hand stock'}
                                </p>
                                <InputError
                                    message={inventoryForm.errors.quantity}
                                />
                            </div>
                            {isAdjustment ? (
                                <div className="grid content-start gap-2">
                                    <Label htmlFor="inventory-reason">
                                        Reason
                                    </Label>
                                    <Select
                                        value={
                                            inventoryForm.data.reason ||
                                            undefined
                                        }
                                        onValueChange={(value) =>
                                            inventoryForm.setData({
                                                ...inventoryForm.data,
                                                reason: value,
                                                reason_note:
                                                    value === 'other'
                                                        ? inventoryForm.data
                                                              .reason_note
                                                        : '',
                                            })
                                        }
                                    >
                                        <SelectTrigger
                                            id="inventory-reason"
                                            className="h-9 min-h-9 w-full data-[size=default]:h-9"
                                        >
                                            <SelectValue placeholder="Select a reason" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {adjustmentReasons.map(
                                                (reason) => (
                                                    <SelectItem
                                                        key={reason.value}
                                                        value={reason.value}
                                                    >
                                                        {reason.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={inventoryForm.errors.reason}
                                    />
                                </div>
                            ) : null}
                        </div>
                        {isOtherReason ? (
                            <AdjustmentReasonNote
                                id="inventory-reason-note"
                                value={inventoryForm.data.reason_note}
                                error={inventoryForm.errors.reason_note}
                                onChange={(value) =>
                                    inventoryForm.setData(
                                        'reason_note',
                                        value,
                                    )
                                }
                            />
                        ) : null}
                    </form>

                    <form
                        className="grid content-start gap-4 p-5"
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
                        <div className="flex items-center justify-between gap-3">
                            <div className="min-w-0">
                                <h3 className="text-sm font-semibold">
                                    Price
                                </h3>
                                <p className="text-muted-foreground text-xs">
                                    Selling price and optional discount
                                </p>
                            </div>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={priceForm.processing}
                            >
                                Save
                            </Button>
                        </div>

                        <div className="grid min-w-0 items-start gap-4 sm:grid-cols-2">
                            <div className="grid min-w-0 gap-2">
                                <Label htmlFor="selling_price">
                                    Selling price{' '}
                                    <span className="text-muted-foreground font-normal">
                                        (AUD)
                                    </span>
                                </Label>
                                <Input
                                    id="selling_price"
                                    type="number"
                                    step="0.01"
                                    min={0}
                                    placeholder="e.g. 16.50"
                                    value={priceForm.data.selling_price}
                                    onChange={(event) =>
                                        priceForm.setData(
                                            'selling_price',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={priceForm.errors.selling_price}
                                />
                            </div>

                            <div className="grid min-w-0 gap-2">
                                <Label htmlFor="discount_type">Discount</Label>
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
                                    message={priceForm.errors.discount_type}
                                />
                            </div>
                        </div>

                        {showDiscountFields ? (
                            <div className="grid min-w-0 items-start gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                <div className="grid min-w-0 gap-2">
                                    <Label htmlFor="discount_price">
                                        Discount value{' '}
                                        <span className="text-muted-foreground font-normal">
                                            {priceForm.data.discount_type ===
                                            'percent'
                                                ? '(%)'
                                                : '(AUD)'}
                                        </span>
                                    </Label>
                                    <Input
                                        id="discount_price"
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        max={
                                            priceForm.data.discount_type ===
                                            'percent'
                                                ? 100
                                                : undefined
                                        }
                                        placeholder={
                                            priceForm.data.discount_type ===
                                            'percent'
                                                ? 'e.g. 10'
                                                : 'e.g. 5.00'
                                        }
                                        value={priceForm.data.discount_price}
                                        onChange={(event) =>
                                            priceForm.setData(
                                                'discount_price',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        {priceForm.data.discount_type ===
                                        'percent'
                                            ? 'Off the selling price (0–100).'
                                            : 'Sale price, ≤ selling.'}
                                    </p>
                                    <InputError
                                        message={
                                            priceForm.errors.discount_price
                                        }
                                    />
                                </div>
                                <div className="grid min-w-0 gap-2">
                                    <Label htmlFor="start_date">
                                        Start date
                                    </Label>
                                    <DatePicker
                                        id="start_date"
                                        value={priceForm.data.start_date}
                                        onChange={(next) =>
                                            priceForm.setData(
                                                'start_date',
                                                next,
                                            )
                                        }
                                        max={
                                            priceForm.data.end_date ||
                                            undefined
                                        }
                                        placeholder="Optional start"
                                        aria-invalid={Boolean(
                                            priceForm.errors.start_date,
                                        )}
                                    />
                                    <InputError
                                        message={priceForm.errors.start_date}
                                    />
                                </div>
                                <div className="grid min-w-0 gap-2">
                                    <Label htmlFor="end_date">End date</Label>
                                    <DatePicker
                                        id="end_date"
                                        value={priceForm.data.end_date}
                                        onChange={(next) =>
                                            priceForm.setData('end_date', next)
                                        }
                                        min={
                                            priceForm.data.start_date ||
                                            undefined
                                        }
                                        placeholder="Optional end"
                                        aria-invalid={Boolean(
                                            priceForm.errors.end_date,
                                        )}
                                    />
                                    <InputError
                                        message={priceForm.errors.end_date}
                                    />
                                </div>
                            </div>
                        ) : null}
                    </form>
                </div>
            </Card>
        </div>
    );
}
