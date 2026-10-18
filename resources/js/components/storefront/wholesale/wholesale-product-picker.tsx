import { Minus, Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import type { ComboboxOption } from '@/components/stock/infinite-scroll-combobox';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { StorefrontProductImage } from '@/components/storefront/storefront-product-image';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

export type WholesalePickerVariant = {
    id: number;
    name: string;
    sku: string | null;
};

export type WholesalePickerLine = {
    /** Stable row identity; product + option may change while editing. */
    key: string;
    productId: number;
    variantId: number | null;
    variants: WholesalePickerVariant[];
    name: string;
    sku: string | null;
    image: string | null;
    packLabel: string | null;
    minQuantity: number | null;
    wholesalePrice: number | null;
    quantity: number;
};

type WholesaleProductPickerProps = {
    optionsUrl: string;
    lines: WholesalePickerLine[];
    onChange: (lines: WholesalePickerLine[]) => void;
    errors?: Record<string, string | undefined>;
    searchPlaceholder?: string;
    triggerClassName?: string;
    disabled?: boolean;
};

const MAX_QUANTITY = 100000;
const ANY_VARIANT = 'any';

function formatMoney(amount: number): string {
    return `$${amount.toFixed(2)}`;
}

function toVariants(value: unknown): WholesalePickerVariant[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value.flatMap((row) => {
        const id = Number((row as { id?: unknown })?.id);
        const name = (row as { name?: unknown })?.name;
        const sku = (row as { sku?: unknown })?.sku;

        return Number.isFinite(id) && id > 0 && typeof name === 'string'
            ? [{ id, name, sku: typeof sku === 'string' ? sku : null }]
            : [];
    });
}

function toLine(option: ComboboxOption): WholesalePickerLine | null {
    const row = option.data ?? {};
    const productId = Number(option.value);

    if (!Number.isFinite(productId) || productId < 1) {
        return null;
    }

    const minQuantity =
        row.min_wholesale_qty == null ? null : Number(row.min_wholesale_qty);
    const wholesalePrice =
        row.wholesale_price == null ? null : Number(row.wholesale_price);

    return {
        key: `${productId}-${Date.now()}`,
        productId,
        variantId: null,
        variants: toVariants(row.variants),
        name: option.label,
        sku: typeof row.sku === 'string' ? row.sku : null,
        image: typeof row.image === 'string' ? row.image : null,
        packLabel: typeof row.pack_label === 'string' ? row.pack_label : null,
        minQuantity,
        wholesalePrice,
        quantity: Math.max(1, minQuantity ?? 1),
    };
}

function sameItem(
    line: WholesalePickerLine,
    productId: number,
    variantId: number | null,
): boolean {
    return line.productId === productId && line.variantId === variantId;
}

function clampQuantity(value: number): number {
    if (!Number.isFinite(value)) {
        return 1;
    }

    return Math.min(MAX_QUANTITY, Math.max(1, Math.trunc(value)));
}

export function WholesaleProductPicker({
    optionsUrl,
    lines,
    onChange,
    errors = {},
    searchPlaceholder = 'Search products',
    triggerClassName,
    disabled = false,
}: WholesaleProductPickerProps) {
    const addOption = (option?: ComboboxOption) => {
        if (!option) {
            return;
        }

        const line = toLine(option);

        if (line === null) {
            return;
        }

        if (lines.some((item) => sameItem(item, line.productId, null))) {
            return;
        }

        onChange([...lines, line]);
    };

    const update = (
        key: string,
        patch: Partial<Pick<WholesalePickerLine, 'quantity' | 'variantId'>>,
    ) => {
        onChange(
            lines.map((line) =>
                line.key === key ? { ...line, ...patch } : line,
            ),
        );
    };

    const remove = (key: string) => {
        onChange(lines.filter((line) => line.key !== key));
    };

    return (
        <div className="space-y-3">
            <InfiniteScrollCombobox
                value={null}
                optionsUrl={optionsUrl}
                onValueChange={(_, option) => addOption(option)}
                placeholder={searchPlaceholder}
                searchPlaceholder={searchPlaceholder}
                emptyText="No products found."
                className={triggerClassName}
                disabled={disabled}
            />

            {lines.length > 0 ? (
                <ul className="flex min-w-0 flex-col gap-2">
                    {lines.map((line, index) => {
                        const belowMinimum =
                            line.minQuantity !== null &&
                            line.quantity < line.minQuantity;
                        const variant = line.variants.find(
                            (item) => item.id === line.variantId,
                        );
                        const quantityError =
                            errors[`items.${index}.quantity`] ??
                            errors[`items.${index}.product_id`] ??
                            errors[`items.${index}.variant_id`];

                        return (
                            <li
                                key={line.key}
                                className="border-border bg-background flex min-w-0 flex-col gap-3 border p-3 sm:flex-row sm:flex-wrap sm:items-center"
                            >
                                <div className="flex min-w-0 flex-1 items-center gap-3">
                                    <div className="bg-muted relative size-12 shrink-0 overflow-hidden">
                                        <StorefrontProductImage
                                            src={line.image}
                                            alt={line.name}
                                        />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-foreground truncate text-sm font-bold">
                                            {line.name}
                                        </p>
                                        <p className="text-muted-foreground truncate text-xs">
                                            {[
                                                variant?.sku ?? line.sku,
                                                line.packLabel,
                                                line.wholesalePrice !== null
                                                    ? `from ${formatMoney(line.wholesalePrice)}`
                                                    : null,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                        {line.minQuantity !== null ? (
                                            <p
                                                className={cn(
                                                    'text-xs font-medium',
                                                    belowMinimum
                                                        ? 'text-destructive'
                                                        : 'text-muted-foreground',
                                                )}
                                            >
                                                Minimum order {line.minQuantity}
                                            </p>
                                        ) : null}
                                    </div>
                                </div>

                                {line.variants.length > 0 ? (
                                    <Select
                                        value={
                                            line.variantId === null
                                                ? ANY_VARIANT
                                                : String(line.variantId)
                                        }
                                        onValueChange={(next) =>
                                            update(line.key, {
                                                variantId:
                                                    next === ANY_VARIANT
                                                        ? null
                                                        : Number(next),
                                            })
                                        }
                                        disabled={disabled}
                                    >
                                        <SelectTrigger
                                            className="border-border h-9 w-full rounded-none bg-transparent shadow-none data-[size=default]:h-9 sm:w-40"
                                            aria-label={`${line.name} option`}
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent className="rounded-none">
                                            <SelectItem
                                                value={ANY_VARIANT}
                                                disabled={lines.some(
                                                    (item) =>
                                                        item.key !== line.key &&
                                                        sameItem(
                                                            item,
                                                            line.productId,
                                                            null,
                                                        ),
                                                )}
                                            >
                                                Any option
                                            </SelectItem>
                                            {line.variants.map((option) => (
                                                <SelectItem
                                                    key={option.id}
                                                    value={String(option.id)}
                                                    disabled={lines.some(
                                                        (item) =>
                                                            item.key !==
                                                                line.key &&
                                                            sameItem(
                                                                item,
                                                                line.productId,
                                                                option.id,
                                                            ),
                                                    )}
                                                >
                                                    {option.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : null}

                                <div className="flex h-9 shrink-0 items-center gap-3">
                                    <div className="border-primary/50 flex h-9 items-center border">
                                        <button
                                            type="button"
                                            className="text-foreground flex size-9 items-center justify-center disabled:cursor-not-allowed disabled:opacity-40"
                                            aria-label={`Decrease ${line.name} quantity`}
                                            disabled={
                                                disabled || line.quantity <= 1
                                            }
                                            onClick={() =>
                                                update(line.key, {
                                                    quantity: clampQuantity(
                                                        line.quantity - 1,
                                                    ),
                                                })
                                            }
                                        >
                                            <Minus className="size-3.5" />
                                        </button>
                                        <input
                                            type="number"
                                            inputMode="numeric"
                                            min={1}
                                            max={MAX_QUANTITY}
                                            value={line.quantity}
                                            disabled={disabled}
                                            aria-label={`${line.name} quantity`}
                                            onChange={(event) =>
                                                update(line.key, {
                                                    quantity: clampQuantity(
                                                        Number(
                                                            event.target.value,
                                                        ),
                                                    ),
                                                })
                                            }
                                            className="text-foreground h-9 w-16 [appearance:textfield] bg-transparent text-center text-sm font-semibold outline-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                        />
                                        <button
                                            type="button"
                                            className="text-foreground flex size-9 items-center justify-center disabled:cursor-not-allowed disabled:opacity-40"
                                            aria-label={`Increase ${line.name} quantity`}
                                            disabled={
                                                disabled ||
                                                line.quantity >= MAX_QUANTITY
                                            }
                                            onClick={() =>
                                                update(line.key, {
                                                    quantity: clampQuantity(
                                                        line.quantity + 1,
                                                    ),
                                                })
                                            }
                                        >
                                            <Plus className="size-3.5" />
                                        </button>
                                    </div>
                                    <button
                                        type="button"
                                        className="text-muted-foreground hover:text-destructive flex size-9 shrink-0 items-center justify-center"
                                        aria-label={`Remove ${line.name}`}
                                        disabled={disabled}
                                        onClick={() => remove(line.key)}
                                    >
                                        <Trash2 className="size-4" />
                                    </button>
                                </div>

                                <InputError
                                    className="sm:basis-full"
                                    message={quantityError}
                                />
                            </li>
                        );
                    })}
                </ul>
            ) : null}

            <InputError message={errors.items} />
        </div>
    );
}
