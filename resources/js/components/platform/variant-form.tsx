import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type FormAction = {
    action: string;
    method?: string;
};

interface VariantFormDefaults {
    name?: string;
    sku?: string | null;
    attributes?: Record<string, string> | null;
    status?: boolean;
}

interface VariantFormProps {
    action: FormAction;
    defaults?: VariantFormDefaults;
    isEdit?: boolean;
    onCancel: () => void;
    onSuccess?: () => void;
    submitLabel?: string;
}

function attributesToText(
    attributes: Record<string, string> | null | undefined,
): string {
    if (!attributes || Object.keys(attributes).length === 0) {
        return '';
    }

    return JSON.stringify(attributes, null, 2);
}

export function VariantForm({
    action,
    defaults,
    isEdit = false,
    onCancel,
    onSuccess,
    submitLabel,
}: VariantFormProps) {
    const formMethod = (
        action.method ?? 'post'
    ).toLowerCase() as 'get' | 'post' | 'put' | 'patch' | 'delete';

    const {
        data,
        setData,
        post,
        processing,
        errors,
        transform,
        validate,
        setValidationTimeout,
    } = useForm(formMethod, action.action, {
        name: defaults?.name ?? '',
        sku: defaults?.sku ?? '',
        attributes: attributesToText(defaults?.attributes),
        status: defaults?.status ?? true,
        ...(isEdit ? { _method: 'put' } : {}),
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        transform((form) => {
            const attributesText = form.attributes.trim();
            let attributes: Record<string, string> | null = null;

            if (attributesText !== '') {
                try {
                    const parsed = JSON.parse(attributesText) as unknown;

                    if (
                        parsed !== null &&
                        typeof parsed === 'object' &&
                        !Array.isArray(parsed)
                    ) {
                        attributes = parsed as Record<string, string>;
                    }
                } catch {
                    attributes = null;
                }
            }

            return {
                name: form.name,
                sku: form.sku,
                status: form.status,
                attributes,
                ...(isEdit ? { _method: 'put' } : {}),
            };
        });

        post(action.action, {
            preserveScroll: true,
            onSuccess: () => onSuccess?.(),
        });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-5">
            <div className="flex flex-col gap-2">
                <Label htmlFor="variant-name">Variant name</Label>
                <Input
                    id="variant-name"
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    required
                    placeholder="e.g. 50mg · Blue Razz"
                />
                <InputError message={errors.name} />
            </div>

            <div className="flex flex-col gap-2">
                <Label htmlFor="variant-sku">SKU</Label>
                <Input
                    id="variant-sku"
                    value={data.sku}
                    onChange={(event) => setData('sku', event.target.value)}
                    onBlur={() => {
                        if (data.sku.trim() !== '') {
                            validate('sku');
                        }
                    }}
                    placeholder="e.g. MNG-BR-50"
                />
                <InputError message={errors.sku} />
            </div>

            <div className="flex flex-col gap-2">
                <Label htmlFor="variant-attributes">Attributes (JSON)</Label>
                <Textarea
                    id="variant-attributes"
                    value={data.attributes}
                    onChange={(event) =>
                        setData('attributes', event.target.value)
                    }
                    placeholder='{"nicotine": "50mg", "flavor": "Blue Razz"}'
                    rows={4}
                    className="font-mono text-xs"
                />
                <InputError message={errors.attributes} />
            </div>

            <div className="flex items-center gap-2">
                <Checkbox
                    id="variant-status"
                    checked={data.status}
                    onCheckedChange={(value) =>
                        setData('status', value === true)
                    }
                />
                <Label htmlFor="variant-status" className="font-normal">
                    Active
                </Label>
                <InputError message={errors.status} />
            </div>

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {processing ? <Spinner /> : null}
                    {submitLabel ?? (isEdit ? 'Save variant' : 'Add variant')}
                </Button>
                <Button type="button" variant="secondary" onClick={onCancel}>
                    Cancel
                </Button>
            </div>
        </form>
    );
}
