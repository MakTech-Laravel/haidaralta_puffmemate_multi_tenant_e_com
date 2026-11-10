import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

export type ProductPackageDraft = {
    id?: number | null;
    label: string;
    quantity: string;
    price: string;
    status: boolean;
};

/**
 * Unit price for package auto-fill: selling price when present and > 0,
 * otherwise regular price when present and > 0.
 */
export function packageUnitPrice(
    sellingPrice: string | number | null | undefined,
    regularPrice: string | number | null | undefined = null,
): string {
    const selling = Number(sellingPrice);

    if (Number.isFinite(selling) && selling > 0) {
        return String(selling);
    }

    const regular = Number(regularPrice);

    if (Number.isFinite(regular) && regular > 0) {
        return String(regular);
    }

    return '';
}

/**
 * Package price seed from the product unit price × quantity.
 */
export function calculatedPackagePrice(
    unitPrice: string,
    quantity: string,
): string {
    const unit = Number(unitPrice);
    const qty = Number(quantity);

    if (!Number.isFinite(unit) || unit < 0 || !Number.isFinite(qty) || qty < 1) {
        return '';
    }

    return (unit * qty).toFixed(2);
}

export function emptyProductPackageDraft(
    unitPrice = '',
): ProductPackageDraft {
    return {
        label: '',
        quantity: '1',
        price: calculatedPackagePrice(unitPrice, '1'),
        status: true,
    };
}

/**
 * Every package draft row must have label, quantity, and price — or be removed.
 */
export function validateProductPackageDrafts(
    packages: ProductPackageDraft[],
): Record<string, string> {
    const errors: Record<string, string> = {};

    packages.forEach((row, index) => {
        if (row.label.trim() === '') {
            errors[`packages.${index}.label`] =
                'Enter a package label, or remove this row.';
        }

        const quantity = Number(row.quantity);

        if (
            row.quantity.trim() === '' ||
            !Number.isFinite(quantity) ||
            !Number.isInteger(quantity) ||
            quantity < 1
        ) {
            errors[`packages.${index}.quantity`] =
                'Enter a quantity of at least 1, or remove this row.';
        }

        const price = Number(row.price);

        if (
            row.price.trim() === '' ||
            !Number.isFinite(price) ||
            price < 0
        ) {
            errors[`packages.${index}.price`] =
                'Enter a package price, or remove this row.';
        }
    });

    return errors;
}

type ProductPackagesDraftFieldsProps = {
    packagesEnabled: boolean;
    packages: ProductPackageDraft[];
    /** Current selling price of the product form; preferred unit for package prices. */
    sellingPrice: string;
    /** Fallback unit when selling price is empty or 0. */
    regularPrice?: string;
    errors: Record<string, string>;
    onPackagesEnabledChange: (value: boolean) => void;
    onPackagesChange: (packages: ProductPackageDraft[]) => void;
};

export function ProductPackagesDraftFields({
    packagesEnabled,
    packages,
    sellingPrice,
    regularPrice = '',
    errors,
    onPackagesEnabledChange,
    onPackagesChange,
}: ProductPackagesDraftFieldsProps) {
    const [priceTouched, setPriceTouched] = useState<Record<number, boolean>>(
        {},
    );
    const unitPrice = packageUnitPrice(sellingPrice, regularPrice);

    useEffect(() => {
        const next = packages.map((row, index) => {
            if (priceTouched[index]) {
                return row;
            }

            const calculated = calculatedPackagePrice(
                unitPrice,
                row.quantity,
            );

            if (calculated === '' || calculated === row.price) {
                return row;
            }

            return { ...row, price: calculated };
        });

        const changed = next.some(
            (row, index) => row.price !== packages[index]?.price,
        );

        if (changed) {
            onPackagesChange(next);
        }
        // Only re-seed when the resolved unit price changes.
        // eslint-disable-next-line react-hooks/exhaustive-deps -- intentional
    }, [unitPrice]);

    const updatePackage = (
        index: number,
        key: keyof ProductPackageDraft,
        value: string | boolean,
    ) => {
        onPackagesChange(
            packages.map((row, rowIndex) =>
                rowIndex === index ? { ...row, [key]: value } : row,
            ),
        );
    };

    const updateQuantity = (index: number, quantity: string) => {
        const calculated = calculatedPackagePrice(unitPrice, quantity);

        setPriceTouched((current) => {
            const next = { ...current };
            delete next[index];

            return next;
        });

        onPackagesChange(
            packages.map((row, rowIndex) =>
                rowIndex === index
                    ? {
                          ...row,
                          quantity,
                          ...(calculated !== '' ? { price: calculated } : {}),
                      }
                    : row,
            ),
        );
    };

    const addPackage = () => {
        onPackagesChange([...packages, emptyProductPackageDraft(unitPrice)]);
    };

    const removePackage = (index: number) => {
        setPriceTouched((current) => {
            const next: Record<number, boolean> = {};

            Object.entries(current).forEach(([key, touched]) => {
                const rowIndex = Number(key);

                if (rowIndex < index) {
                    next[rowIndex] = touched;
                } else if (rowIndex > index) {
                    next[rowIndex - 1] = touched;
                }
            });

            return next;
        });

        onPackagesChange(packages.filter((_, rowIndex) => rowIndex !== index));
    };

    const movePackage = (index: number, offset: -1 | 1) => {
        const target = index + offset;

        if (target < 0 || target >= packages.length) {
            return;
        }

        const next = [...packages];
        [next[index], next[target]] = [next[target], next[index]];

        setPriceTouched((current) => {
            const swapped = { ...current };
            const a = current[index];
            const b = current[target];

            if (a) {
                swapped[target] = a;
            } else {
                delete swapped[target];
            }

            if (b) {
                swapped[index] = b;
            } else {
                delete swapped[index];
            }

            return swapped;
        });

        onPackagesChange(next);
    };

    return (
        <section className="border-border/80 bg-card space-y-5 rounded-2xl border p-5 shadow-sm sm:p-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Packages
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Optional quantity tiers for this product. Changing
                        quantity recalculates price from selling price, or
                        regular price when selling is empty or 0.
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <Switch
                        id="create-packages-enabled"
                        checked={packagesEnabled}
                        onCheckedChange={onPackagesEnabledChange}
                    />
                    <Label
                        htmlFor="create-packages-enabled"
                        className="text-sm font-normal"
                    >
                        Available on storefront
                    </Label>
                </div>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-muted-foreground text-xs">
                    Use the arrows to set the storefront order. Fill label,
                    quantity, and price on every row, or remove it before
                    saving.
                </p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addPackage}
                >
                    <Plus className="size-4" />
                    Add package
                </Button>
            </div>

            <div className="space-y-4">
                {packages.map((row, index) => (
                    <div
                        key={`package-draft-${index}`}
                        className="border-border/70 grid gap-3 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,0.7fr)_minmax(0,0.9fr)_auto]"
                    >
                        <div className="flex flex-col gap-2">
                            <Label htmlFor={`package-draft-label-${index}`}>
                                Label
                            </Label>
                            <Input
                                id={`package-draft-label-${index}`}
                                value={row.label}
                                onChange={(event) =>
                                    updatePackage(
                                        index,
                                        'label',
                                        event.target.value,
                                    )
                                }
                                required
                                placeholder="e.g. Pack of 5"
                            />
                            <InputError
                                message={errors[`packages.${index}.label`]}
                            />
                        </div>
                        <div className="flex flex-col gap-2">
                            <Label htmlFor={`package-draft-quantity-${index}`}>
                                Quantity
                            </Label>
                            <Input
                                id={`package-draft-quantity-${index}`}
                                type="number"
                                min={1}
                                step={1}
                                value={row.quantity}
                                onChange={(event) =>
                                    updateQuantity(index, event.target.value)
                                }
                                required
                            />
                            <InputError
                                message={errors[`packages.${index}.quantity`]}
                            />
                        </div>
                        <div className="flex flex-col gap-2">
                            <Label htmlFor={`package-draft-price-${index}`}>
                                Price
                            </Label>
                            <Input
                                id={`package-draft-price-${index}`}
                                type="number"
                                min={0}
                                step="0.01"
                                value={row.price}
                                onChange={(event) => {
                                    setPriceTouched((current) => ({
                                        ...current,
                                        [index]: true,
                                    }));
                                    updatePackage(
                                        index,
                                        'price',
                                        event.target.value,
                                    );
                                }}
                                required
                                placeholder="0.00"
                            />
                            <p className="text-muted-foreground text-xs">
                                Auto-fills as unit price × quantity (selling, or
                                regular when selling is empty/0). You can still
                                edit it.
                            </p>
                            <InputError
                                message={errors[`packages.${index}.price`]}
                            />
                        </div>
                        <div className="flex flex-wrap items-center gap-2 sm:col-span-2 lg:col-span-1 lg:self-start lg:pt-7">
                            <label className="mr-1 flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={row.status}
                                    onCheckedChange={(checked) =>
                                        updatePackage(
                                            index,
                                            'status',
                                            checked === true,
                                        )
                                    }
                                />
                                Enabled
                            </label>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="size-9 shrink-0"
                                disabled={index === 0}
                                onClick={() => movePackage(index, -1)}
                                aria-label="Move package up"
                            >
                                <ArrowUp className="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="size-9 shrink-0"
                                disabled={index === packages.length - 1}
                                onClick={() => movePackage(index, 1)}
                                aria-label="Move package down"
                            >
                                <ArrowDown className="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="size-9 shrink-0"
                                onClick={() => removePackage(index)}
                                aria-label="Remove package draft"
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    </div>
                ))}
            </div>
            <InputError message={errors.packages} />
            <InputError message={errors.packages_enabled} />
        </section>
    );
}
