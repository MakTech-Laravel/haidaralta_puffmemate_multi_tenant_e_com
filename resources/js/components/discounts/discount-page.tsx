import { Head, Link, useForm } from '@inertiajs/react';
import {
    BadgePercent,
    Building2,
    Layers,
    Package,
    Replace,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import {
    InfiniteScrollMultiCombobox,
    type MultiComboboxOption,
} from '@/components/stock/infinite-scroll-multi-combobox';
import { Button } from '@/components/ui/button';
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
    isBranchLocked,
    type StockOption,
    type StockPageContext,
} from '@/lib/stock-query';

type DiscountTypeOption = { value: string; label: string };

type DiscountPreview = {
    pairs: number;
    with_offer: number;
    active: number;
    missing_price: number;
};

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

export function DiscountPage({
    context,
    createProductUrl,
    updateUrl,
    previewUrl,
    discountTypes,
    productOptionsUrl,
    branchOptionsUrl,
    lockBranch = false,
}: {
    context: StockPageContext;
    createProductUrl: string;
    updateUrl: string;
    previewUrl: string;
    discountTypes: DiscountTypeOption[];
    productOptionsUrl: string;
    branchOptionsUrl?: string;
    lockBranch?: boolean;
}) {
    const branchLocked = lockBranch || isBranchLocked(context.locked);
    const [branches, setBranches] = useState<MultiComboboxOption[]>(
        context.branch ? [toOption(context.branch)] : [],
    );
    const [products, setProducts] = useState<MultiComboboxOption[]>(
        context.product ? [toOption(context.product)] : [],
    );
    const [preview, setPreview] = useState<DiscountPreview>({
        pairs: 0,
        with_offer: 0,
        active: 0,
        missing_price: 0,
    });
    const form = useForm({
        branch_ids: context.branch ? [context.branch.id] : ([] as number[]),
        product_ids: context.product ? [context.product.id] : ([] as number[]),
        discount_type: context.price?.discount_type ?? '',
        discount_price: context.price?.discount_price ?? '',
        start_date: context.price?.start_date ?? '',
        end_date: context.price?.end_date ?? '',
    });
    const hasDiscount =
        form.data.discount_type === 'fixed' ||
        form.data.discount_type === 'percent';
    const pairCount =
        form.data.branch_ids.length * form.data.product_ids.length;
    const valueEmpty = String(form.data.discount_price ?? '').trim() === '';
    const saveDisabled =
        form.processing ||
        form.data.branch_ids.length === 0 ||
        form.data.product_ids.length === 0 ||
        form.data.discount_type === '' ||
        (hasDiscount && valueEmpty);

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

    useEffect(() => {
        if (
            form.data.branch_ids.length === 0 ||
            form.data.product_ids.length === 0
        ) {
            setPreview({
                pairs: 0,
                with_offer: 0,
                active: 0,
                missing_price: 0,
            });

            return;
        }

        const url = new URL(previewUrl, window.location.origin);
        form.data.branch_ids.forEach((id) => {
            url.searchParams.append('branch_ids[]', String(id));
        });
        form.data.product_ids.forEach((id) => {
            url.searchParams.append('product_ids[]', String(id));
        });

        const controller = new AbortController();

        fetch(url.toString(), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then((response) => response.json())
            .then((payload: DiscountPreview) => {
                setPreview({
                    pairs: payload.pairs ?? 0,
                    with_offer: payload.with_offer ?? 0,
                    active: payload.active ?? 0,
                    missing_price: payload.missing_price ?? 0,
                });
            })
            .catch(() => {
                if (!controller.signal.aborted) {
                    setPreview({
                        pairs:
                            form.data.branch_ids.length *
                            form.data.product_ids.length,
                        with_offer: 0,
                        active: 0,
                        missing_price: 0,
                    });
                }
            });

        return () => controller.abort();
    }, [form.data.branch_ids, form.data.product_ids, previewUrl]);

    return (
        <>
            <Head title="Discounts" />
            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Discounts"
                    description="Apply the same offer to many branch and product pairs. Save replaces any existing offer."
                />

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(17.5rem,20rem)]">
                    <section className="self-start rounded-2xl border border-border/80 bg-card p-5 shadow-sm sm:p-6">
                        <form
                            className="flex flex-col gap-5"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.transform((data) => ({
                                    ...data,
                                    discount_price:
                                        data.discount_type === 'none'
                                            ? null
                                            : data.discount_price,
                                    start_date:
                                        data.discount_type === 'none'
                                            ? null
                                            : data.start_date || null,
                                    end_date:
                                        data.discount_type === 'none'
                                            ? null
                                            : data.end_date || null,
                                }));
                                form.put(updateUrl, { preserveScroll: true });
                            }}
                        >
                            <div className="space-y-1">
                                <h2 className="text-base font-semibold tracking-tight">
                                    Branches and products
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    Select one or more of each. Every pair
                                    receives the same offer.
                                </p>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="discount-branch">
                                        Branches
                                    </Label>
                                    {branchLocked && context.branch ? (
                                        <LockedValue>
                                            {context.branch.name}
                                        </LockedValue>
                                    ) : branchOptionsUrl ? (
                                        <InfiniteScrollMultiCombobox
                                            id="discount-branch"
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
                                <div className="grid gap-2">
                                    <div className="flex items-center justify-between gap-2">
                                        <Label htmlFor="discount-product">
                                            Products
                                        </Label>
                                        <Link
                                            href={createProductUrl}
                                            className="text-primary text-xs font-medium hover:underline"
                                        >
                                            Add product
                                        </Link>
                                    </div>
                                    <InfiniteScrollMultiCombobox
                                        id="discount-product"
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
                                    <InputError
                                        message={form.errors.product_ids}
                                    />
                                </div>
                            </div>

                            <div className="flex flex-col gap-5 border-t border-border/70 pt-6">
                                <div className="space-y-1">
                                    <h2 className="text-base font-semibold tracking-tight">
                                        Offer
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        Type, value, and dates replace any
                                        current offer on the selected pairs.
                                    </p>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="discount_type">
                                            Discount type
                                        </Label>
                                        <Select
                                            value={
                                                form.data.discount_type ||
                                                undefined
                                            }
                                            onValueChange={(value) =>
                                                form.setData(
                                                    'discount_type',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                id="discount_type"
                                                className="w-full"
                                            >
                                                <SelectValue placeholder="Select type" />
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
                                            message={form.errors.discount_type}
                                        />
                                    </div>
                                    {hasDiscount ? (
                                        <div className="grid gap-2">
                                            <Label htmlFor="discount_price">
                                                Discount value{' '}
                                                <span className="text-muted-foreground font-normal">
                                                    {form.data.discount_type ===
                                                    'percent'
                                                        ? '(%)'
                                                        : '(AUD)'}
                                                </span>
                                            </Label>
                                            <Input
                                                id="discount_price"
                                                type="number"
                                                min={0}
                                                step="0.01"
                                                placeholder={
                                                    form.data.discount_type ===
                                                    'percent'
                                                        ? 'e.g. 10'
                                                        : 'e.g. 5.00'
                                                }
                                                value={
                                                    form.data.discount_price ??
                                                    ''
                                                }
                                                onChange={(event) =>
                                                    form.setData(
                                                        'discount_price',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    form.errors.discount_price
                                                }
                                            />
                                        </div>
                                    ) : null}
                                </div>

                                {hasDiscount ? (
                                    <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                                        <div className="grid min-w-0 gap-2">
                                            <Label>Start date</Label>
                                            <DatePicker
                                                value={
                                                    form.data.start_date ||
                                                    null
                                                }
                                                onChange={(value) =>
                                                    form.setData(
                                                        'start_date',
                                                        value,
                                                    )
                                                }
                                                clearable
                                            />
                                            <InputError
                                                message={
                                                    form.errors.start_date
                                                }
                                            />
                                        </div>
                                        <div className="grid min-w-0 gap-2">
                                            <Label>End date</Label>
                                            <DatePicker
                                                value={
                                                    form.data.end_date || null
                                                }
                                                onChange={(value) =>
                                                    form.setData(
                                                        'end_date',
                                                        value,
                                                    )
                                                }
                                                min={
                                                    form.data.start_date ||
                                                    undefined
                                                }
                                                clearable
                                            />
                                            <InputError
                                                message={form.errors.end_date}
                                            />
                                        </div>
                                    </div>
                                ) : null}

                                <div>
                                    <Button
                                        type="submit"
                                        disabled={saveDisabled}
                                    >
                                        Save discount
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </section>

                    <aside className="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                        <DemoStatCard
                            label="Pairs"
                            value={pairCount === 0 ? '—' : String(pairCount)}
                            hint={`${form.data.branch_ids.length} branches × ${form.data.product_ids.length} products`}
                            icon={Layers}
                        />
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
                                'Offer applies at these locations'
                            }
                            icon={Building2}
                            valueClassName="truncate text-lg"
                        />
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
                        <DemoStatCard
                            label="Existing offers"
                            value={
                                pairCount === 0
                                    ? '—'
                                    : String(preview.with_offer)
                            }
                            hint={
                                pairCount === 0
                                    ? 'Select pairs to see what will be replaced'
                                    : preview.with_offer === 0
                                      ? preview.missing_price > 0
                                          ? `${preview.missing_price} pair${preview.missing_price === 1 ? '' : 's'} still need a selling price`
                                          : 'No current offers — save will add this one'
                                      : `${preview.with_offer} selected pair${preview.with_offer === 1 ? '' : 's'} already have an offer (${preview.active} active now). Save will replace them.`
                            }
                            icon={
                                preview.with_offer > 0
                                    ? Replace
                                    : BadgePercent
                            }
                            valueClassName="text-lg"
                        />
                    </aside>
                </div>
            </div>
        </>
    );
}
