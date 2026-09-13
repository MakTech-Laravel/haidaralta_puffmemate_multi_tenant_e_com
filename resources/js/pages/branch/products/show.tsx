import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
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
    description: string | null;
    status: boolean;
    image_url: string | null;
    category_ids: number[];
    categories: { id: number; name: string; slug: string }[];
    category_name: string;
    variants: { id: number; name: string; sku: string | null; status: boolean }[];
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
        status: boolean;
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

export default function BranchProductShow({
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
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Products',
                href: ProductController.index.url(branch.slug),
            },
            {
                title: product.name,
                href: ProductController.show.url({
                    branch: branch.slug,
                    product: product.slug,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={product.name} />

            <div className="mx-auto flex max-w-4xl flex-col gap-8 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={product.name}
                        description={
                            product.category_name ||
                            'Branch inventory and pricing'
                        }
                    />
                    <Button variant="outline" asChild>
                        <Link href={ProductController.index.url(branch.slug)}>
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
                                    ProductController.toggle.url({
                                        branch: branch.slug,
                                        product: product.slug,
                                    }),
                                    { status: next },
                                    { preserveScroll: true },
                                );
                            }}
                        />
                        <span className="text-sm">
                            {enabled ? 'Enabled' : 'Disabled'} for this branch
                        </span>
                    </div>
                    {product.inventory?.is_low_stock ? (
                        <Badge variant="destructive">Low stock</Badge>
                    ) : null}
                </div>

                <div className="grid gap-2 text-sm">
                    <div>
                        <span className="text-muted-foreground">SKU: </span>
                        {product.sku ?? '—'}
                    </div>
                    <div>
                        <span className="text-muted-foreground">On hand: </span>
                        {product.inventory?.quantity ?? 0}
                    </div>
                    <div>
                        <span className="text-muted-foreground">Available: </span>
                        {product.inventory?.available_quantity ?? 0}
                    </div>
                </div>

                <section className="space-y-4">
                    <h2 className="text-lg font-medium">Adjust inventory</h2>
                    <form
                        className="grid gap-4 sm:grid-cols-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            inventoryForm.post(
                                ProductController.adjustInventory.url({
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
                                ProductController.upsertPrice.url({
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
