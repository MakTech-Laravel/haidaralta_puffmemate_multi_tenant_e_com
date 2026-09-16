import { Head, useForm } from '@inertiajs/react';
import { Boxes, Package } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import { StockPairFields } from '@/components/stock/stock-pair-fields';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { StockPageContext } from '@/lib/stock-query';

const ADJUST_TYPES = [
    { value: 'stock_in', label: 'New entry' },
    { value: 'stock_out', label: 'Reduce' },
    { value: 'adjustment', label: 'Adjustment' },
];

export function ManageStockPage({
    context,
    createProductUrl,
    pageUrl,
    adjustUrl,
    productOptionsUrl,
    branchOptionsUrl,
    title = 'Manage stock',
}: {
    context: StockPageContext;
    createProductUrl: string;
    pageUrl: string;
    adjustUrl: string;
    productOptionsUrl: string;
    branchOptionsUrl?: string;
    title?: string;
}) {
    const ready = context.branch !== null && context.product !== null;
    const form = useForm({
        branch_id: context.branch?.id ?? 0,
        product_id: context.product?.id ?? 0,
        type: 'stock_in',
        quantity: 0,
    });

    return (
        <>
            <Head title={title} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={title}
                    description="Adjust stock for one branch and one product"
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Branch and product</CardTitle>
                        <CardDescription>
                            Choose the branch and catalog product you want to
                            adjust.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <StockPairFields
                            context={context}
                            pageUrl={pageUrl}
                            createProductUrl={createProductUrl}
                            productOptionsUrl={productOptionsUrl}
                            branchOptionsUrl={branchOptionsUrl}
                        />
                    </CardContent>
                </Card>

                {ready ? (
                    <>
                        <DemoStatCard
                            label="Current stock"
                            value={String(context.inventory?.quantity ?? 0)}
                            hint={
                                context.product?.sku
                                    ? `${context.product.name} · ${context.product.sku}`
                                    : (context.product?.name ??
                                      'On-hand quantity')
                            }
                            icon={Boxes}
                        />

                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.transform((data) => ({
                                    ...data,
                                    branch_id: context.branch?.id ?? 0,
                                    product_id: context.product?.id ?? 0,
                                }));
                                form.post(adjustUrl, {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            <Card>
                                <CardHeader>
                                    <CardTitle>Adjust inventory</CardTitle>
                                    <CardDescription>
                                        Stock in, stock out, or set an
                                        adjustment quantity for this pair.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="type">
                                            Adjustment
                                        </Label>
                                        <Select
                                            value={form.data.type}
                                            onValueChange={(value) =>
                                                form.setData('type', value)
                                            }
                                        >
                                            <SelectTrigger
                                                id="type"
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {ADJUST_TYPES.map((type) => (
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
                                            message={form.errors.type}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="quantity">
                                            Quantity
                                        </Label>
                                        <Input
                                            id="quantity"
                                            type="number"
                                            min={0}
                                            value={form.data.quantity}
                                            onChange={(event) =>
                                                form.setData(
                                                    'quantity',
                                                    Number(event.target.value),
                                                )
                                            }
                                        />
                                        <InputError
                                            message={form.errors.quantity}
                                        />
                                    </div>
                                </CardContent>
                                <CardFooter className="border-t border-border/60 pt-6">
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                    >
                                        Save stock
                                    </Button>
                                </CardFooter>
                            </Card>
                        </form>
                    </>
                ) : (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center gap-2 py-10 text-center">
                            <Package className="text-muted-foreground size-8" />
                            <p className="text-sm font-medium">
                                Nothing selected yet
                            </p>
                            <p className="text-muted-foreground max-w-sm text-sm">
                                Choose a branch and product above to see current
                                stock and make an adjustment.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
