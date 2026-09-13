import { useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
        <div className="space-y-8">
            <section className="space-y-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div className="space-y-1">
                        <h2 className="text-lg font-medium">
                            Inventory & pricing
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Manage stock and price for {scopeLabel}.
                        </p>
                    </div>
                    <div className="grid min-w-[12rem] gap-2">
                        <Label htmlFor="inventory-scope">Scope</Label>
                        <Select value={scope} onValueChange={setScope}>
                            <SelectTrigger id="inventory-scope">
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
                </div>

                <div className="flex flex-wrap items-center gap-3 text-sm">
                    <span>
                        <span className="text-muted-foreground">On hand: </span>
                        {scopedInventory?.quantity ?? 0}
                    </span>
                    <span>
                        <span className="text-muted-foreground">
                            Available:{' '}
                        </span>
                        {scopedInventory?.available_quantity ?? 0}
                    </span>
                    <span>
                        <span className="text-muted-foreground">Minimum: </span>
                        {scopedInventory?.minimum_stock ?? 0}
                    </span>
                    {scopedInventory?.is_low_stock ? (
                        <Badge variant="destructive">Low stock</Badge>
                    ) : null}
                </div>
            </section>

            {activeVariants.length > 0 ? (
                <section className="space-y-3">
                    <h3 className="text-base font-medium">Variants overview</h3>
                    <Table>
                        <TableHeader>
                            <TableRow>
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
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                {rowInventory?.available_quantity ??
                                                    0}
                                                {rowInventory?.is_low_stock ? (
                                                    <Badge variant="destructive">
                                                        Low
                                                    </Badge>
                                                ) : null}
                                            </div>
                                        </TableCell>
                                        <TableCell>
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
                </section>
            ) : null}

            <section className="space-y-4">
                <h3 className="text-base font-medium">Adjust inventory</h3>
                <form
                    className="grid gap-4 sm:grid-cols-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        inventoryForm.transform((data) => ({
                            ...data,
                            variant_id:
                                scope === BASE_SCOPE ? null : Number(scope),
                        }));
                        inventoryForm.post(inventoryUrl, {
                            preserveScroll: true,
                        });
                    }}
                >
                    <div className="grid gap-2">
                        <Label>Type</Label>
                        <Select
                            value={inventoryForm.data.type}
                            onValueChange={(value) =>
                                inventoryForm.setData('type', value)
                            }
                        >
                            <SelectTrigger>
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
                            value={inventoryForm.data.quantity}
                            onChange={(event) =>
                                inventoryForm.setData(
                                    'quantity',
                                    Number(event.target.value),
                                )
                            }
                        />
                        <InputError message={inventoryForm.errors.quantity} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="minimum_stock">Minimum stock</Label>
                        <Input
                            id="minimum_stock"
                            type="number"
                            value={inventoryForm.data.minimum_stock}
                            onChange={(event) =>
                                inventoryForm.setData(
                                    'minimum_stock',
                                    Number(event.target.value),
                                )
                            }
                        />
                        <InputError
                            message={inventoryForm.errors.minimum_stock}
                        />
                    </div>
                    <div className="sm:col-span-3">
                        <Button
                            type="submit"
                            disabled={inventoryForm.processing}
                        >
                            Save inventory
                        </Button>
                    </div>
                </form>
            </section>

            <section className="space-y-4">
                <h3 className="text-base font-medium">Pricing</h3>
                <form
                    className="grid gap-4 sm:grid-cols-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        priceForm.transform((data) => ({
                            ...data,
                            variant_id:
                                scope === BASE_SCOPE ? null : Number(scope),
                            discount_price:
                                data.discount_price === ''
                                    ? null
                                    : data.discount_price,
                            start_date:
                                data.start_date === ''
                                    ? null
                                    : data.start_date,
                            end_date:
                                data.end_date === '' ? null : data.end_date,
                        }));
                        priceForm.put(priceUrl, { preserveScroll: true });
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="selling_price">Selling price</Label>
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
                        <InputError message={priceForm.errors.selling_price} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="discount_price">Discount price</Label>
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
                            message={priceForm.errors.discount_price}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="start_date">Start date</Label>
                        <Input
                            id="start_date"
                            type="date"
                            value={priceForm.data.start_date}
                            onChange={(event) =>
                                priceForm.setData(
                                    'start_date',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError message={priceForm.errors.start_date} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="end_date">End date</Label>
                        <Input
                            id="end_date"
                            type="date"
                            value={priceForm.data.end_date}
                            onChange={(event) =>
                                priceForm.setData(
                                    'end_date',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError message={priceForm.errors.end_date} />
                    </div>
                    <div className="flex items-center gap-2 sm:col-span-2">
                        <Checkbox
                            id="price_status"
                            checked={priceForm.data.status}
                            onCheckedChange={(checked) =>
                                priceForm.setData('status', checked === true)
                            }
                        />
                        <Label htmlFor="price_status">Price active</Label>
                    </div>
                    <div className="sm:col-span-2">
                        <Button type="submit" disabled={priceForm.processing}>
                            Save price
                        </Button>
                    </div>
                </form>
            </section>

            <section className="space-y-4">
                <h3 className="text-base font-medium">Recent transactions</h3>
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
                        {scopedTransactions.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="text-muted-foreground text-center"
                                >
                                    No transactions yet for this scope.
                                </TableCell>
                            </TableRow>
                        ) : (
                            scopedTransactions.map((txn) => (
                                <TableRow key={txn.id}>
                                    <TableCell>{txn.type}</TableCell>
                                    <TableCell>{txn.quantity}</TableCell>
                                    <TableCell>
                                        {txn.before_quantity}
                                    </TableCell>
                                    <TableCell>{txn.after_quantity}</TableCell>
                                    <TableCell>
                                        {txn.created_at ?? '—'}
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </section>
        </div>
    );
}
