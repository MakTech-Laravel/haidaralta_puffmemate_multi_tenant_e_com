import { Link } from '@inertiajs/react';
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
import { toast } from 'sonner';
import FileUpload from '@/components/file-upload';
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
};

type PackLine = {
    product: PackProductOption;
    qty: number;
};

type OfferType = 'fixed' | 'percent' | 'pack_price';

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
    availableProducts,
    cancelHref,
}: {
    availableProducts: PackProductOption[];
    cancelHref: string;
}) {
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [image, setImage] = useState<File | null>(null);
    const [status, setStatus] = useState(true);
    const [startsAt, setStartsAt] = useState('');
    const [endsAt, setEndsAt] = useState('');
    const [lines, setLines] = useState<PackLine[]>([]);
    const [offerType, setOfferType] = useState<OfferType>('percent');
    const [offerValue, setOfferValue] = useState('20');
    const [pickerOpen, setPickerOpen] = useState(false);

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

    const totals = useMemo(() => {
        const subtotal = lines.reduce(
            (sum, line) => sum + unitPrice(line.product) * line.qty,
            0,
        );
        const rawOffer = parseMoney(offerValue);

        if (offerType === 'fixed') {
            const savings = Math.min(Math.max(rawOffer, 0), subtotal);

            return {
                subtotal,
                savings,
                packPrice: Math.max(0, subtotal - savings),
            };
        }

        if (offerType === 'percent') {
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
    }, [lines, offerType, offerValue]);

    function addProduct(product: PackProductOption): void {
        setLines((current) => {
            if (current.some((line) => line.product.id === product.id)) {
                return current;
            }

            return [...current, { product, qty: 1 }];
        });
        setPickerOpen(false);
    }

    function setQty(productId: number, qty: number): void {
        const next = Number.isFinite(qty) ? Math.max(1, Math.floor(qty)) : 1;

        setLines((current) =>
            current.map((line) =>
                line.product.id === productId ? { ...line, qty: next } : line,
            ),
        );
    }

    function removeLine(productId: number): void {
        setLines((current) =>
            current.filter((line) => line.product.id !== productId),
        );
    }

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();
    }

    function handleSave(): void {
        toast.info('Saving packs comes next.');
    }

    const offerInputLabel =
        offerType === 'percent'
            ? 'Discount value (%)'
            : offerType === 'fixed'
              ? 'Discount value (AUD)'
              : 'Pack price (AUD)';

    const offerPlaceholder =
        offerType === 'percent'
            ? 'e.g. 10'
            : offerType === 'fixed'
              ? 'e.g. 5.00'
              : 'e.g. 80.00';

    const offerHelper =
        offerType === 'percent'
            ? 'Percent off the combined product total (0–100).'
            : offerType === 'fixed'
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
                                    Inactive packs stay hidden once saving is
                                    wired.
                                </p>
                            </div>
                            <Switch
                                id="pack-status"
                                checked={status}
                                onCheckedChange={setStatus}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="pack-name">
                                Name{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="pack-name"
                                value={name}
                                onChange={(event) =>
                                    setName(event.target.value)
                                }
                                placeholder="e.g. Starter trio"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="pack-description">
                                Description
                            </Label>
                            <Textarea
                                id="pack-description"
                                value={description}
                                onChange={(event) =>
                                    setDescription(event.target.value)
                                }
                                placeholder="What is in this pack and why it is a save."
                                rows={4}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>Cover image</Label>
                            <FileUpload
                                value={image}
                                onChange={(file) => {
                                    const next = Array.isArray(file)
                                        ? (file[0] ?? null)
                                        : file;
                                    setImage(next);
                                }}
                                accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,.png,.jpg,.jpeg,.webp,.svg"
                                maxSize={2}
                                lightbox
                                placeholder="Drop or click"
                                hint="PNG, JPG, SVG, or WebP · Max 2MB"
                                compactCards
                            />
                        </div>

                        <div className="grid min-w-0 items-start gap-4 sm:grid-cols-2">
                            <div className="grid min-w-0 gap-2">
                                <Label htmlFor="pack-starts-at">
                                    Start date
                                </Label>
                                <DatePicker
                                    id="pack-starts-at"
                                    value={startsAt}
                                    onChange={setStartsAt}
                                    max={endsAt || undefined}
                                    placeholder="Optional start"
                                />
                            </div>
                            <div className="grid min-w-0 gap-2">
                                <Label htmlFor="pack-ends-at">End date</Label>
                                <DatePicker
                                    id="pack-ends-at"
                                    value={endsAt}
                                    onChange={setEndsAt}
                                    min={startsAt || undefined}
                                    placeholder="Optional end"
                                />
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
                            Choose this branch’s stock products. Quantity is per
                            pack.
                        </p>
                    </div>

                    <div className="mt-5 space-y-4">
                        {availableProducts.length === 0 ? (
                            <Alert>
                                <Package2 />
                                <AlertTitle>
                                    No stock products on this branch
                                </AlertTitle>
                                <AlertDescription>
                                    Add products first, then come back to build
                                    a pack.
                                </AlertDescription>
                            </Alert>
                        ) : (
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
                        )}

                        {lines.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Add at least 2 products to make a pack.
                            </p>
                        ) : (
                            <ul className="divide-border divide-y rounded-xl border">
                                {lines.map((line) => {
                                    const lineTotal =
                                        unitPrice(line.product) * line.qty;

                                    return (
                                        <li
                                            key={line.product.id}
                                            className="flex flex-wrap items-center gap-3 p-3"
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
                                            </div>
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
                                                    className="h-8 w-14 text-center tabular-nums"
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
                                            <p className="w-20 text-right text-sm font-medium tabular-nums">
                                                {formatAud(lineTotal)}
                                            </p>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="text-muted-foreground size-8"
                                                onClick={() =>
                                                    removeLine(line.product.id)
                                                }
                                                aria-label={`Remove ${line.product.name}`}
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}

                        {lines.length === 1 ? (
                            <p className="text-muted-foreground text-sm">
                                Add at least 2 products to make a pack.
                            </p>
                        ) : null}
                    </div>
                </section>
            </div>

            <section className={sectionCardClass}>
                <div className="space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Pack offer
                    </h2>
                    {/* <p className="text-muted-foreground text-sm">
                        3 products totaling $100 → set $80, $20 off, or 20%.
                    </p> */}
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
                                    const selected = offerType === type.value;

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
                                                setOfferType(type.value)
                                            }
                                        >
                                            <Icon className="size-3.5" />
                                            {type.label}
                                        </Button>
                                    );
                                })}
                            </div>
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
                                    offerType === 'percent' ? 100 : undefined
                                }
                                placeholder={offerPlaceholder}
                                value={offerValue}
                                onChange={(event) =>
                                    setOfferValue(event.target.value)
                                }
                            />
                            <p className="text-muted-foreground text-xs">
                                {offerHelper}
                            </p>
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
                <Button type="button" onClick={handleSave}>
                    Save pack
                </Button>
            </div>
        </form>
    );
}
