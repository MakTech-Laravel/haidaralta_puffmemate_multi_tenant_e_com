import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect } from 'react';
import InputError from '@/components/input-error';
import {
    calculatedPackagePrice,
    packageUnitPrice,
} from '@/components/platform/product-packages-draft-fields';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type FormAction = {
    action: string;
    method?: string;
};

interface ProductPackageFormDefaults {
    label?: string;
    quantity?: number;
    price?: string | number | null;
    status?: boolean;
}

interface ProductPackageFormProps {
    action: FormAction;
    defaults?: ProductPackageFormDefaults;
    /** Product selling price used to auto-fill price from quantity. */
    sellingPrice?: string | number | null;
    /** Fallback when selling price is empty or 0. */
    regularPrice?: string | number | null;
    isEdit?: boolean;
    onCancel: () => void;
    onSuccess?: () => void;
    submitLabel?: string;
}

export function ProductPackageForm({
    action,
    defaults,
    sellingPrice = '',
    regularPrice = '',
    isEdit = false,
    onCancel,
    onSuccess,
    submitLabel,
}: ProductPackageFormProps) {
    const formMethod = (action.method ?? 'post').toLowerCase() as
        | 'get'
        | 'post'
        | 'put'
        | 'patch'
        | 'delete';

    const unitPrice = packageUnitPrice(sellingPrice, regularPrice);
    const initialQuantity =
        defaults?.quantity !== undefined && defaults?.quantity !== null
            ? String(defaults.quantity)
            : '1';
    const initialPrice =
        defaults?.price !== undefined && defaults?.price !== null
            ? String(defaults.price)
            : calculatedPackagePrice(unitPrice, initialQuantity);

    const {
        data,
        setData,
        post,
        processing,
        errors,
        setValidationTimeout,
        setError,
        clearErrors,
    } = useForm(formMethod, action.action, {
        label: defaults?.label ?? '',
        quantity: initialQuantity,
        price: initialPrice,
        status: defaults?.status ?? true,
        ...(isEdit ? { _method: 'put' } : {}),
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        clearErrors('label', 'quantity', 'price');

        const nextErrors: Partial<Record<'label' | 'quantity' | 'price', string>> =
            {};

        if (data.label.trim() === '') {
            nextErrors.label = 'Enter a package label.';
        }

        const quantity = Number(data.quantity);

        if (
            String(data.quantity).trim() === '' ||
            !Number.isFinite(quantity) ||
            !Number.isInteger(quantity) ||
            quantity < 1
        ) {
            nextErrors.quantity = 'Enter a quantity of at least 1.';
        }

        const price = Number(data.price);

        if (
            String(data.price).trim() === '' ||
            !Number.isFinite(price) ||
            price < 0
        ) {
            nextErrors.price = 'Enter a package price.';
        }

        if (Object.keys(nextErrors).length > 0) {
            setError(nextErrors);

            return;
        }

        post(action.action, {
            preserveScroll: true,
            onSuccess: () => onSuccess?.(),
        });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-5">
            <div className="flex flex-col gap-2">
                <Label htmlFor="package-label">Package label</Label>
                <Input
                    id="package-label"
                    value={data.label}
                    onChange={(event) => setData('label', event.target.value)}
                    required
                    placeholder="e.g. Pack of 5"
                />
                <InputError message={errors.label} />
            </div>

            <div className="grid gap-5 sm:grid-cols-2">
                <div className="flex flex-col gap-2">
                    <Label htmlFor="package-quantity">Quantity</Label>
                    <Input
                        id="package-quantity"
                        type="number"
                        min={1}
                        step={1}
                        value={data.quantity}
                        onChange={(event) => {
                            const quantity = event.target.value;
                            const calculated = calculatedPackagePrice(
                                unitPrice,
                                quantity,
                            );

                            setData('quantity', quantity);

                            if (calculated !== '') {
                                setData('price', calculated);
                            }
                        }}
                        required
                    />
                    <InputError message={errors.quantity} />
                </div>

                <div className="flex flex-col gap-2">
                    <Label htmlFor="package-price">Package price</Label>
                    <Input
                        id="package-price"
                        type="number"
                        min={0}
                        step="0.01"
                        value={data.price}
                        onChange={(event) =>
                            setData('price', event.target.value)
                        }
                        required
                        placeholder="0.00"
                    />
                    <p className="text-muted-foreground text-xs">
                        Auto-fills as unit price × quantity (selling, or regular
                        when selling is empty/0). You can still edit it.
                    </p>
                    <InputError message={errors.price} />
                </div>
            </div>

            <div className="flex items-center gap-2">
                <Checkbox
                    id="package-status"
                    checked={data.status}
                    onCheckedChange={(value) =>
                        setData('status', value === true)
                    }
                />
                <Label htmlFor="package-status" className="font-normal">
                    Enabled
                </Label>
                <InputError message={errors.status} />
            </div>

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {processing ? <Spinner /> : null}
                    {submitLabel ?? (isEdit ? 'Save package' : 'Add package')}
                </Button>
                <Button type="button" variant="secondary" onClick={onCancel}>
                    Cancel
                </Button>
            </div>
        </form>
    );
}
