import { useForm } from '@inertiajs/react';
import { Package, Tags } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
    reserved_quantity: number;
    available_quantity: number;
    minimum_stock: number;
    is_low_stock: boolean;
};

export type BranchPriceSnapshot = {
    selling_price: string | number;
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

type ProductInventoryPanelProps = {
    variants: BranchProductVariantRow[];
    inventory: BranchInventorySnapshot | null;
    price: BranchPriceSnapshot | null;
    variantInventories: Record<string, BranchInventorySnapshot>;
    variantPrices: Record<string, BranchPriceSnapshot>;
    transactions: BranchInventoryTransaction[];
    transactionTypes: { value: string; label: string }[];
    inventoryUrl: string;
    priceUrl: string;
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

function StatTile({
    label,
    value,
    tone = 'default',
}: {
    label: string;
    value: string | number;
    tone?: 'default' | 'warning';
}) {
    return (
        <div
            className={cn(
                'rounded-xl border border-border/70 bg-muted/20 px-4 py-3',
                tone === 'warning' && 'border-destructive/30 bg-destructive/5',
            )}
        >
            <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                {label}
            </p>
            <p className="mt-1 text-xl font-semibold tabular-nums">{value}</p>
        </div>
    );
}

export function ProductInventoryPanel({
    variants,
    inventory,
    price,
    variantInventories,
    variantPrices,
    transactions,
    transactionTypes,
    inventoryUrl,
    priceUrl,
}: ProductInventoryPanelProps) {
    const [scope, setScope] = useState(BASE_SCOPE);
    const activeVariants = useMemo(
        () => variants.filter((variant) => variant.status !== false),
        [variants],
    );

    const scopedInventory = resolveInventory(
        scope,
        inventory,
        variantInventories,
    );
    const scopedPrice = resolvePrice(scope, price, variantPrices);
    const variantId = scope === BASE_SCOPE ? null : Number(scope);

    const inventoryForm = useForm({
        type: 'stock_in',
        quantity: 1,
        minimum_stock: scopedInventory?.minimum_stock ?? 0,
        variant_id: variantId as number | null,
    });

    const priceForm = useForm({
        selling_price: scopedPrice?.selling_price ?? '',
        discount_price: scopedPrice?.discount_price ?? '',
        start_date: scopedPrice?.start_date ?? '',
        end_date: scopedPrice?.end_date ?? '',
        status: scopedPrice?.status ?? true,
        variant_id: variantId as number | null,
    });

    useEffect(() => {
        const nextInventory = resolveInventory(
            scope,
            inventory,
            variantInventories,
        );
        const nextPrice = resolvePrice(scope, price, variantPrices);
        const nextVariantId = scope === BASE_SCOPE ? null : Number(scope);

        inventoryForm.setData({
            type: 'stock_in',
            quantity: 1,
            minimum_stock: nextInventory?.minimum_stock ?? 0,
            variant_id: nextVariantId,
        });

        priceForm.setData({
            selling_price: nextPrice?.selling_price ?? '',
            discount_price: nextPrice?.discount_price ?? '',
            start_date: nextPrice?.start_date ?? '',
            end_date: nextPrice?.end_date ?? '',
            status: nextPrice?.status ?? true,
            variant_id: nextVariantId,
        });
        // Only re-sync when scope or server snapshots change.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [scope, inventory, price, variantInventories, variantPrices]);

    const scopedTransactions = useMemo(() => {
        if (scope === BASE_SCOPE) {
            return transactions.filter((txn) => txn.variant_id === null);
        }

        const id = Number(scope);

        return transactions.filter((txn) => txn.variant_id === id);
    }, [scope, transactions]);

    const scopeLabel =
        scope === BASE_SCOPE
            ? 'Base product'
            : (activeVariants.find((variant) => String(variant.id) === scope)
                  ?.name ?? 'Variant');

    return (
        <div className="space-y-6">
            <Card className="border-border/80 shadow-sm">
                <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-1">
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <Package className="size-4 text-muted-foreground" />
                            Inventory & pricing
                        </CardTitle>
                        <CardDescription>
                            Manage stock and price for {scopeLabel}.
                        </CardDescription>
                    </div>
                    <div className="grid w-full gap-2 sm:max-w-[14rem]">
                        <Label htmlFor="inventory-scope">Scope</Label>
                        <Select value={scope} onValueChange={setScope}>
                            <SelectTrigger id="inventory-scope" className="w-full">
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
                </CardHeader>
                <CardContent>
                    <div className="grid gap-3 sm:grid-cols-3">
                        <StatTile
                            label="On hand"
                            value={scopedInventory?.quantity ?? 0}
                        />
                        <StatTile
                            label="Available"
                            value={scopedInventory?.available_quantity ?? 0}
                            tone={
                                scopedInventory?.is_low_stock
                                    ? 'warning'
                                    : 'default'
                            }
                        />
                        <StatTile
                            label="Minimum"
                            value={scopedInventory?.minimum_stock ?? 0}
                        />
                    </div>
                    {scopedInventory?.is_low_stock ? (
                        <div className="mt-3">
                            <Badge variant="destructive">Low stock</Badge>
                        </div>
                    ) : null}
                </CardContent>
            </Card>

            {activeVariants.length > 0 ? (
                <Card className="border-border/80 shadow-sm">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Tags className="size-4 text-muted-foreground" />
                            Variants overview
                        </CardTitle>
                        <CardDescription>
                            Jump to a variant scope to adjust its stock or
                            price.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-hidden rounded-xl border border-border/70">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/30 hover:bg-muted/30">
                                        <TableHead>Variant</TableHead>
                                        <TableHead>Available</TableHead>
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
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {variant.name}
                                                    </div>
                                                    <div className="text-muted-foreground text-xs">
                                                        {variant.sku ?? '—'}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <span className="tabular-nums">
                                                            {rowInventory?.available_quantity ??
                                                                0}
                                                        </span>
                                                        {rowInventory?.is_low_stock ? (
                                                            <Badge variant="destructive">
                                                                Low
                                                            </Badge>
                                                        ) : null}
                                                    </div>
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
                    </CardContent>
                </Card>
            ) : null}

            <div className="grid items-start gap-6 xl:grid-cols-2">
                <Card className="border-border/80 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-base">
                            Adjust inventory
                        </CardTitle>
                        <CardDescription>
                            Stock in, stock out, or set minimum levels for this
                            scope.
                        </CardDescription>
                    </CardHeader>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            inventoryForm.transform((data) => ({
                                ...data,
                                variant_id:
                                    scope === BASE_SCOPE
                                        ? null
                                        : Number(scope),
                            }));
                            inventoryForm.post(inventoryUrl, {
                                preserveScroll: true,
                            });
                        }}
                    >
                        <CardContent className="grid gap-4">
                            <div className="grid gap-2">
                                <Label>Type</Label>
                                <Select
                                    value={inventoryForm.data.type}
                                    onValueChange={(value) =>
                                        inventoryForm.setData('type', value)
                                    }
                                >
                                    <SelectTrigger className="w-full">
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
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="quantity">Quantity</Label>
                                    <Input
                                        id="quantity"
                                        type="number"
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
                                <div className="grid gap-2">
                                    <Label htmlFor="minimum_stock">
                                        Minimum stock
                                    </Label>
                                    <Input
                                        id="minimum_stock"
                                        type="number"
                                        value={
                                            inventoryForm.data.minimum_stock
                                        }
                                        onChange={(event) =>
                                            inventoryForm.setData(
                                                'minimum_stock',
                                                Number(event.target.value),
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            inventoryForm.errors.minimum_stock
                                        }
                                    />
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="border-t border-border/60 py-4">
                            <Button
                                type="submit"
                                disabled={inventoryForm.processing}
                            >
                                Save inventory
                            </Button>
                        </CardFooter>
                    </form>
                </Card>

                <Card className="border-border/80 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-base">Pricing</CardTitle>
                        <CardDescription>
                            Selling price, optional discount window, and active
                            status.
                        </CardDescription>
                    </CardHeader>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            priceForm.transform((data) => ({
                                ...data,
                                variant_id:
                                    scope === BASE_SCOPE
                                        ? null
                                        : Number(scope),
                                discount_price:
                                    data.discount_price === ''
                                        ? null
                                        : data.discount_price,
                                start_date:
                                    data.start_date === ''
                                        ? null
                                        : data.start_date,
                                end_date:
                                    data.end_date === ''
                                        ? null
                                        : data.end_date,
                            }));
                            priceForm.put(priceUrl, { preserveScroll: true });
                        }}
                    >
                        <CardContent className="grid gap-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="selling_price">
                                        Selling price
                                    </Label>
                                    <Input
                                        id="selling_price"
                                        type="number"
                                        step="0.01"
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
                                    <Label htmlFor="discount_price">
                                        Discount price
                                    </Label>
                                    <Input
                                        id="discount_price"
                                        type="number"
                                        step="0.01"
                                        value={priceForm.data.discount_price}
                                        onChange={(event) =>
                                            priceForm.setData(
                                                'discount_price',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            priceForm.errors.discount_price
                                        }
                                    />
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
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
                                <div className="grid gap-2">
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
                            <div className="flex items-center gap-2 rounded-xl border border-border/70 bg-muted/20 px-4 py-3">
                                <Checkbox
                                    id="price_status"
                                    checked={priceForm.data.status}
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
                        <CardFooter className="border-t border-border/60 py-4">
                            <Button
                                type="submit"
                                disabled={priceForm.processing}
                            >
                                Save price
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>

            <Card className="border-border/80 shadow-sm">
                <CardHeader>
                    <CardTitle className="text-base">
                        Recent transactions
                    </CardTitle>
                    <CardDescription>
                        Stock movements for the selected scope.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="overflow-hidden rounded-xl border border-border/70">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/30 hover:bg-muted/30">
                                    <TableHead>Type</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>Before</TableHead>
                                    <TableHead>After</TableHead>
                                    <TableHead>When</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {scopedTransactions.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="text-muted-foreground py-10 text-center"
                                        >
                                            No transactions yet for this scope.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    scopedTransactions.map((txn) => (
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
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
