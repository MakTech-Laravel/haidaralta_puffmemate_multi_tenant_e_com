import { Head, useForm } from '@inertiajs/react';
import {
    ArrowDownToLine,
    ArrowUpFromLine,
    Boxes,
    Building2,
    Package,
    SlidersHorizontal,
    type LucideIcon,
} from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import { StockPairFields } from '@/components/stock/stock-pair-fields';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { StockPageContext } from '@/lib/stock-query';
import { cn } from '@/lib/utils';

const ADJUST_TYPES: Array<{
    value: string;
    label: string;
    hint: string;
    icon: LucideIcon;
}> = [
    {
        value: 'stock_in',
        label: 'New entry',
        hint: 'Added to on-hand stock',
        icon: ArrowDownToLine,
    },
    {
        value: 'stock_out',
        label: 'Reduce',
        hint: 'Removed from on-hand stock',
        icon: ArrowUpFromLine,
    },
    {
        value: 'adjustment',
        label: 'Adjustment',
        hint: 'Recorded as an adjustment quantity',
        icon: SlidersHorizontal,
    },
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
    const selectedType =
        ADJUST_TYPES.find((type) => type.value === form.data.type) ??
        ADJUST_TYPES[0];

    return (
        <>
            <Head title={title} />
            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={title}
                    description="Adjust stock for one branch and one product"
                />

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(17.5rem,20rem)]">
                    <section className="self-start rounded-2xl border border-border/80 bg-card p-5 shadow-sm sm:p-6">
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Branch and product
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Choose the branch and catalog product you want
                                to adjust.
                            </p>
                        </div>

                        <StockPairFields
                            context={context}
                            pageUrl={pageUrl}
                            createProductUrl={createProductUrl}
                            productOptionsUrl={productOptionsUrl}
                            branchOptionsUrl={branchOptionsUrl}
                        />

                        {ready ? (
                            <form
                                className="mt-6 flex flex-col gap-5 border-t border-border/70 pt-6"
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
                                <div className="space-y-1">
                                    <h2 className="text-base font-semibold tracking-tight">
                                        Adjust inventory
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        Stock in, stock out, or set an
                                        adjustment quantity for this pair.
                                    </p>
                                </div>

                                <div className="grid gap-2">
                                    <Label>Adjustment</Label>
                                    <div className="grid gap-2 sm:grid-cols-3">
                                        {ADJUST_TYPES.map((type) => {
                                            const Icon = type.icon;
                                            const selected =
                                                form.data.type === type.value;

                                            return (
                                                <Button
                                                    key={type.value}
                                                    type="button"
                                                    variant={
                                                        selected
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                    className={cn(
                                                        'h-auto flex-col gap-1.5 py-3 font-medium',
                                                        selected
                                                            ? ''
                                                            : 'text-foreground',
                                                    )}
                                                    onClick={() =>
                                                        form.setData(
                                                            'type',
                                                            type.value,
                                                        )
                                                    }
                                                >
                                                    <Icon className="size-4" />
                                                    {type.label}
                                                </Button>
                                            );
                                        })}
                                    </div>
                                    <InputError message={form.errors.type} />
                                </div>

                                <div className="grid max-w-sm gap-2">
                                    <Label htmlFor="quantity">Quantity</Label>
                                    <Input
                                        id="quantity"
                                        type="number"
                                        min={0}
                                        className="h-11 text-lg tabular-nums"
                                        value={form.data.quantity}
                                        onChange={(event) =>
                                            form.setData(
                                                'quantity',
                                                Number(event.target.value),
                                            )
                                        }
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        {selectedType.hint}
                                    </p>
                                    <InputError
                                        message={form.errors.quantity}
                                    />
                                </div>

                                <div>
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                    >
                                        Save stock
                                    </Button>
                                </div>
                            </form>
                        ) : (
                            <div className="mt-6 flex min-h-64 flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-border/80 px-6 py-12 text-center">
                                <Package className="text-muted-foreground size-8" />
                                <p className="text-sm font-medium">
                                    Nothing selected yet
                                </p>
                                <p className="text-muted-foreground max-w-sm text-sm">
                                    Choose a branch and product above to see
                                    current stock and make an adjustment.
                                </p>
                            </div>
                        )}
                    </section>

                    <aside className="grid gap-4 sm:grid-cols-3 xl:grid-cols-1">
                        <DemoStatCard
                            label="Current stock"
                            value={
                                ready
                                    ? String(context.inventory?.quantity ?? 0)
                                    : '—'
                            }
                            hint="On-hand quantity"
                            icon={Boxes}
                        />
                        <DemoStatCard
                            label="Branch"
                            value={context.branch?.name ?? 'Not selected'}
                            hint="Stock is held at this location"
                            icon={Building2}
                            valueClassName="truncate text-lg"
                        />
                        <DemoStatCard
                            label="Product"
                            value={context.product?.name ?? 'Not selected'}
                            hint={
                                context.product?.sku
                                    ? context.product.sku
                                    : 'Catalog listing'
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
