import { Link, useForm, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Plus, RefreshCw, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useMemo, useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import { ColorPicker } from '@/components/color-picker';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    CATALOG_COLOR_PICKER_MODES,
    colorPickerStyle,
    isTransparentColorPickerValue,
    normalizeCatalogColorPickerValue,
    catalogFormColorPickerValue,
    type ColorPickerValue,
} from '@/lib/color-picker';
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

type ProductSuggestion = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    image_url: string | null;
};

export type ProductSpecificationRow = {
    label: string;
    value: string;
};

export const PRODUCT_TAG_OPTIONS = [
    { value: 'new_arrival', label: 'New arrival' },
    { value: 'best_selling', label: 'Best selling' },
    { value: 'popular', label: 'Popular' },
    { value: 'on_offer', label: 'On offer' },
] as const;

export type ProductTagValue = (typeof PRODUCT_TAG_OPTIONS)[number]['value'];

interface ProductFormDefaults {
    category_ids?: number[];
    name?: string;
    slug?: string;
    sku?: string | null;
    brand?: string | null;
    description?: string | null;
    tags?: ProductTagValue[];
    specifications?: ProductSpecificationRow[];
    image?: string | null;
    image_url?: string | null;
    gallery?: ProductGalleryImage[];
    color_code?: ColorPickerValue | null;
    status?: boolean;
}

function initialSpecifications(
    specifications: ProductSpecificationRow[] | undefined,
): ProductSpecificationRow[] {
    if (specifications && specifications.length > 0) {
        return specifications;
    }

    return [{ label: '', value: '' }];
}

interface ProductFormProps {
    action: FormAction;
    categoryOptions: ProductCategoryOption[];
    defaults?: ProductFormDefaults;
    isEdit?: boolean;
    showBranchNotice?: boolean;
    /** Platform catalog create/edit — match existing products while typing. */
    enableSuggestions?: boolean;
    /** Defaults to platform products suggestions when omitted. */
    suggestionsUrl?: string;
    suggestionShowHref?: (slug: string) => string;
    excludeProductId?: number | null;
    /** Branch create: include initial selling_price / quantity. */
    branchOpsFields?: boolean;
    /** Hide gallery when backend ignores it (branch create). Default true. */
    showGallery?: boolean;
    returnTo?: string | null;
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

function skuFromSlug(slug: string, prefix: string): string {
    const body = slug.trim().replace(/^-+|-+$/g, '').toUpperCase();
    const tag = prefix.trim().toUpperCase() || 'PMM';

    if (body === '') {
        return '';
    }

    return `${tag}-${body}`;
}

export function ProductForm({
    action,
    categoryOptions,
    defaults,
    isEdit = false,
    showBranchNotice = false,
    enableSuggestions = false,
    suggestionsUrl,
    suggestionShowHref,
    excludeProductId = null,
    branchOpsFields = false,
    showGallery = true,
    returnTo = null,
    cancelHref,
    submitLabel,
}: ProductFormProps) {
    const { catalog } = usePage().props;
    const [slugTouched, setSlugTouched] = useState(isEdit);
    const [skuTouched, setSkuTouched] = useState(isEdit);
    const [suggestionQuery, setSuggestionQuery] = useState('');
    const [existingPrimary, setExistingPrimary] = useState<ExistingFile[]>(
        () =>
            defaults?.image && defaults?.image_url
                ? [
                      {
                          id: 'primary',
                          path: defaults.image,
                          url: defaults.image_url,
                          mime_type: 'image/jpeg',
                          name:
                              defaults.image.split('/').pop() ?? 'primary-image',
                      },
                  ]
                : [],
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
    const [suggestions, setSuggestions] = useState<ProductSuggestion[]>([]);
    const [categoryOpen, setCategoryOpen] = useState(false);

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
        category_ids: defaults?.category_ids ?? ([] as number[]),
        name: defaults?.name ?? '',
        slug: defaults?.slug ?? '',
        sku: defaults?.sku ?? '',
        brand: defaults?.brand ?? '',
        description: defaults?.description ?? '',
        tags: defaults?.tags ?? ([] as ProductTagValue[]),
        specifications: initialSpecifications(defaults?.specifications),
        image: null as File | null,
        gallery: [] as File[],
        remove_image: false,
        remove_gallery_ids: [] as number[],
        color_code: catalogFormColorPickerValue(
            defaults?.color_code,
            isEdit,
        ),
        status: defaults?.status ?? true,
        ...(returnTo ? { return_to: returnTo } : {}),
        ...(branchOpsFields
            ? {
                  selling_price: '',
                  quantity: 0,
              }
            : {}),
        ...(isEdit ? { _method: 'put' } : {}),
    });

    const selectedCategories = useMemo(
        () =>
            categoryOptions.filter((option) =>
                data.category_ids.includes(option.id),
            ),
        [categoryOptions, data.category_ids],
    );

    const imageTileStyle = colorPickerStyle(data.color_code);
    const imageTileTransparent = isTransparentColorPickerValue(data.color_code);

    const toggleCategory = (categoryId: number) => {
        const next = data.category_ids.includes(categoryId)
            ? data.category_ids.filter((id) => id !== categoryId)
            : [...data.category_ids, categoryId];

        setData('category_ids', next);
    };

    const toggleTag = (tag: ProductTagValue) => {
        const next = data.tags.includes(tag)
            ? data.tags.filter((value) => value !== tag)
            : [...data.tags, tag];

        setData('tags', next);
    };

    const updateSpecification = (
        index: number,
        field: keyof ProductSpecificationRow,
        value: string,
    ) => {
        const next = data.specifications.map((row, rowIndex) =>
            rowIndex === index ? { ...row, [field]: value } : row,
        );

        setData('specifications', next);
    };

    const addSpecification = () => {
        setData('specifications', [
            ...data.specifications,
            { label: '', value: '' },
        ]);
    };

    const removeSpecification = (index: number) => {
        if (data.specifications.length <= 1) {
            setData('specifications', [{ label: '', value: '' }]);

            return;
        }

        setData(
            'specifications',
            data.specifications.filter((_, rowIndex) => rowIndex !== index),
        );
    };

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

    useEffect(() => {
        if (skuTouched) {
            return;
        }

        const nextSku = skuFromSlug(data.slug, catalog.sku_prefix);

        if (nextSku !== data.sku) {
            setData('sku', nextSku);
        }
    }, [catalog.sku_prefix, data.sku, data.slug, setData, skuTouched]);

    useEffect(() => {
        if (!enableSuggestions) {
            return;
        }

        const query = suggestionQuery.trim();

        if (query.length < 2) {
            setSuggestions([]);

            return;
        }

        const endpoint =
            suggestionsUrl ?? ProductController.suggestions.url();

        const timer = window.setTimeout(() => {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('q', query);

            if (excludeProductId) {
                url.searchParams.set('exclude', String(excludeProductId));
            }

            fetch(url.toString(), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })
                .then((response) => response.json())
                .then((payload: { suggestions: ProductSuggestion[] }) => {
                    setSuggestions(payload.suggestions ?? []);
                })
                .catch(() => setSuggestions([]));
        }, 300);

        return () => window.clearTimeout(timer);
    }, [
        enableSuggestions,
        excludeProductId,
        suggestionQuery,
        suggestionsUrl,
    ]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        transform((form) => {
            const {
                image,
                gallery,
                remove_image,
                remove_gallery_ids,
                color_code,
                brand,
                specifications,
                selling_price,
                quantity,
                ...rest
            } = form as typeof form & {
                selling_price?: string;
                quantity?: number;
            };

            const filteredSpecifications = specifications.filter(
                (row) => row.label.trim() !== '' || row.value.trim() !== '',
            );

            return {
                ...rest,
                brand: brand.trim() === '' ? null : brand,
                specifications: JSON.stringify(filteredSpecifications),
                color_code: JSON.stringify(
                    normalizeCatalogColorPickerValue(color_code),
                ),
                ...(image ? { image } : {}),
                ...(showGallery && gallery.length > 0 ? { gallery } : {}),
                ...(remove_image ? { remove_image: true } : {}),
                ...(showGallery && remove_gallery_ids.length > 0
                    ? { remove_gallery_ids }
                    : {}),
                ...(branchOpsFields
                    ? {
                          ...(selling_price !== undefined &&
                          selling_price !== ''
                              ? { selling_price }
                              : {}),
                          quantity: quantity ?? 0,
                      }
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
                    <AlertTitle>Shared catalog</AlertTitle>
                    <AlertDescription>
                        Active products are available at every branch. Set stock
                        and price from Catalog Manage after creating the listing.
                    </AlertDescription>
                </Alert>
            ) : null}

            <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(17.5rem,20rem)]">
                <section
                    className={cn(
                        'self-start rounded-2xl border border-border/80 bg-card p-5 shadow-sm',
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
                        <div className="flex w-full min-w-0 flex-col gap-2">
                            <Label htmlFor="category_ids">Categories</Label>
                            <Popover
                                open={categoryOpen}
                                onOpenChange={setCategoryOpen}
                            >
                                <PopoverTrigger asChild>
                                    <Button
                                        id="category_ids"
                                        type="button"
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={categoryOpen}
                                        aria-invalid={Boolean(
                                            errors.category_ids ??
                                                errors['category_ids.0'],
                                        )}
                                        className="h-auto min-h-9 w-full min-w-0 justify-between gap-2 px-3 py-1.5 font-normal"
                                    >
                                        {selectedCategories.length === 0 ? (
                                            <span className="text-muted-foreground truncate">
                                                Select categories
                                            </span>
                                        ) : (
                                            <span className="flex min-w-0 flex-1 flex-wrap items-center gap-1.5 text-left">
                                                {selectedCategories.map(
                                                    (category) => (
                                                        <Badge
                                                            key={category.id}
                                                            variant="secondary"
                                                            className="max-w-full font-normal"
                                                        >
                                                            <span className="truncate">
                                                                {category.name}
                                                            </span>
                                                        </Badge>
                                                    ),
                                                )}
                                            </span>
                                        )}
                                        <ChevronsUpDown className="size-4 shrink-0 self-center opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    className="w-[var(--radix-popover-trigger-width)] p-0"
                                    align="start"
                                >
                                    <Command>
                                        <CommandInput placeholder="Search categories…" />
                                        <CommandList>
                                            <CommandEmpty>
                                                No category found.
                                            </CommandEmpty>
                                            <CommandGroup>
                                                {categoryOptions.map(
                                                    (option) => {
                                                        const selected =
                                                            data.category_ids.includes(
                                                                option.id,
                                                            );

                                                        return (
                                                            <CommandItem
                                                                key={option.id}
                                                                value={
                                                                    option.name
                                                                }
                                                                onSelect={() =>
                                                                    toggleCategory(
                                                                        option.id,
                                                                    )
                                                                }
                                                            >
                                                                <Check
                                                                    className={cn(
                                                                        'size-4',
                                                                        selected
                                                                            ? 'opacity-100'
                                                                            : 'opacity-0',
                                                                    )}
                                                                />
                                                                {option.name}
                                                            </CommandItem>
                                                        );
                                                    },
                                                )}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <InputError
                                message={
                                    errors.category_ids ??
                                    errors['category_ids.0']
                                }
                            />
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) => {
                                    setSuggestionQuery(event.target.value);
                                    setData('name', event.target.value);
                                }}
                                onBlur={() => {
                                    if (data.slug.trim() !== '') {
                                        validate('slug');
                                    }
                                }}
                                required
                                placeholder="e.g. Air Pro Kit"
                                autoComplete="off"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <div className="flex items-center justify-between gap-2">
                                    <Label htmlFor="slug">Slug</Label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="xs"
                                        className="text-muted-foreground hover:text-foreground"
                                        disabled={data.name.trim() === ''}
                                        onClick={() => {
                                            const nextSlug = slugify(data.name);
                                            setSlugTouched(true);
                                            setSuggestionQuery(nextSlug);
                                            setData('slug', nextSlug);

                                            if (nextSlug !== '') {
                                                validate('slug');
                                            }
                                        }}
                                    >
                                        <RefreshCw className="size-3" />
                                        Generate
                                    </Button>
                                </div>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={(event) => {
                                        setSlugTouched(true);
                                        setSuggestionQuery(event.target.value);
                                        setData('slug', event.target.value);
                                    }}
                                    onBlur={() => {
                                        if (data.slug.trim() !== '') {
                                            validate('slug');
                                        }
                                    }}
                                    required
                                    placeholder="e.g. air-pro-kit"
                                />
                                <InputError message={errors.slug} />
                            </div>

                            <div className="flex flex-col gap-2">
                                <div className="flex items-center justify-between gap-2">
                                    <Label htmlFor="sku">SKU</Label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="xs"
                                        className="text-muted-foreground hover:text-foreground"
                                        disabled={data.slug.trim() === ''}
                                        onClick={() => {
                                            const nextSku = skuFromSlug(
                                                data.slug,
                                                catalog.sku_prefix,
                                            );
                                            setSkuTouched(true);
                                            setData('sku', nextSku);

                                            if (nextSku !== '') {
                                                validate('sku');
                                            }
                                        }}
                                    >
                                        <RefreshCw className="size-3" />
                                        Generate
                                    </Button>
                                </div>
                                <Input
                                    id="sku"
                                    value={data.sku}
                                    onChange={(event) => {
                                        setSkuTouched(true);
                                        setData('sku', event.target.value);
                                    }}
                                    onBlur={() => {
                                        if (data.sku.trim() !== '') {
                                            validate('sku');
                                        }
                                    }}
                                    placeholder={`e.g. ${catalog.sku_prefix}-AIR-PRO`}
                                />
                                <InputError message={errors.sku} />
                            </div>
                        </div>

                        {enableSuggestions && suggestions.length > 0 ? (
                            <div className="space-y-2 rounded-xl border border-amber-500/30 bg-amber-500/5 p-3">
                                <p className="text-sm font-medium">
                                    Similar products already exist
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    Review these before creating a duplicate.
                                </p>
                                <ul className="space-y-2">
                                    {suggestions.map((suggestion) => (
                                        <li
                                            key={suggestion.id}
                                            className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border/70 bg-background px-3 py-2"
                                        >
                                            <div className="flex min-w-0 items-center gap-3">
                                                {suggestion.image_url ? (
                                                    <img
                                                        src={
                                                            suggestion.image_url
                                                        }
                                                        alt=""
                                                        className="size-9 shrink-0 rounded-md object-cover"
                                                    />
                                                ) : (
                                                    <div className="bg-muted size-9 shrink-0 rounded-md" />
                                                )}
                                                <div className="min-w-0">
                                                    <div className="truncate font-medium">
                                                        {suggestion.name}
                                                    </div>
                                                    <div className="text-muted-foreground truncate text-xs">
                                                        {suggestion.sku ??
                                                            suggestion.slug}
                                                    </div>
                                                </div>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={
                                                        suggestionShowHref
                                                            ? suggestionShowHref(
                                                                  suggestion.slug,
                                                              )
                                                            : ProductController.show.url(
                                                                  suggestion.slug,
                                                              )
                                                    }
                                                >
                                                    View
                                                </Link>
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ) : null}

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="brand">Brand</Label>
                            <Input
                                id="brand"
                                value={data.brand}
                                onChange={(event) =>
                                    setData('brand', event.target.value)
                                }
                                placeholder="e.g. PuffMeMate"
                            />
                            <InputError message={errors.brand} />
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
                                rows={4}
                                className="min-h-28 resize-y"
                            />
                            <InputError message={errors.description} />
                        </div>

                        {branchOpsFields ? (
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="selling_price">
                                        Selling price
                                    </Label>
                                    <Input
                                        id="selling_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={
                                            (
                                                data as typeof data & {
                                                    selling_price?: string;
                                                }
                                            ).selling_price ?? ''
                                        }
                                        onChange={(event) =>
                                            setData(
                                                'selling_price' as never,
                                                event.target.value as never,
                                            )
                                        }
                                        placeholder="Optional"
                                    />
                                    <InputError
                                        message={errors.selling_price}
                                    />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="quantity">
                                        Initial quantity
                                    </Label>
                                    <Input
                                        id="quantity"
                                        type="number"
                                        value={
                                            (
                                                data as typeof data & {
                                                    quantity?: number;
                                                }
                                            ).quantity ?? 0
                                        }
                                        onChange={(event) =>
                                            setData(
                                                'quantity' as never,
                                                Number(
                                                    event.target.value,
                                                ) as never,
                                            )
                                        }
                                    />
                                    <InputError message={errors.quantity} />
                                </div>
                            </div>
                        ) : null}
                    </div>
                </section>

                <aside className="flex min-w-0 flex-col xl:row-span-2">
                    <section
                        className={cn(
                            'rounded-2xl border border-border/80 bg-card p-4 shadow-sm',
                            'sm:p-5',
                        )}
                    >
                        <div className="mb-3 space-y-0.5">
                            <h2 className="text-base font-semibold tracking-tight">
                                Media
                            </h2>
                            <p className="text-muted-foreground text-xs">
                                Listing photo, background, and gallery thumbs
                            </p>
                        </div>

                    <div className="space-y-4">
                        <div className="overflow-hidden rounded-xl border border-border/80">
                            <div
                                className="aspect-square p-3"
                                style={
                                    imageTileTransparent
                                        ? undefined
                                        : imageTileStyle
                                }
                            >
                                <FileUpload
                                    value={data.image}
                                    onChange={(file) => {
                                        const next = Array.isArray(file)
                                            ? (file[0] ?? null)
                                            : file;
                                        setExistingPrimary([]);
                                        setData((current) => ({
                                            ...current,
                                            image: next,
                                            remove_image: next
                                                ? false
                                                : current.remove_image,
                                        }));
                                    }}
                                    existingFiles={
                                        data.image || data.remove_image
                                            ? []
                                            : existingPrimary
                                    }
                                    onRemoveExisting={() => {
                                        setExistingPrimary([]);
                                        setData((current) => ({
                                            ...current,
                                            image: null,
                                            remove_image: true,
                                        }));
                                    }}
                                    accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,.png,.jpg,.jpeg,.webp,.svg"
                                    maxSize={2}
                                    lightbox
                                    isUploading={processing}
                                    uploadProgress={
                                        progress?.percentage ?? null
                                    }
                                    onCancel={
                                        processing ? cancel : undefined
                                    }
                                    placeholder="Drop or click"
                                    hint="PNG, JPG, SVG, or WebP · Max 2MB"
                                    compactCards
                                    classNames={{
                                        wrapper:
                                            'flex h-full min-h-0 w-full flex-col',
                                        dropzone:
                                            'h-full min-h-0 border-dashed border-border/60 bg-transparent px-3 py-6',
                                        dropzoneContent: 'gap-1',
                                        card: 'h-full overflow-hidden border-0 bg-transparent shadow-none hover:shadow-none',
                                        cardMedia: 'h-full bg-transparent',
                                        cardImage:
                                            '!h-auto !w-auto max-h-full max-w-full object-contain object-center',
                                    }}
                                    error={errors.image}
                                />
                            </div>

                            <div className="border-border/60 bg-card flex flex-wrap items-center justify-between gap-2 border-t px-3 py-2">
                                <p className="text-sm font-medium">
                                    Background
                                </p>
                                <ColorPicker
                                    mode="dialog"
                                    triggerVariant="button"
                                    enabledModes={[
                                        ...CATALOG_COLOR_PICKER_MODES,
                                    ]}
                                    defaultMode="transparent"
                                    value={data.color_code}
                                    onChange={(color_code) =>
                                        setData('color_code', color_code)
                                    }
                                    label="Image background"
                                    description="Fill behind product imagery"
                                    dialogTitle="Image background"
                                    dialogDescription="None is the default. The listing uses the page or card color until you pick a solid or gradient fill."
                                    error={
                                        errors.color_code ??
                                        errors['color_code.mode'] ??
                                        errors['color_code.color'] ??
                                        errors['color_code.from'] ??
                                        errors['color_code.to']
                                    }
                                />
                            </div>
                        </div>

                        {showGallery ? (
                            <div className="space-y-2">
                                <div className="flex items-center justify-between gap-3">
                                    <Label>Gallery</Label>
                                    <span className="text-muted-foreground text-xs">
                                        {existingGallery.length +
                                            data.gallery.length}
                                        /12
                                    </span>
                                </div>
                                <FileUpload
                                    multiple
                                    dropzoneVisibility="empty"
                                    compactCards
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
                                            const next = current.includes(
                                                numericId,
                                            )
                                                ? current
                                                : [...current, numericId];
                                            setData(
                                                'remove_gallery_ids',
                                                next,
                                            );
                                            return next;
                                        });
                                    }}
                                    accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,.png,.jpg,.jpeg,.webp,.svg"
                                    maxSize={2}
                                    maxFiles={12}
                                    lightbox
                                    isUploading={processing}
                                    uploadProgress={
                                        progress?.percentage ?? null
                                    }
                                    onCancel={
                                        processing ? cancel : undefined
                                    }
                                    placeholder="Add gallery images"
                                    hint="Up to 12 · PNG, JPG, SVG, or WebP"
                                    classNames={{
                                        dropzone: 'py-5',
                                        grid: 'grid-cols-3 gap-2 sm:grid-cols-3 lg:grid-cols-3',
                                        card: 'aspect-square overflow-hidden',
                                        cardMedia: 'h-full bg-muted/20',
                                        cardImage: 'object-contain p-1',
                                    }}
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
                        ) : null}
                    </div>
                    </section>
                </aside>

            <section
                className={cn(
                    'rounded-2xl border border-border/80 bg-card p-5 shadow-sm',
                    'sm:p-6',
                )}
            >
                <div className="mb-5 space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Storefront merchandising
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Tags drive home collections; specifications appear on
                        the product page table.
                    </p>
                </div>

                <div className="flex flex-col gap-8">
                    <div className="grid items-start gap-6 lg:grid-cols-2 lg:gap-8">
                        <div className="flex flex-col gap-3">
                            <div className="space-y-1">
                                <Label>Tags</Label>
                                <p className="text-muted-foreground text-xs">
                                    Highlight this product in storefront
                                    collections.
                                </p>
                            </div>
                            <div className="grid gap-2 sm:grid-cols-2">
                                {PRODUCT_TAG_OPTIONS.map((option) => {
                                    const checked = data.tags.includes(
                                        option.value,
                                    );

                                    return (
                                        <label
                                            key={option.value}
                                            htmlFor={`tag-${option.value}`}
                                            className={cn(
                                                'inline-flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2.5 text-sm transition-colors',
                                                checked
                                                    ? 'border-primary/40 bg-primary/5'
                                                    : 'border-border/70 bg-muted/20 hover:bg-muted/40',
                                            )}
                                        >
                                            <Checkbox
                                                id={`tag-${option.value}`}
                                                checked={checked}
                                                onCheckedChange={() =>
                                                    toggleTag(option.value)
                                                }
                                            />
                                            <span className="font-normal">
                                                {option.label}
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                            <InputError message={errors.tags} />
                        </div>

                        <div className="space-y-3">
                            <div className="space-y-1">
                                <Label>Status</Label>
                                <p className="text-muted-foreground text-xs">
                                    Inactive products stay hidden from browsing.
                                </p>
                            </div>
                            <div
                                className="grid gap-3 sm:grid-cols-2"
                                role="radiogroup"
                                aria-label="Status"
                            >
                                <button
                                    type="button"
                                    role="radio"
                                    aria-checked={data.status}
                                    className={cn(
                                        'flex flex-col gap-1 rounded-xl border px-4 py-3 text-left transition-colors',
                                        data.status
                                            ? 'border-primary/40 bg-primary/5'
                                            : 'border-border/70 bg-muted/20 hover:bg-muted/40',
                                    )}
                                    onClick={() => setData('status', true)}
                                >
                                    <span className="text-sm font-medium">
                                        Active
                                    </span>
                                    <span className="text-muted-foreground text-xs">
                                        Visible in the catalog and storefront.
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    role="radio"
                                    aria-checked={!data.status}
                                    className={cn(
                                        'flex flex-col gap-1 rounded-xl border px-4 py-3 text-left transition-colors',
                                        !data.status
                                            ? 'border-primary/40 bg-primary/5'
                                            : 'border-border/70 bg-muted/20 hover:bg-muted/40',
                                    )}
                                    onClick={() => setData('status', false)}
                                >
                                    <span className="text-sm font-medium">
                                        Inactive
                                    </span>
                                    <span className="text-muted-foreground text-xs">
                                        Hidden from storefront browsing.
                                    </span>
                                </button>
                            </div>
                            <InputError message={errors.status} />
                        </div>
                    </div>

                    <div className="flex flex-col gap-2">
                        <div className="flex flex-wrap items-start justify-between gap-2">
                            <div className="space-y-1">
                                <Label>Specifications</Label>
                                <p className="text-muted-foreground text-xs">
                                    Label and value pairs for the product page.
                                    Brand is added automatically from the brand
                                    field on the storefront.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addSpecification}
                            >
                                <Plus className="size-4" />
                                Add row
                            </Button>
                        </div>
                        <div className="space-y-2">
                            <div className="grid grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)_auto] items-center gap-3 px-1">
                                <span className="text-muted-foreground text-xs font-medium">
                                    Label
                                </span>
                                <span className="text-muted-foreground text-xs font-medium">
                                    Value
                                </span>
                                <span className="size-9" aria-hidden />
                            </div>
                            <div className="max-h-80 space-y-3 overflow-y-auto p-1">
                                {data.specifications.map((row, index) => (
                                    <div
                                        key={`specification-${index}`}
                                        className="grid grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)_auto] items-start gap-3"
                                    >
                                        <div className="flex flex-col gap-1">
                                            <Label
                                                htmlFor={`specification-label-${index}`}
                                                className="sr-only"
                                            >
                                                Label
                                            </Label>
                                            <Input
                                                id={`specification-label-${index}`}
                                                value={row.label}
                                                onChange={(event) =>
                                                    updateSpecification(
                                                        index,
                                                        'label',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="e.g. Pack Format"
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `specifications.${index}.label`
                                                    ]
                                                }
                                            />
                                        </div>
                                        <div className="flex flex-col gap-1">
                                            <Label
                                                htmlFor={`specification-value-${index}`}
                                                className="sr-only"
                                            >
                                                Value
                                            </Label>
                                            <Input
                                                id={`specification-value-${index}`}
                                                value={row.value}
                                                onChange={(event) =>
                                                    updateSpecification(
                                                        index,
                                                        'value',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="e.g. Soft Pack"
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `specifications.${index}.value`
                                                    ]
                                                }
                                            />
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            className="size-9 shrink-0"
                                            onClick={() =>
                                                removeSpecification(index)
                                            }
                                            aria-label="Remove specification row"
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <InputError message={errors.specifications} />
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
