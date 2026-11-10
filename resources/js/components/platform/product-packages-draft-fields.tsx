import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

export const DEFAULT_PRODUCT_PACKAGE_LABEL = '1 Item';

export type ProductPackageDraft = {
    label: string;
    quantity: string;
    price: string;
    status: boolean;
    is_default: boolean;
};

/**
 * Package price seed from the product selling price × quantity.
 */
export function calculatedPackagePrice(
    sellingPrice: string,
    quantity: string,
): string {
    const unit = Number(sellingPrice);
    const qty = Number(quantity);

    if (!Number.isFinite(unit) || unit < 0 || !Number.isFinite(qty) || qty < 1) {
        return '';
    }

    return (unit * qty).toFixed(2);
}

export function emptyProductPackageDraft(
    sellingPrice = '',
): ProductPackageDraft {
    return {
        label: '',
        quantity: '1',
        price: calculatedPackagePrice(sellingPrice, '1'),
        status: true,
        is_default: false,
    };
}

/**
 * The protected single-unit package. Quantity stays locked at 1; label and
 * price are editable. A blank price means "use the product selling price".
 */
export function defaultProductPackageDraft(
    sellingPrice = '',
): ProductPackageDraft {
    return {
        label: DEFAULT_PRODUCT_PACKAGE_LABEL,
        quantity: '1',
        price: calculatedPackagePrice(sellingPrice, '1'),
        status: true,
        is_default: true,
    };
}

type ProductPackagesDraftFieldsProps = {
    packagesEnabled: boolean;
    packages: ProductPackageDraft[];
    /** Current selling price of the product form; seeds package prices. */
    sellingPrice: string;
    errors: Record<string, string>;
    onPackagesEnabledChange: (value: boolean) => void;
    onPackagesChange: (packages: ProductPackageDraft[]) => void;
};

export function ProductPackagesDraftFields({
    packagesEnabled,
    packages,
    sellingPrice,
    errors,
    onPackagesEnabledChange,
    onPackagesChange,
}: ProductPackagesDraftFieldsProps) {
    const [priceTouched, setPriceTouched] = useState<Record<number, boolean>>(
        {},
    );

    useEffect(() => {
        const next = packages.map((row, index) => {
            if (priceTouched[index]) {
                return row;
            }

            const calculated = calculatedPackagePrice(
                sellingPrice,
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
        // Only re-seed when the product selling price changes.
        // eslint-disable-next-line react-hooks/exhaustive-deps -- intentional
    }, [sellingPrice]);

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
        const calculated = calculatedPackagePrice(sellingPrice, quantity);

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
        onPackagesChange([...packages, emptyProductPackageDraft(sellingPrice)]);
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
                        Quantity tiers created with this product. The default
                        package cannot be deleted; its quantity stays at 1.
                        Changing quantity recalculates price from the selling
                        price.
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
                    Use the arrows to set the storefront order. Extra rows
                    without a label are not saved.
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
                            <Label
                                htmlFor={`package-draft-label-${index}`}
                                className="flex items-center gap-2"
                            >
                                Label
                                {row.is_default ? (
                                    <Badge variant="secondary">Default</Badge>
                                ) : null}
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
                                value={row.quantity}
                                disabled={row.is_default}
                                onChange={(event) =>
                                    updateQuantity(index, event.target.value)
                                }
                            />
                            {row.is_default ? (
                                <p className="text-muted-foreground text-xs">
                                    Default quantity is fixed at 1.
                                </p>
                            ) : null}
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
                                placeholder="0.00"
                            />
                            <p className="text-muted-foreground text-xs">
                                Auto-fills as selling price × quantity. You can
                                still edit it.
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
                            {row.is_default ? null : (
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
                            )}
                        </div>
                    </div>
                ))}
            </div>
            <InputError message={errors.packages} />
            <InputError message={errors.packages_enabled} />
        </section>
    );
}
