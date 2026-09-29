import { Link, useForm } from '@inertiajs/react';
import {
    ChevronsUpDown,
    CircleDollarSign,
    Minus,
    Package2,
    Percent,
    Plus,
    Tag,
    Trash2,
} from 'lucide-react';
import { useMemo, useState, type FormEvent } from 'react';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

export type PackProductOption = {
    id: number;
    name: string;
    sku: string | null;
    image_url: string | null;
    selling_price: string | null;
    effective_price: string | null;
    quantity: number;
};

export type PackGalleryImage = {
    id: number;
    path: string;
    url: string;
    sort_order: number;
    mime_type?: string;
};

export type PackFormDefaults = {
    name?: string;
    description?: string | null;
    image?: string | null;
    image_url?: string | null;
    gallery?: PackGalleryImage[];
    offer_type?: OfferType;
    offer_value?: string;
    quantity?: number;
    status?: boolean;
    is_highlighted?: boolean;
    starts_at?: string | null;
    ends_at?: string | null;
    items?: { quantity: number; product: PackProductOption }[];
};

type EmptyProductsMessage = {
    title: string;
    description: string;
};

type PackLine = {
    product: PackProductOption;
    qty: number;
};

type OfferType = 'fixed' | 'percent' | 'pack_price';

type FormAction = {
    action: string;
    method?: string;
};

const sectionCardClass =
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm sm:p-6';

const OFFER_TYPES: {
    value: OfferType;
    label: string;
    icon: typeof Tag;
}[] = [
    { value: 'fixed', label: 'Amount off', icon: Tag },
    { value: 'percent', label: 'Percent', icon: Percent },
    { value: 'pack_price', label: 'Pack price', icon: CircleDollarSign },
];

function initials(name: string): string {
    return name.slice(0, 2).toUpperCase();
}

function parseMoney(value: string | number | null | undefined): number {
    if (value == null || value === '') {
        return 0;
    }

    const amount = Number(value);

    return Number.isFinite(amount) ? amount : 0;
}

function formatAud(value: number): string {
    return `$${value.toFixed(2)}`;
}

function unitPrice(product: PackProductOption): number {
    const effective = parseMoney(product.effective_price);
    if (effective > 0) {
        return effective;
    }

    return parseMoney(product.selling_price);
}

function ProductUnitPrice({ product }: { product: PackProductOption }) {
    const selling = parseMoney(product.selling_price);
    const effective = unitPrice(product);

    if (selling <= 0 && effective <= 0) {
        return <span className="text-muted-foreground">—</span>;
    }

    if (selling > effective && effective > 0) {
        return (
            <span className="tabular-nums">
                <s className="text-muted-foreground">{formatAud(selling)}</s>{' '}
                {formatAud(effective)}
            </span>
        );
    }

    return (
        <span className="tabular-nums">
            {formatAud(effective || selling)}
        </span>
    );
}

export function BranchPackForm({
    action,
    availableProducts,
    cancelHref,
    defaults,
    isEdit = false,
    extraData,
    canHighlight = false,
    emptyProductsMessage = {
        title: 'No stock products on this branch',
        description: 'Add products first, then come back to build a pack.',
    },
}: {
    action: FormAction;
    availableProducts: PackProductOption[];
    cancelHref: string;
    defaults?: PackFormDefaults;
    isEdit?: boolean;
    extraData?: { branch_id: number | null };
    canHighlight?: boolean;
    emptyProductsMessage?: EmptyProductsMessage;
}) {
    const [existingCover, setExistingCover] = useState<ExistingFile[]>(
        defaults?.image && defaults.image_url
            ? [
                  {
                      id: 'cover',
                      path: defaults.image,
                      url: defaults.image_url,
                      mime_type: 'image/jpeg',
                  },
              ]
            : [],
    );
    const [existingGallery, setExistingGallery] = useState<ExistingFile[]>(
        (defaults?.gallery ?? []).map((image) => ({
            id: image.id,
            path: image.path,
            url: image.url,
            mime_type: image.mime_type ?? 'image/jpeg',
        })),
    );
    const [lines, setLines] = useState<PackLine[]>(
        (defaults?.items ?? []).map((item) => ({
            product: item.product,
            qty: item.quantity,
        })),
    );
    const [pickerOpen, setPickerOpen] = useState(false);

    const {
        data,
        setData,
        transform,
        post,
        processing,
        progress,
        errors,
        cancel,
    } = useForm({
        name: defaults?.name ?? '',
        description: defaults?.description ?? '',
        image: null as File | null,
        gallery: [] as File[],
        remove_image: false,
        remove_gallery_ids: [] as number[],
        status: defaults?.status ?? true,
        is_highlighted: defaults?.is_highlighted ?? false,
        starts_at: defaults?.starts_at ?? '',
        ends_at: defaults?.ends_at ?? '',
        offer_type: defaults?.offer_type ?? ('percent' as OfferType),
        offer_value: defaults?.offer_value ?? '20',
        quantity: defaults?.quantity ?? 1,
    });

    const selectedIds = useMemo(
        () => new Set(lines.map((line) => line.product.id)),
        [lines],
    );

    const pickerOptions = useMemo(
        () =>
            availableProducts.filter(
                (product) => !selectedIds.has(product.id),
            ),
        [availableProducts, selectedIds],
    );

    const packsToSell = Number.isFinite(Number(data.quantity))
        ? Math.max(1, Math.floor(Number(data.quantity)))
        : 1;

    // Per-product stock validation (client-side, create mode only).
    // Key: product id, Value: error message.
    const stockIssues = useMemo(() => {
        const issues: Record<number, string> = {};

        if (isEdit) {
            return issues;
        }

        for (const line of lines) {
            const needed = line.qty * packsToSell;
            const available = line.product.quantity;

            if (needed > available) {
                issues[line.product.id] =
                    available <= 0
                        ? 'Out of stock. This product has no stock available.'
                        : `Not enough stock. Needs ${needed} (${line.qty} × ${packsToSell} packs) but only ${available} available.`;
            }
        }

        return issues;
    }, [lines, packsToSell, isEdit]);

    const hasStockIssues = Object.keys(stockIssues).length > 0;

    const itemErrors = errors as Record<string, string | undefined>;

    // Server-side per-item error (e.g. items.0.quantity / items.0.product_id).
    function serverLineError(index: number): string | undefined {
        return (
            itemErrors[`items.${index}.quantity`] ??
            itemErrors[`items.${index}.product_id`] ??
            itemErrors[`items.${index}`]
        );
    }

    const totals = useMemo(() => {
        const subtotal = lines.reduce(
            (sum, line) => sum + unitPrice(line.product) * line.qty,
            0,
        );
        const rawOffer = parseMoney(data.offer_value);

        if (data.offer_type === 'fixed') {
            const savings = Math.min(Math.max(rawOffer, 0), subtotal);

            return {
                subtotal,
                savings,
                packPrice: Math.max(0, subtotal - savings),
            };
        }

        if (data.offer_type === 'percent') {
            const percent = Math.min(Math.max(rawOffer, 0), 100);
            const savings = (subtotal * percent) / 100;

            return {
                subtotal,
                savings,
                packPrice: Math.max(0, subtotal - savings),
            };
        }

        const packPrice = Math.max(rawOffer, 0);

        return {
            subtotal,
            savings: Math.max(0, subtotal - packPrice),
            packPrice,
        };
    }, [lines, data.offer_type, data.offer_value]);

    function addProduct(product: PackProductOption): void {
        if (isEdit) {
            return;
        }

        setLines((current) => {
            if (current.some((line) => line.product.id === product.id)) {
                return current;
            }

            return [...current, { product, qty: 1 }];
        });
        setPickerOpen(false);
    }

    function setQty(productId: number, qty: number): void {
        if (isEdit) {
            return;
        }

        const next = Number.isFinite(qty) ? Math.max(1, Math.floor(qty)) : 1;

        setLines((current) =>
            current.map((line) =>
                line.product.id === productId ? { ...line, qty: next } : line,
            ),
        );
    }

    function removeLine(productId: number): void {
        if (isEdit) {
            return;
        }

        setLines((current) =>
            current.filter((line) => line.product.id !== productId),
        );
    }

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();

        // Stop here if any product doesn't have enough stock;
        // the messages are already shown under each product.
        if (hasStockIssues) {
            return;
        }

        transform((form) => {
            const {
                image,
                gallery,
                remove_image,
                remove_gallery_ids,
                description,
                starts_at,
                ends_at,
                is_highlighted,
                ...rest
            } = form;

            return {
                ...rest,
                ...(extraData ?? {}),
                ...(canHighlight ? { is_highlighted } : {}),
                description: description === '' ? null : description,
                starts_at: starts_at === '' ? null : starts_at,
                ends_at: ends_at === '' ? null : ends_at,
                ...(image ? { image } : {}),
                ...(gallery.length > 0 ? { gallery } : {}),
                ...(remove_image ? { remove_image: true } : {}),
                ...(remove_gallery_ids.length > 0
                    ? { remove_gallery_ids }
                    : {}),
                ...(!isEdit
                    ? {
                          items: lines.map((line) => ({
                              product_id: line.product.id,
                              quantity: line.qty,
                          })),
                      }
                    : {}),
            };
        });

        post(action.action, {
            forceFormData: true,
            preserveScroll: true,
        });
    }

    const offerInputLabel =
        data.offer_type === 'percent'
            ? 'Discount value (%)'
            : data.offer_type === 'fixed'
              ? 'Discount value (AUD)'
              : 'Pack price (AUD)';

    const offerPlaceholder =
        data.offer_type === 'percent'
            ? 'e.g. 10'
            : data.offer_type === 'fixed'
              ? 'e.g. 5.00'
              : 'e.g. 80.00';

    const offerHelper =
        data.offer_type === 'percent'
            ? 'Percent off the combined product total (0–100).'
            : data.offer_type === 'fixed'
              ? 'Amount off the combined product total, ≤ subtotal.'
              : 'Set the pack selling price. Savings is subtotal minus this price.';

    return (
        <form className="space-y-6" onSubmit={handleSubmit}>
            <div className="grid items-start gap-6 lg:grid-cols-2">
                <section className={sectionCardClass}>
                    <div className="space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            Pack details
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Name, cover image, and when this pack is on.
                        </p>
                    </div>

                    <div className="mt-5 space-y-5">
                        <div className="flex items-center justify-between gap-4 rounded-xl border p-4">
                            <div>
                                <Label htmlFor="pack-status">Active</Label>
                                <p className="text-muted-foreground text-sm">
                                    Inactive packs stay hidden from selling.
                                </p>
                            </div>
                            <Switch
                                id="pack-status"
                                checked={data.status}
                                onCheckedChange={(status) =>
                                    setData((current) => ({
                                        ...current,
                                        status,
                                    }))
                                }
                            />
                        </div>
                        <InputError message={errors.status} />

                        {canHighlight ? (
                            <div className="flex items-center justify-between gap-4 rounded-xl border p-4">
                                <div>
                                    <Label htmlFor="pack-highlighted">
                                        Highlight on storefront
                                    </Label>
                                    <p className="text-muted-foreground text-sm">
                                        Shows as the featured pack on Packs &amp;
                                        Saves. Only one pack can be highlighted.
                                    </p>
                                </div>
                                <Switch
                                    id="pack-highlighted"
                                    checked={data.is_highlighted}
                                    onCheckedChange={(is_highlighted) =>
                                        setData((current) => ({
                                            ...current,
                                            is_highlighted,
                                        }))
                                    }
                                />
                            </div>
                        ) : null}

                        <div className="space-y-2">
                            <Label htmlFor="pack-name">
                                Name{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="pack-name"
                                value={data.name}
                                onChange={(event) =>
                                    setData((current) => ({
                                        ...current,
                                        name: event.target.value,
                                    }))
                                }
                                placeholder="e.g. Starter trio"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="pack-description">
                                Description
                            </Label>
                            <Textarea
                                id="pack-description"
                                value={data.description}
                                onChange={(event) =>
                                    setData((current) => ({
                                        ...current,
                                        description: event.target.value,
                                    }))
                                }
                                placeholder="What is in this pack and why it is a save."
                                rows={4}
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="pack-quantity">
                                Packs to sell{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="pack-quantity"
                                type="number"
                                min={1}
                                step={1}
                                value={data.quantity}
                                onChange={(event) =>
                                    setData((current) => ({
                                        ...current,
                                        quantity: Number(event.target.value),
                                    }))
                                }
                            />
                            <p className="text-muted-foreground text-xs">
                                This count is reserved from each product’s
                                on-hand stock.
                            </p>
                            <InputError message={errors.quantity} />
                        </div>

                        <div className="space-y-2">
                            <Label>Cover image</Label>
                            <FileUpload
                                value={data.image}
                                onChange={(file) => {
                                    const next = Array.isArray(file)
                                        ? (file[0] ?? null)
                                        : file;
                                    setExistingCover([]);
                                    setData((current) => ({
                                        ...current,
                                        image: next,
                                        remove_image: next
                                            ? false
                                            : current.remove_image,
                                    }));
                                }}
                                existingFiles={
                                    data.image || data.remove_image
                                        ? []
                                        : existingCover
                                }
                                onRemoveExisting={() => {
                                    setExistingCover([]);
                                    setData((current) => ({
                                        ...current,
                                        image: null,
                                        remove_image: true,
                                    }));
                                }}
                                accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,.png,.jpg,.jpeg,.webp,.svg"
                                maxSize={2}
                                lightbox
                                isUploading={processing}
                                uploadProgress={progress?.percentage ?? null}
                                onCancel={processing ? cancel : undefined}
                                placeholder="Drop or click"
                                hint="PNG, JPG, SVG, or WebP · Max 2MB"
                                compactCards
                            />
                            <InputError message={errors.image} />
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between gap-3">
                                <Label>Gallery</Label>
                                <span className="text-muted-foreground text-xs">
                                    {existingGallery.length +
                                        data.gallery.length}
                                    /12
                                </span>
                            </div>
                            <FileUpload
                                multiple
                                dropzoneVisibility="empty"
                                compactCards
                                value={data.gallery}
                                onChange={(files) => {
                                    const next = Array.isArray(files)
                                        ? files
                                        : files
                                          ? [files]
                                          : [];
                                    setData((current) => ({
                                        ...current,
                                        gallery: next,
                                    }));
                                }}
                                existingFiles={existingGallery}
                                onRemoveExisting={(id) => {
                                    const numericId = Number(id);
                                    setExistingGallery((current) =>
                                        current.filter(
                                            (file) => file.id !== id,
                                        ),
                                    );
                                    setData((current) => {
                                        const next =
                                            current.remove_gallery_ids.includes(
                                                numericId,
                                            )
                                                ? current.remove_gallery_ids
                                                : [
                                                      ...current.remove_gallery_ids,
                                                      numericId,
                                                  ];

                                        return {
                                            ...current,
                                            remove_gallery_ids: next,
                                        };
                                    });
                                }}
                                accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,.png,.jpg,.jpeg,.webp,.svg"
                                maxSize={2}
                                maxFiles={12}
                                lightbox
                                isUploading={processing}
                                uploadProgress={progress?.percentage ?? null}
                                onCancel={processing ? cancel : undefined}
                                placeholder="Add gallery images"
                                hint="Up to 12 · PNG, JPG, SVG, or WebP"
                                classNames={{
                                    dropzone: 'py-5',
                                    grid: 'grid-cols-3 gap-2 sm:grid-cols-3 lg:grid-cols-3',
                                    card: 'aspect-square overflow-hidden',
                                    cardMedia: 'h-full bg-muted/20',
                                    cardImage: 'object-contain p-1',
                                }}
                            />
                            <InputError
                                message={
                                    errors.gallery ?? errors.remove_gallery_ids
                                }
                            />
                        </div>

                        <div className="grid min-w-0 items-start gap-4 sm:grid-cols-2">
                            <div className="grid min-w-0 gap-2">
                                <Label htmlFor="pack-starts-at">
                                    Start date
                                </Label>
                                <DatePicker
                                    id="pack-starts-at"
                                    value={data.starts_at}
                                    onChange={(starts_at) =>
                                        setData((current) => ({
                                            ...current,
                                            starts_at,
                                        }))
                                    }
                                    max={data.ends_at || undefined}
                                    placeholder="Optional start"
                                />
                                <InputError message={errors.starts_at} />
                            </div>
                            <div className="grid min-w-0 gap-2">
                                <Label htmlFor="pack-ends-at">End date</Label>
                                <DatePicker
                                    id="pack-ends-at"
                                    value={data.ends_at}
                                    onChange={(ends_at) =>
                                        setData((current) => ({
                                            ...current,
                                            ends_at,
                                        }))
                                    }
                                    min={data.starts_at || undefined}
                                    placeholder="Optional end"
                                />
                                <InputError message={errors.ends_at} />
                            </div>
                        </div>
                    </div>
                </section>

                <section className={sectionCardClass}>
                    <div className="space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            Products in this pack
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            {isEdit
                                ? 'Product mix is frozen after create. Change packs to sell to reserve or release stock.'
                                : 'Choose this branch’s stock products. Quantity is per pack.'}
                        </p>
                    </div>

                    <div className="mt-5 space-y-4">
                        {availableProducts.length === 0 && !isEdit ? (
                            <Alert>
                                <Package2 />
                                <AlertTitle>
                                    {emptyProductsMessage.title}
                                </AlertTitle>
                                <AlertDescription>
                                    {emptyProductsMessage.description}
                                </AlertDescription>
                            </Alert>
                        ) : null}

                        {!isEdit && availableProducts.length > 0 ? (
                            <div className="space-y-2">
                                <Label>Add product</Label>
                                <Popover
                                    open={pickerOpen}
                                    onOpenChange={setPickerOpen}
                                >
                                    <PopoverTrigger asChild>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            role="combobox"
                                            aria-expanded={pickerOpen}
                                            className="w-full justify-between font-normal"
                                        >
                                            Search this branch’s products…
                                            <ChevronsUpDown className="size-4 opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        className="w-(--radix-popover-trigger-width) p-0"
                                        align="start"
                                    >
                                        <Command>
                                            <CommandInput placeholder="Search name or SKU…" />
                                            <CommandList>
                                                <CommandEmpty>
                                                    {pickerOptions.length === 0
                                                        ? 'All products added.'
                                                        : 'No products found.'}
                                                </CommandEmpty>
                                                <CommandGroup>
                                                    {pickerOptions.map(
                                                        (product) => (
                                                            <CommandItem
                                                                key={
                                                                    product.id
                                                                }
                                                                value={`${product.name} ${product.sku ?? ''}`}
                                                                onSelect={() =>
                                                                    addProduct(
                                                                        product,
                                                                    )
                                                                }
                                                            >
                                                                <span className="min-w-0 flex-1 truncate">
                                                                    {
                                                                        product.name
                                                                    }
                                                                </span>
                                                                <span className="text-muted-foreground text-xs tabular-nums">
                                                                    <ProductUnitPrice
                                                                        product={
                                                                            product
                                                                        }
                                                                    />
                                                                </span>
                                                            </CommandItem>
                                                        ),
                                                    )}
                                                </CommandGroup>
                                            </CommandList>
                                        </Command>
                                    </PopoverContent>
                                </Popover>
                            </div>
                        ) : null}

                        {lines.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Add at least 2 products to make a pack.
                            </p>
                        ) : (
                            <ul className="divide-border divide-y rounded-xl border">
                                {lines.map((line, index) => {
                                    const lineTotal =
                                        unitPrice(line.product) * line.qty;
                                    const reserved = line.qty * packsToSell;
                                    const remaining = line.product.quantity;
                                    const lineError =
                                        stockIssues[line.product.id] ??
                                        serverLineError(index);

                                    return (
                                        <li
                                            key={line.product.id}
                                            className={cn(
                                                'flex flex-wrap items-center gap-3 p-3',
                                                lineError &&
                                                    'bg-destructive/5',
                                            )}
                                        >
                                            <Avatar className="size-10 rounded-md">
                                                <AvatarImage
                                                    src={
                                                        line.product
                                                            .image_url ??
                                                        undefined
                                                    }
                                                    alt={line.product.name}
                                                />
                                                <AvatarFallback className="rounded-md">
                                                    {initials(
                                                        line.product.name,
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate font-medium">
                                                    {line.product.name}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {line.product.sku ??
                                                        'No SKU'}{' '}
                                                    ·{' '}
                                                    <ProductUnitPrice
                                                        product={line.product}
                                                    />
                                                </p>
                                                <p
                                                    className={cn(
                                                        'mt-1 text-xs',
                                                        stockIssues[
                                                            line.product.id
                                                        ]
                                                            ? 'text-destructive'
                                                            : 'text-muted-foreground',
                                                    )}
                                                >
                                                    Will reserve {reserved} of{' '}
                                                    {line.product.sku ??
                                                        line.product.name}{' '}
                                                    ({remaining} remaining)
                                                </p>
                                            </div>
                                            {isEdit ? (
                                                <p className="text-sm tabular-nums">
                                                    ×{line.qty}
                                                </p>
                                            ) : (
                                                <div className="flex items-center gap-1">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        className="size-8"
                                                        onClick={() =>
                                                            setQty(
                                                                line.product.id,
                                                                line.qty - 1,
                                                            )
                                                        }
                                                        aria-label={`Decrease ${line.product.name}`}
                                                    >
                                                        <Minus className="size-3.5" />
                                                    </Button>
                                                    <Input
                                                        className={cn(
                                                            'h-8 w-14 text-center tabular-nums',
                                                            lineError &&
                                                                'border-destructive',
                                                        )}
                                                        type="number"
                                                        min={1}
                                                        step={1}
                                                        value={line.qty}
                                                        onChange={(event) =>
                                                            setQty(
                                                                line.product.id,
                                                                Number(
                                                                    event.target
                                                                        .value,
                                                                ),
                                                            )
                                                        }
                                                        aria-label={`${line.product.name} quantity`}
                                                        aria-invalid={
                                                            lineError
                                                                ? true
                                                                : undefined
                                                        }
                                                    />
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        className="size-8"
                                                        onClick={() =>
                                                            setQty(
                                                                line.product.id,
                                                                line.qty + 1,
                                                            )
                                                        }
                                                        aria-label={`Increase ${line.product.name}`}
                                                    >
                                                        <Plus className="size-3.5" />
                                                    </Button>
                                                </div>
                                            )}
                                            <p className="w-20 text-right text-sm font-medium tabular-nums">
                                                {formatAud(lineTotal)}
                                            </p>
                                            {!isEdit ? (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-muted-foreground size-8"
                                                    onClick={() =>
                                                        removeLine(
                                                            line.product.id,
                                                        )
                                                    }
                                                    aria-label={`Remove ${line.product.name}`}
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            ) : null}

                                            {/* Per-product stock validation message */}
                                            {lineError ? (
                                                <div className="basis-full">
                                                    <InputError
                                                        message={lineError}
                                                    />
                                                </div>
                                            ) : null}
                                        </li>
                                    );
                                })}
                            </ul>
                        )}

                        {!isEdit && lines.length === 1 ? (
                            <p className="text-muted-foreground text-sm">
                                Add at least 2 products to make a pack.
                            </p>
                        ) : null}
                        <InputError message={itemErrors.items} />
                    </div>
                </section>
            </div>

            <section className={sectionCardClass}>
                <div className="space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Pack offer
                    </h2>
                </div>

                <div className="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <div className="space-y-4">
                        <div className="grid max-w-xl gap-2">
                            <Label id="pack-offer-type-label">Type</Label>
                            <div
                                role="group"
                                aria-labelledby="pack-offer-type-label"
                                className="grid grid-cols-3 gap-2"
                            >
                                {OFFER_TYPES.map((type) => {
                                    const Icon = type.icon;
                                    const selected =
                                        data.offer_type === type.value;

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
                                                setData((current) => ({
                                                    ...current,
                                                    offer_type: type.value,
                                                }))
                                            }
                                        >
                                            <Icon className="size-3.5" />
                                            {type.label}
                                        </Button>
                                    );
                                })}
                            </div>
                            <InputError message={errors.offer_type} />
                        </div>

                        <div className="grid max-w-xs gap-2">
                            <Label htmlFor="pack-offer-value">
                                {offerInputLabel}
                            </Label>
                            <Input
                                id="pack-offer-value"
                                type="number"
                                step="0.01"
                                min={0}
                                max={
                                    data.offer_type === 'percent'
                                        ? 100
                                        : undefined
                                }
                                placeholder={offerPlaceholder}
                                value={data.offer_value}
                                onChange={(event) =>
                                    setData((current) => ({
                                        ...current,
                                        offer_value: event.target.value,
                                    }))
                                }
                            />
                            <p className="text-muted-foreground text-xs">
                                {offerHelper}
                            </p>
                            <InputError message={errors.offer_value} />
                        </div>
                    </div>

                    <div className="bg-muted/40 grid gap-3 rounded-xl border p-4">
                        <div className="flex items-center justify-between gap-3 text-sm">
                            <span className="text-muted-foreground">
                                Subtotal
                            </span>
                            <span className="tabular-nums">
                                {formatAud(totals.subtotal)}
                            </span>
                        </div>
                        <div className="flex items-center justify-between gap-3 text-sm">
                            <span className="text-muted-foreground">
                                Savings
                            </span>
                            <span className="tabular-nums">
                                {formatAud(totals.savings)}
                            </span>
                        </div>
                        <div className="flex items-center justify-between gap-3 border-t pt-3 text-sm font-semibold">
                            <span>Pack price</span>
                            <span className="tabular-nums">
                                {formatAud(totals.packPrice)}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <div className="flex flex-wrap items-center justify-end gap-2">
                <Button variant="outline" asChild>
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
                <Button type="submit" disabled={processing || hasStockIssues}>
                    {processing ? <Spinner /> : null}
                    Save pack
                </Button>
            </div>
        </form>
    );
}