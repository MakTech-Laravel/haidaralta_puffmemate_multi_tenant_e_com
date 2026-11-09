import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect } from 'react';
import InputError from '@/components/input-error';
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
    sort_order?: number;
}

interface ProductPackageFormProps {
    action: FormAction;
    defaults?: ProductPackageFormDefaults;
    isEdit?: boolean;
    onCancel: () => void;
    onSuccess?: () => void;
    submitLabel?: string;
}

export function ProductPackageForm({
    action,
    defaults,
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

    const {
        data,
        setData,
        post,
        processing,
        errors,
        setValidationTimeout,
    } = useForm(formMethod, action.action, {
        label: defaults?.label ?? '',
        quantity:
            defaults?.quantity !== undefined && defaults?.quantity !== null
                ? String(defaults.quantity)
                : '1',
        price:
            defaults?.price !== undefined && defaults?.price !== null
                ? String(defaults.price)
                : '',
        status: defaults?.status ?? true,
        sort_order:
            defaults?.sort_order !== undefined && defaults?.sort_order !== null
                ? String(defaults.sort_order)
                : '0',
        ...(isEdit ? { _method: 'put' } : {}),
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

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
                        onChange={(event) =>
                            setData('quantity', event.target.value)
                        }
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
                    <InputError message={errors.price} />
                </div>
            </div>

            <div className="flex flex-col gap-2">
                <Label htmlFor="package-sort-order">Sort order</Label>
                <Input
                    id="package-sort-order"
                    type="number"
                    min={0}
                    step={1}
                    value={data.sort_order}
                    onChange={(event) =>
                        setData('sort_order', event.target.value)
                    }
                />
                <InputError message={errors.sort_order} />
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
