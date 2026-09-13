import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/react';
import { useState } from 'react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import Heading from '@/components/heading';
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

type Branch = { id: number; name: string; slug: string };

type ProductShow = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    status: boolean;
    image_url: string | null;
    category: { id: number; name: string } | null;
    variants: { id: number; name: string; sku: string | null }[];
    enabled: boolean;
    inventory: {
        quantity: number;
        reserved_quantity: number;
        available_quantity: number;
        minimum_stock: number;
        is_low_stock: boolean;
    } | null;
    price: {
        selling_price: string | number;
        discount_price: string | number | null;
        start_date: string | null;
        end_date: string | null;
    } | null;
    transactions: {
        id: number;
        type: string;
        quantity: number;
        before_quantity: number;
        after_quantity: number;
        created_at: string | null;
    }[];
};

export default function PlatformBranchProductShow({
    branch,
    product,
    transactionTypes,
}: {
    branch: Branch;
    product: ProductShow;
    transactionTypes: { value: string; label: string }[];
}) {
    const [enabled, setEnabled] = useState(product.enabled);
    const inventoryForm = useForm({
        type: 'stock_in',
        quantity: 1,
        minimum_stock: product.inventory?.minimum_stock ?? 0,
    });
    const priceForm = useForm({
        selling_price: product.price?.selling_price ?? '',
        discount_price: product.price?.discount_price ?? '',
        start_date: product.price?.start_date ?? '',
        end_date: product.price?.end_date ?? '',
        status: true,
    });

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Branches', href: '/platform/branches' },
            {
                title: branch.name,
                href: `/platform/branches/${branch.slug}`,
            },
            {
                title: 'Products',
                href: BranchInventoryController.index.url(branch.slug),
            },
            {
                title: product.name,
                href: BranchInventoryController.show.url({
                    branch: branch.slug,
                    product: product.slug,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={`${product.name} · ${branch.name}`} />

            <div className="mx-auto flex max-w-4xl flex-col gap-8 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={product.name}
                        description={`Inventory for ${branch.name}`}
                    />
                    <Button variant="outline" asChild>
                        <Link
                            href={BranchInventoryController.index.url(
                                branch.slug,
                            )}
                        >
                            Back
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-4">
                    <div className="flex items-center gap-2">
                        <Checkbox
                            checked={enabled}
                            onCheckedChange={(checked) => {
                                const next = checked === true;
                                setEnabled(next);
                                router.put(
                                    BranchInventoryController.toggle.url({
                                        branch: branch.slug,
                                        product: product.slug,
                                    }),
                                    { status: next },
                                    { preserveScroll: true },
                                );
                            }}
                        />
                        <span className="text-sm">
                            {enabled ? 'Enabled' : 'Disabled'} at this branch
                        </span>
                    </div>
                    {product.inventory?.is_low_stock ? (
                        <Badge variant="destructive">Low stock</Badge>
                    ) : null}
                </div>

                <section className="space-y-4">
                    <h2 className="text-lg font-medium">Adjust inventory</h2>
                    <form
                        className="grid gap-4 sm:grid-cols-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            inventoryForm.post(
                                BranchInventoryController.adjustInventory.url({
                                    branch: branch.slug,
                                    product: product.slug,
                                }),
                                { preserveScroll: true },
                            );
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
                            <InputError
                                message={inventoryForm.errors.quantity}
                            />
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
                    <h2 className="text-lg font-medium">Pricing</h2>
                    <form
                        className="grid gap-4 sm:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            priceForm.put(
                                BranchInventoryController.upsertPrice.url({
                                    branch: branch.slug,
                                    product: product.slug,
                                }),
                                { preserveScroll: true },
                            );
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
                            <InputError
                                message={priceForm.errors.selling_price}
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
                        </div>
                        <div className="sm:col-span-2">
                            <Button
                                type="submit"
                                disabled={priceForm.processing}
                            >
                                Save price
                            </Button>
                        </div>
                    </form>
                </section>

                <section className="space-y-4">
                    <h2 className="text-lg font-medium">Recent transactions</h2>
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
                            {product.transactions.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="text-muted-foreground text-center"
                                    >
                                        No transactions yet.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                product.transactions.map((txn) => (
                                    <TableRow key={txn.id}>
                                        <TableCell>{txn.type}</TableCell>
                                        <TableCell>{txn.quantity}</TableCell>
                                        <TableCell>
                                            {txn.before_quantity}
                                        </TableCell>
                                        <TableCell>
                                            {txn.after_quantity}
                                        </TableCell>
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
        </>
    );
}
