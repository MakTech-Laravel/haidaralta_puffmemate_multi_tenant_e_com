import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { FileUploadAvatar } from '@/components/file-upload-avatar';
import InputError from '@/components/input-error';
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

type FormAction = {
    action: string;
    method?: string;
};

export type CategoryOption = {
    id: number;
    name: string;
};

interface CategoryFormDefaults {
    name?: string;
    slug?: string;
    parent_id?: number | null;
    image?: string | null;
    image_url?: string | null;
    status?: boolean;
}

interface CategoryFormProps {
    action: FormAction;
    parentOptions: CategoryOption[];
    defaults?: CategoryFormDefaults;
    isEdit?: boolean;
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

export function CategoryForm({
    action,
    parentOptions,
    defaults,
    isEdit = false,
    cancelHref,
    submitLabel,
}: CategoryFormProps) {
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
        name: defaults?.name ?? '',
        slug: defaults?.slug ?? '',
        parent_id: defaults?.parent_id ?? null,
        image: null as File | null,
        remove_image: false,
        status: defaults?.status ?? true,
        ...(isEdit ? { _method: 'put' } : {}),
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    useEffect(() => {
        if (slugTouched) {
            return;
        }

        const nextSlug = slugify(data.name);

        if (nextSlug !== data.slug) {
            setData('slug', nextSlug);
        }
    }, [data.name, data.slug, slugTouched, setData]);

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
                        {isEdit ? 'Category details' : 'Category information'}
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex flex-col gap-6">
                    <div className="flex flex-col gap-2">
                        <Label>Category image</Label>
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
                            placeholder="Category image"
                        />
                        <InputError message={errors.image} />
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
                                placeholder="e.g. Disposables"
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
                                placeholder="e.g. disposables"
                            />
                            <InputError message={errors.slug} />
                        </div>
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="parent_id">Parent category</Label>
                        <Select
                            value={
                                data.parent_id === null
                                    ? 'none'
                                    : String(data.parent_id)
                            }
                            onValueChange={(value) =>
                                setData(
                                    'parent_id',
                                    value === 'none' ? null : Number(value),
                                )
                            }
                        >
                            <SelectTrigger id="parent_id">
                                <SelectValue placeholder="No parent" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="none">
                                        No parent
                                    </SelectItem>
                                    {parentOptions.map((option) => (
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
                        <InputError message={errors.parent_id} />
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
