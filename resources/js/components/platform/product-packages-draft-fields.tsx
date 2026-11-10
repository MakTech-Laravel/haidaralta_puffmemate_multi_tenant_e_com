import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
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

export function emptyProductPackageDraft(): ProductPackageDraft {
    return {
        label: '',
        quantity: '1',
        price: '',
        status: true,
        is_default: false,
    };
}

/**
 * The protected single-unit package. A blank price means "use the product
 * selling price", which the server resolves on save.
 */
export function defaultProductPackageDraft(): ProductPackageDraft {
    return {
        label: DEFAULT_PRODUCT_PACKAGE_LABEL,
        quantity: '1',
        price: '',
        status: true,
        is_default: true,
    };
}

type ProductPackagesDraftFieldsProps = {
    packagesEnabled: boolean;
    packages: ProductPackageDraft[];
    /** Current selling price of the product form; seeds the default package price. */
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
    const [defaultPriceTouched, setDefaultPriceTouched] = useState(false);

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

    const addPackage = () => {
        onPackagesChange([...packages, emptyProductPackageDraft()]);
    };

    const removePackage = (index: number) => {
        onPackagesChange(packages.filter((_, rowIndex) => rowIndex !== index));
    };

    const movePackage = (index: number, offset: -1 | 1) => {
        const target = index + offset;

        if (target < 0 || target >= packages.length) {
            return;
        }

        const next = [...packages];
        [next[index], next[target]] = [next[target], next[index]];
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
                        Quantity tiers created with this product. The{' '}
                        {DEFAULT_PRODUCT_PACKAGE_LABEL} package is always
                        created and cannot be deleted.
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
                                disabled={row.is_default}
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
                                    updatePackage(
                                        index,
                                        'quantity',
                                        event.target.value,
                                    )
                                }
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
                                value={
                                    row.is_default && !defaultPriceTouched
                                        ? sellingPrice
                                        : row.price
                                }
                                onChange={(event) => {
                                    if (row.is_default) {
                                        setDefaultPriceTouched(true);
                                    }

                                    updatePackage(
                                        index,
                                        'price',
                                        event.target.value,
                                    );
                                }}
                                placeholder="0.00"
                            />
                            {row.is_default ? (
                                <p className="text-muted-foreground text-xs">
                                    Starts from the selling price. Leave blank
                                    to use it.
                                </p>
                            ) : null}
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
