import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import { FileUploadAvatar } from '@/components/file-upload-avatar';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
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
import { cn } from '@/lib/utils';

type FormAction = {
    action: string;
    method?: string;
};

export type ProductCategoryOption = {
    id: number;
    name: string;
};

export type ProductGalleryImage = {
    id: number;
    path: string;
    url: string;
    sort_order: number;
    mime_type?: string;
};

interface ProductFormDefaults {
    category_id?: number;
    name?: string;
    slug?: string;
    sku?: string | null;
    description?: string | null;
    image?: string | null;
    image_url?: string | null;
    gallery?: ProductGalleryImage[];
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
    const [existingGallery, setExistingGallery] = useState<ExistingFile[]>(
        () =>
            (defaults?.gallery ?? []).map((image) => ({
                id: image.id,
                path: image.path,
                url: image.url,
                mime_type: image.mime_type ?? 'image/jpeg',
                name: image.path.split('/').pop() ?? `image-${image.id}`,
            })),
    );
    const [removeGalleryIds, setRemoveGalleryIds] = useState<number[]>([]);

    const formMethod = (
        action.method ?? 'post'
    ).toLowerCase() as 'get' | 'post' | 'put' | 'patch' | 'delete';

    const {
        data,
        setData,
        post,
        processing,
        errors,
        progress,
        transform,
        validate,
        setValidationTimeout,
        cancel,
    } = useForm(formMethod, action.action, {
        category_id: defaults?.category_id ?? categoryOptions[0]?.id ?? '',
        name: defaults?.name ?? '',
        slug: defaults?.slug ?? '',
        sku: defaults?.sku ?? '',
        description: defaults?.description ?? '',
        image: null as File | null,
        gallery: [] as File[],
        remove_image: false,
        remove_gallery_ids: [] as number[],
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
            const {
                image,
                gallery,
                remove_image,
                remove_gallery_ids,
                ...rest
            } = form;

            return {
                ...rest,
                ...(image ? { image } : {}),
                ...(gallery.length > 0 ? { gallery } : {}),
                ...(remove_image ? { remove_image: true } : {}),
                ...(remove_gallery_ids.length > 0
                    ? { remove_gallery_ids }
                    : {}),
            };
        });

        post(action.action, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            {showBranchNotice ? (
                <Alert className="border-primary/20 bg-primary/5">
                    <AlertTitle>Branch availability</AlertTitle>
                    <AlertDescription>
                        Disabled for all branches — enable from branch
                        inventory after creating the product.
                    </AlertDescription>
                </Alert>
            ) : null}

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
                <section
                    className={cn(
                        'rounded-2xl border border-border/80 bg-card p-5 shadow-sm',
                        'sm:p-6',
                    )}
                >
                    <div className="mb-5 space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            Media
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Set a primary image for listings, then add gallery
                            shots for the product page.
                        </p>
                    </div>

                    <div className="space-y-8">
                        <div className="space-y-3">
                            <div className="flex items-center justify-between gap-3">
                                <Label>Primary image</Label>
                                <span className="text-muted-foreground text-xs">
                                    Shown on product cards
                                </span>
                            </div>
                            <div className="rounded-xl border border-dashed border-border/80 bg-muted/20 p-4">
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
                                    placeholder="Primary image"
                                    avatarSize={144}
                                />
                            </div>
                            <InputError message={errors.image} />
                        </div>

                        <div className="space-y-3">
                            <div className="flex items-center justify-between gap-3">
                                <Label>Image gallery</Label>
                                <span className="text-muted-foreground text-xs">
                                    Up to 12 images
                                </span>
                            </div>
                            <FileUpload
                                multiple
                                value={data.gallery}
                                onChange={(files) => {
                                    const next = Array.isArray(files)
                                        ? files
                                        : files
                                          ? [files]
                                          : [];
                                    setData('gallery', next);
                                }}
                                existingFiles={existingGallery}
                                onRemoveExisting={(id) => {
                                    const numericId = Number(id);
                                    setExistingGallery((current) =>
                                        current.filter(
                                            (file) => file.id !== id,
                                        ),
                                    );
                                    setRemoveGalleryIds((current) => {
                                        const next = current.includes(numericId)
                                            ? current
                                            : [...current, numericId];
                                        setData('remove_gallery_ids', next);
                                        return next;
                                    });
                                }}
                                accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp"
                                maxSize={2}
                                maxFiles={12}
                                lightbox
                                isUploading={processing}
                                uploadProgress={progress?.percentage ?? null}
                                onCancel={processing ? cancel : undefined}
                                placeholder="Drop gallery images here"
                                hint="Additional product photos for the detail page"
                                error={
                                    errors.gallery ??
                                    errors['gallery.0'] ??
                                    errors['gallery.1']
                                }
                            />
                            <InputError
                                message={
                                    errors.gallery ??
                                    errors['gallery.0'] ??
                                    errors.remove_gallery_ids
                                }
                            />
                            {removeGalleryIds.length > 0 ? (
                                <p className="text-muted-foreground text-xs">
                                    {removeGalleryIds.length} gallery image
                                    {removeGalleryIds.length === 1 ? '' : 's'}{' '}
                                    marked for removal on save.
                                </p>
                            ) : null}
                        </div>
                    </div>
                </section>

                <section
                    className={cn(
                        'rounded-2xl border border-border/80 bg-card p-5 shadow-sm',
                        'sm:p-6',
                    )}
                >
                    <div className="mb-5 space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            {isEdit ? 'Product details' : 'Product information'}
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Catalog metadata shared across every branch.
                        </p>
                    </div>

                    <div className="space-y-5">
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
                                    placeholder="e.g. Air Pro Kit"
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
                                    placeholder="e.g. air-pro-kit"
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
                                placeholder="e.g. POM-AIR-PRO"
                            />
                            <InputError message={errors.sku} />
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(event) =>
                                    setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                                placeholder="Tell customers what makes this product stand out"
                                rows={7}
                                className="min-h-40 resize-y"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="flex items-center gap-2 rounded-xl border border-border/70 bg-muted/20 px-4 py-3">
                            <Checkbox
                                id="status"
                                checked={data.status}
                                onCheckedChange={(value) =>
                                    setData('status', value === true)
                                }
                            />
                            <div className="space-y-0.5">
                                <Label
                                    htmlFor="status"
                                    className="font-medium"
                                >
                                    Active in catalog
                                </Label>
                                <p className="text-muted-foreground text-xs">
                                    Inactive products stay hidden from browsing.
                                </p>
                            </div>
                            <InputError message={errors.status} />
                        </div>
                    </div>
                </section>
            </div>

            <div className="sticky bottom-0 z-10 -mx-2 flex flex-wrap items-center justify-end gap-3 border-t bg-background/95 px-2 py-4 backdrop-blur supports-[backdrop-filter]:bg-background/80">
                <Button variant="secondary" asChild>
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? <Spinner /> : null}
                    {submitLabel ?? (isEdit ? 'Save changes' : 'Create product')}
                </Button>
            </div>
        </form>
    );
}
