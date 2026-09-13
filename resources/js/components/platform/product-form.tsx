import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { FileUploadAvatar } from '@/components/file-upload-avatar';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type FormAction = {
    action: string;
    method?: string;
};

export type ProductCategoryOption = {
    id: number;
    name: string;
};

interface ProductFormDefaults {
    category_id?: number;
    name?: string;
    slug?: string;
    sku?: string | null;
    description?: string | null;
    image?: string | null;
    image_url?: string | null;
    status?: boolean;
}

interface ProductFormProps {
    action: FormAction;
    categoryOptions: ProductCategoryOption[];
    defaults?: ProductFormDefaults;
    isEdit?: boolean;
    showBranchNotice?: boolean;
    cancelHref: string;
    submitLabel?: string;
}

function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export function ProductForm({
    action,
    categoryOptions,
    defaults,
    isEdit = false,
    showBranchNotice = false,
    cancelHref,
    submitLabel,
}: ProductFormProps) {
    const [slugTouched, setSlugTouched] = useState(isEdit);
    const [existingImageUrl, setExistingImageUrl] = useState<string | null>(
        defaults?.image_url ?? null,
    );

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
        category_id: defaults?.category_id ?? categoryOptions[0]?.id ?? '',
        name: defaults?.name ?? '',
        slug: defaults?.slug ?? '',
        sku: defaults?.sku ?? '',
        description: defaults?.description ?? '',
        image: null as File | null,
        remove_image: false,
        status: defaults?.status ?? true,
        ...(isEdit ? { _method: 'put' } : {}),
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    useEffect(() => {
        if (!slugTouched) {
            const nextSlug = slugify(data.name);
            setData('slug', nextSlug);

            if (nextSlug !== '') {
                validate('slug');
            }
        }
    }, [data.name, slugTouched, setData, validate]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        transform((form) => {
            const { image, remove_image, ...rest } = form;

            return {
                ...rest,
                ...(image ? { image } : {}),
                ...(remove_image ? { remove_image: true } : {}),
            };
        });

        post(action.action, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={submit}>
            <Card>
                <CardHeader>
                    <CardTitle>
                        {isEdit ? 'Product details' : 'Product information'}
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex flex-col gap-6">
                    {showBranchNotice ? (
                        <Alert>
                            <AlertTitle>Branch availability</AlertTitle>
                            <AlertDescription>
                                Disabled for all branches — enable from branch
                                inventory.
                            </AlertDescription>
                        </Alert>
                    ) : null}

                    <div className="flex flex-col gap-2">
                        <Label>Product image</Label>
                        <FileUploadAvatar
                            value={data.image}
                            onChange={(file) => {
                                const next = Array.isArray(file)
                                    ? (file[0] ?? null)
                                    : file;
                                setData((current) => ({
                                    ...current,
                                    image: next,
                                    remove_image: next
                                        ? false
                                        : current.remove_image,
                                }));
                            }}
                            existingUrl={
                                data.image || data.remove_image
                                    ? null
                                    : existingImageUrl
                            }
                            onClearExisting={() => {
                                setExistingImageUrl(null);
                                setData((current) => ({
                                    ...current,
                                    image: null,
                                    remove_image: true,
                                }));
                            }}
                            accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp"
                            maxSize={2}
                            error={errors.image}
                            hint="PNG, JPG, or WebP up to 2MB"
                            placeholder="Product image"
                        />
                        <InputError message={errors.image} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="category_id">Category</Label>
                        <Select
                            value={
                                data.category_id === ''
                                    ? undefined
                                    : String(data.category_id)
                            }
                            onValueChange={(value) =>
                                setData('category_id', Number(value))
                            }
                        >
                            <SelectTrigger id="category_id">
                                <SelectValue placeholder="Select category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    {categoryOptions.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={String(option.id)}
                                        >
                                            {option.name}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.category_id} />
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                required
                                placeholder="e.g. Mango Ice 5000"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="slug">Slug</Label>
                            <Input
                                id="slug"
                                value={data.slug}
                                onChange={(event) => {
                                    setSlugTouched(true);
                                    setData('slug', event.target.value);
                                }}
                                onBlur={() => validate('slug')}
                                required
                                placeholder="e.g. mango-ice-5000"
                            />
                            <InputError message={errors.slug} />
                        </div>
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="sku">SKU</Label>
                        <Input
                            id="sku"
                            value={data.sku}
                            onChange={(event) =>
                                setData('sku', event.target.value)
                            }
                            onBlur={() => {
                                if (data.sku.trim() !== '') {
                                    validate('sku');
                                }
                            }}
                            placeholder="e.g. MNG-ICE-5000"
                        />
                        <InputError message={errors.sku} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                            placeholder="Product description"
                            rows={4}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="status"
                            checked={data.status}
                            onCheckedChange={(value) =>
                                setData('status', value === true)
                            }
                        />
                        <Label htmlFor="status" className="font-normal">
                            Active
                        </Label>
                        <InputError message={errors.status} />
                    </div>
                </CardContent>
                <CardFooter className="flex items-center gap-3 border-t pt-6">
                    <Button type="submit" disabled={processing}>
                        {processing ? <Spinner /> : null}
                        {submitLabel ?? (isEdit ? 'Save changes' : 'Create')}
                    </Button>
                    <Button variant="secondary" asChild>
                        <Link href={cancelHref}>Cancel</Link>
                    </Button>
                </CardFooter>
            </Card>
        </form>
    );
}
