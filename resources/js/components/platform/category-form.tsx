import { Link, useForm } from '@inertiajs/react';
import { Check, ChevronsUpDown } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useMemo, useState } from 'react';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import { ColorPicker } from '@/components/color-picker';
import { COLOR_PICKER_CHECKERBOARD } from '@/components/color-picker/color-swatch-popover';
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
import {
    CATALOG_COLOR_PICKER_MODES,
    catalogFormColorPickerValue,
    colorPickerStyle,
    isTransparentColorPickerValue,
    normalizeCatalogColorPickerValue,
    type ColorPickerValue,
} from '@/lib/color-picker';
import { cn } from '@/lib/utils';

type FormAction = {
    action: string;
    method?: string;
};

export type CategoryOption = {
    id: number;
    name: string;
};

type CategorySuggestion = {
    id: number;
    name: string;
    slug: string;
    image_url: string | null;
};

interface CategoryFormDefaults {
    name?: string;
    slug?: string;
    parent_id?: number | null;
    image?: string | null;
    image_url?: string | null;
    color_code?: ColorPickerValue | null;
    status?: boolean;
    featured?: boolean;
    slogan?: string | null;
    show_in_nav?: boolean;
    nav_sort?: number | null;
}

export type CategoryLimits = {
    featured_max: number;
    nav_max: number;
    featured_count: number;
    nav_count: number;
};

interface CategoryFormProps {
    action: FormAction;
    parentOptions: CategoryOption[];
    defaults?: CategoryFormDefaults;
    isEdit?: boolean;
    cancelHref: string;
    submitLabel?: string;
    limits?: CategoryLimits;
    /** When set with suggestionsUrl, fetch similar categories while typing the name. */
    enableSuggestions?: boolean;
    suggestionsUrl?: string;
    suggestionShowHref?: (slug: string) => string;
    excludeCategoryId?: number;
}

const CATALOG_IMAGE_ACCEPT =
    'image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,.png,.jpg,.jpeg,.webp,.svg';

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
    limits,
    enableSuggestions = false,
    suggestionsUrl,
    suggestionShowHref,
    excludeCategoryId,
}: CategoryFormProps) {
    const [slugTouched, setSlugTouched] = useState(isEdit);
    const [parentOpen, setParentOpen] = useState(false);
    const [suggestions, setSuggestions] = useState<CategorySuggestion[]>([]);
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
                              defaults.image.split('/').pop() ?? 'category-image',
                      },
                  ]
                : [],
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
        progress,
        transform,
        validate,
        setValidationTimeout,
        cancel,
    } = useForm(formMethod, action.action, {
        name: defaults?.name ?? '',
        slug: defaults?.slug ?? '',
        parent_id: defaults?.parent_id ?? null,
        image: null as File | null,
        remove_image: false,
        color_code: catalogFormColorPickerValue(
            defaults?.color_code,
            isEdit,
        ),
        status: defaults?.status ?? true,
        featured: defaults?.featured ?? false,
        slogan: defaults?.slogan ?? '',
        show_in_nav: defaults?.show_in_nav ?? false,
        nav_sort: defaults?.nav_sort ?? null,
        ...(isEdit ? { _method: 'put' } : {}),
    });

    const imageTileStyle = colorPickerStyle(data.color_code);
    const imageTileTransparent = isTransparentColorPickerValue(data.color_code);
    const featuredAtLimit =
        limits !== undefined &&
        limits.featured_count >= limits.featured_max &&
        !defaults?.featured;
    const featuredRemaining =
        limits !== undefined
            ? Math.max(0, limits.featured_max - limits.featured_count)
            : null;
    const navRemaining =
        limits !== undefined
            ? Math.max(0, limits.nav_max - limits.nav_count)
            : null;
    const selectedParent = useMemo(
        () =>
            data.parent_id === null
                ? null
                : (parentOptions.find((option) => option.id === data.parent_id) ??
                  null),
        [data.parent_id, parentOptions],
    );

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
        if (!enableSuggestions || !suggestionsUrl) {
            return;
        }

        const query = data.name.trim();

        if (query.length < 2) {
            setSuggestions([]);

            return;
        }

        const timer = window.setTimeout(() => {
            const url = new URL(suggestionsUrl, window.location.origin);
            url.searchParams.set('q', query);

            if (excludeCategoryId) {
                url.searchParams.set('exclude', String(excludeCategoryId));
            }

            fetch(url.toString(), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })
                .then((response) => response.json())
                .then((payload: { suggestions: CategorySuggestion[] }) => {
                    setSuggestions(payload.suggestions ?? []);
                })
                .catch(() => setSuggestions([]));
        }, 300);

        return () => window.clearTimeout(timer);
    }, [
        data.name,
        enableSuggestions,
        excludeCategoryId,
        suggestionsUrl,
    ]);

    const validateSlug = () => {
        if (data.slug.trim() === '') {
            return;
        }

        validate('slug');
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        transform((form) => {
            const { image, remove_image, color_code, ...rest } = form;

            return {
                ...rest,
                color_code: JSON.stringify(
                    normalizeCatalogColorPickerValue(color_code),
                ),
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
        <form onSubmit={submit} className="space-y-6">
            <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(17.5rem,20rem)]">
                <section
                    className={cn(
                        'self-start rounded-2xl border border-border/80 bg-card p-5 shadow-sm',
                        'sm:p-6',
                    )}
                >
                    <div className="mb-5 space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            {isEdit
                                ? 'Category details'
                                : 'Category information'}
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Catalog metadata shared across every branch.
                        </p>
                    </div>

                    <div className="flex flex-col gap-5">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                onBlur={validateSlug}
                                required
                                placeholder="e.g. Disposables"
                                autoComplete="off"
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
                                onBlur={validateSlug}
                                required
                                placeholder="e.g. disposables"
                            />
                            <InputError message={errors.slug} />
                        </div>

                        {enableSuggestions && suggestions.length > 0 ? (
                            <div className="space-y-2 rounded-xl border border-amber-500/30 bg-amber-500/5 p-3">
                                <p className="text-sm font-medium">
                                    Similar categories already exist
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
                                                        {suggestion.slug}
                                                    </div>
                                                </div>
                                            </div>
                                            {suggestionShowHref ? (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={suggestionShowHref(
                                                            suggestion.slug,
                                                        )}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                            ) : null}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ) : null}

                        <div className="flex w-full min-w-0 flex-col gap-2">
                            <Label htmlFor="parent_id">Parent category</Label>
                            <Popover
                                open={parentOpen}
                                onOpenChange={setParentOpen}
                            >
                                <PopoverTrigger asChild>
                                    <Button
                                        id="parent_id"
                                        type="button"
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={parentOpen}
                                        aria-invalid={Boolean(errors.parent_id)}
                                        className="h-9 w-full min-w-0 justify-between gap-2 px-3 font-normal"
                                    >
                                        <span
                                            className={cn(
                                                'truncate text-left',
                                                !selectedParent &&
                                                    'text-muted-foreground',
                                            )}
                                        >
                                            {selectedParent?.name ?? 'No parent'}
                                        </span>
                                        <ChevronsUpDown className="size-4 shrink-0 opacity-50" />
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
                                                <CommandItem
                                                    value="No parent"
                                                    onSelect={() => {
                                                        setData(
                                                            'parent_id',
                                                            null,
                                                        );
                                                        setParentOpen(false);
                                                    }}
                                                >
                                                    <Check
                                                        className={cn(
                                                            'size-4',
                                                            data.parent_id ===
                                                                null
                                                                ? 'opacity-100'
                                                                : 'opacity-0',
                                                        )}
                                                    />
                                                    No parent
                                                </CommandItem>
                                                {parentOptions.map((option) => {
                                                    const selected =
                                                        data.parent_id ===
                                                        option.id;

                                                    return (
                                                        <CommandItem
                                                            key={option.id}
                                                            value={option.name}
                                                            onSelect={() => {
                                                                setData(
                                                                    'parent_id',
                                                                    option.id,
                                                                );
                                                                setParentOpen(
                                                                    false,
                                                                );
                                                            }}
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
                                                })}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <InputError message={errors.parent_id} />
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="slogan">Slogan / short description</Label>
                            <Input
                                id="slogan"
                                value={data.slogan}
                                onChange={(event) =>
                                    setData('slogan', event.target.value)
                                }
                                maxLength={120}
                                placeholder="e.g. Ready to vape, no fuss"
                                autoComplete="off"
                            />
                            <p className="text-muted-foreground text-xs">
                                Shown on homepage category cards
                                {data.slogan.length > 0
                                    ? ` · ${data.slogan.length}/120`
                                    : ''}
                            </p>
                            <InputError message={errors.slogan} />
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
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
                                        Inactive categories stay hidden from
                                        browsing.
                                    </p>
                                </div>
                                <InputError message={errors.status} />
                            </div>

                            <div className="flex items-center gap-2 rounded-xl border border-border/70 bg-muted/20 px-4 py-3">
                                <Checkbox
                                    id="featured"
                                    checked={data.featured}
                                    disabled={featuredAtLimit}
                                    onCheckedChange={(value) =>
                                        setData('featured', value === true)
                                    }
                                />
                                <div className="space-y-0.5">
                                    <Label
                                        htmlFor="featured"
                                        className="font-medium"
                                    >
                                        Featured category
                                    </Label>
                                    <p className="text-muted-foreground text-xs">
                                        Highlight this category in featured
                                        storefront sections.
                                        {featuredRemaining !== null
                                            ? ` ${featuredRemaining} of ${limits?.featured_max} slots remaining.`
                                            : null}
                                        {featuredAtLimit
                                            ? ' Limit reached.'
                                            : null}
                                    </p>
                                </div>
                                <InputError message={errors.featured} />
                            </div>

                            <div className="flex items-center gap-2 rounded-xl border border-border/70 bg-muted/20 px-4 py-3">
                                <Checkbox
                                    id="show_in_nav"
                                    checked={data.show_in_nav}
                                    onCheckedChange={(value) =>
                                        setData(
                                            'show_in_nav',
                                            value === true,
                                        )
                                    }
                                />
                                <div className="space-y-0.5">
                                    <Label
                                        htmlFor="show_in_nav"
                                        className="font-medium"
                                    >
                                        Show in header navigation
                                    </Label>
                                    <p className="text-muted-foreground text-xs">
                                        {limits
                                            ? `Up to ${limits.nav_max} categories in the header${navRemaining !== null ? ` · ${navRemaining} remaining` : ''}.`
                                            : 'Include this category in the storefront header.'}
                                    </p>
                                </div>
                                <InputError message={errors.show_in_nav} />
                            </div>

                            <div className="flex flex-col gap-2 rounded-xl border border-border/70 bg-muted/20 px-4 py-3">
                                <Label htmlFor="nav_sort">Nav order</Label>
                                <Input
                                    id="nav_sort"
                                    type="number"
                                    min={0}
                                    disabled={!data.show_in_nav}
                                    value={
                                        data.nav_sort === null ||
                                        data.nav_sort === undefined
                                            ? ''
                                            : data.nav_sort
                                    }
                                    onChange={(event) => {
                                        const raw = event.target.value;
                                        setData(
                                            'nav_sort',
                                            raw === ''
                                                ? null
                                                : Number(raw),
                                        );
                                    }}
                                    placeholder="0"
                                />
                                <p className="text-muted-foreground text-xs">
                                    Lower numbers appear first when shown in
                                    navigation.
                                </p>
                                <InputError message={errors.nav_sort} />
                            </div>
                        </div>
                    </div>
                </section>

                <aside className="flex min-w-0 flex-col">
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
                                Listing photo and image background
                            </p>
                        </div>

                        <div className="overflow-hidden rounded-xl border border-border/80">
                            <div
                                className={cn(
                                    'aspect-square p-3',
                                    imageTileTransparent &&
                                        cn(
                                            'bg-muted/25',
                                            COLOR_PICKER_CHECKERBOARD,
                                        ),
                                )}
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
                                    accept={CATALOG_IMAGE_ACCEPT}
                                    maxSize={2}
                                    lightbox
                                    compactCards
                                    isUploading={processing}
                                    uploadProgress={
                                        progress?.percentage ?? null
                                    }
                                    onCancel={
                                        processing ? cancel : undefined
                                    }
                                    placeholder="Drop or click"
                                    hint="PNG, JPG, SVG, or WebP · Max 2MB"
                                    classNames={{
                                        wrapper:
                                            'flex h-full min-h-0 w-full flex-col',
                                        dropzone:
                                            'h-full min-h-0 border-dashed border-border/60 bg-background/75 px-3 py-6',
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
                                    defaultMode="solid"
                                    value={data.color_code}
                                    onChange={(color_code) =>
                                        setData('color_code', color_code)
                                    }
                                    label="Image background"
                                    description="Fill behind category imagery"
                                    dialogTitle="Image background"
                                    dialogDescription="Solid red is the default. Switch to none or a two-tone gradient if needed."
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
                    </section>
                </aside>
            </div>

            <div className="sticky bottom-0 z-10 -mx-2 flex flex-wrap items-center justify-end gap-3 border-t bg-background/95 px-2 py-4 backdrop-blur supports-[backdrop-filter]:bg-background/80">
                <Button variant="secondary" asChild>
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? <Spinner /> : null}
                    {submitLabel ??
                        (isEdit ? 'Save changes' : 'Create category')}
                </Button>
            </div>
        </form>
    );
}
