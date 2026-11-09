import { Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

export type ProductPackageDraft = {
    label: string;
    quantity: string;
    price: string;
    status: boolean;
    sort_order: string;
};

export function emptyProductPackageDraft(
    sortOrder = 0,
): ProductPackageDraft {
    return {
        label: '',
        quantity: '1',
        price: '',
        status: true,
        sort_order: String(sortOrder),
    };
}

type ProductPackagesDraftFieldsProps = {
    packagesEnabled: boolean;
    packages: ProductPackageDraft[];
    errors: Record<string, string>;
    onPackagesEnabledChange: (value: boolean) => void;
    onPackagesChange: (packages: ProductPackageDraft[]) => void;
};

export function ProductPackagesDraftFields({
    packagesEnabled,
    packages,
    errors,
    onPackagesEnabledChange,
    onPackagesChange,
}: ProductPackagesDraftFieldsProps) {
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
        onPackagesChange([
            ...packages,
            emptyProductPackageDraft(packages.length),
        ]);
    };

    const removePackage = (index: number) => {
        if (packages.length <= 1) {
            onPackagesChange([emptyProductPackageDraft(0)]);

            return;
        }

        onPackagesChange(packages.filter((_, rowIndex) => rowIndex !== index));
    };

    return (
        <section className="border-border/80 bg-card space-y-5 rounded-2xl border p-5 shadow-sm sm:p-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Packages
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Optional quantity tiers created with this product. You
                        can still manage them later on edit or view.
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
                    Leave rows blank to skip. Empty drafts are not saved.
                </p>
                <Button type="button" variant="outline" size="sm" onClick={addPackage}>
                    <Plus className="size-4" />
                    Add package
                </Button>
            </div>

            <div className="space-y-4">
                {packages.map((row, index) => (
                    <div
                        key={`package-draft-${index}`}
                        className="border-border/70 grid gap-3 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,0.7fr)_minmax(0,0.9fr)_minmax(0,0.7fr)_auto]"
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
                                value={row.price}
                                onChange={(event) =>
                                    updatePackage(
                                        index,
                                        'price',
                                        event.target.value,
                                    )
                                }
                                placeholder="0.00"
                            />
                            <InputError
                                message={errors[`packages.${index}.price`]}
                            />
                        </div>
                        <div className="flex flex-col gap-2">
                            <Label htmlFor={`package-draft-sort-${index}`}>
                                Sort
                            </Label>
                            <Input
                                id={`package-draft-sort-${index}`}
                                type="number"
                                min={0}
                                value={row.sort_order}
                                onChange={(event) =>
                                    updatePackage(
                                        index,
                                        'sort_order',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={
                                    errors[`packages.${index}.sort_order`]
                                }
                            />
                        </div>
                        <div className="flex items-end justify-between gap-3 sm:col-span-2 lg:col-span-1 lg:flex-col lg:items-stretch lg:justify-end">
                            <label className="flex items-center gap-2 text-sm">
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
