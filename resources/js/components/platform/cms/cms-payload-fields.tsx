import { ChevronDown, Plus, Trash2 } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { RichTextEditor } from '@/components/editor/rich-text-editor';
import { LucideIconPicker } from '@/components/icons/lucide-icon-picker';
import InputError from '@/components/input-error';
import { CmsImageField } from '@/components/platform/cms/cms-image-field';
import {
    newId,
    padNumber,
} from '@/components/platform/cms/cms-payload-helpers';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { CMS_ICON_KEYS } from '@/lib/cms-icons';
import { cn } from '@/lib/utils';

const cardClass = cn(
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
    'sm:p-6',
);

export type CmsMediaControls = {
    heroImage: File | null;
    setHeroImage: (file: File | null) => void;
    removeHeroImage: boolean;
    setRemoveHeroImage: (value: boolean) => void;
    mediaImage: File | null;
    setMediaImage: (file: File | null) => void;
    removeMediaImage: boolean;
    setRemoveMediaImage: (value: boolean) => void;
};

type CmsPayloadFieldsProps = {
    slug: string;
    payload: any;
    setPayload: (next: any) => void;
    errors: Record<string, string>;
    media: CmsMediaControls;
};

type FieldDef = { key: string; label: string };

const CONTACT_LABEL_FIELDS: FieldDef[] = [
    { key: 'firstName', label: 'First name' },
    { key: 'lastName', label: 'Last name' },
    { key: 'email', label: 'Email' },
    { key: 'phone', label: 'Phone' },
    { key: 'message', label: 'Message' },
    { key: 'submitLabel', label: 'Submit button' },
];

const CONTACT_PLACEHOLDER_FIELDS: FieldDef[] = [
    { key: 'lastNamePlaceholder', label: 'Last name placeholder' },
    { key: 'phonePlaceholder', label: 'Phone placeholder' },
    { key: 'messagePlaceholder', label: 'Message placeholder' },
];

const WHOLESALE_LABEL_FIELDS: FieldDef[] = [
    { key: 'firstName', label: 'First name' },
    { key: 'lastName', label: 'Last name' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'company', label: 'Company' },
    { key: 'address', label: 'Address' },
    { key: 'message', label: 'Message' },
    { key: 'submitLabel', label: 'Submit button' },
];

const WHOLESALE_PLACEHOLDER_FIELDS: FieldDef[] = [
    { key: 'firstNamePlaceholder', label: 'First name placeholder' },
    { key: 'lastNamePlaceholder', label: 'Last name placeholder' },
    { key: 'phonePlaceholder', label: 'Phone placeholder' },
    { key: 'emailPlaceholder', label: 'Email placeholder' },
    { key: 'companyPlaceholder', label: 'Company placeholder' },
    { key: 'addressPlaceholder', label: 'Address placeholder' },
    { key: 'messagePlaceholder', label: 'Message placeholder' },
];

const ABOUT_TABS = [
    { id: 'hero', label: 'Hero' },
    { id: 'who', label: 'Who we are' },
    { id: 'how', label: 'How it works' },
    { id: 'multi', label: 'Multi-branch' },
    { id: 'delivery', label: 'Delivery' },
    { id: 'cta', label: 'CTA' },
] as const;

const FAQ_TABS = [
    { id: 'hero', label: 'Hero' },
    { id: 'categories', label: 'Categories' },
] as const;

function FieldError({
    errors,
    name,
}: {
    errors: Record<string, string>;
    name: string;
}) {
    return <InputError message={errors[name]} />;
}

function SectionHeader({
    title,
    description,
    action,
}: {
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div className="space-y-1">
                <h2 className="text-base font-semibold tracking-tight">
                    {title}
                </h2>
                {description ? (
                    <p className="text-muted-foreground text-sm">
                        {description}
                    </p>
                ) : null}
            </div>
            {action}
        </div>
    );
}

function SectionTabs<T extends string>({
    tabs,
    active,
    onChange,
}: {
    tabs: ReadonlyArray<{ id: T; label: string }>;
    active: T;
    onChange: (id: T) => void;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            {tabs.map((tab) => (
                <Button
                    key={tab.id}
                    type="button"
                    size="sm"
                    variant={active === tab.id ? 'default' : 'outline'}
                    onClick={() => onChange(tab.id)}
                >
                    {tab.label}
                </Button>
            ))}
        </div>
    );
}

function StringField({
    label,
    value,
    onChange,
    error,
    id,
    multiline,
    rows = 3,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    id?: string;
    multiline?: boolean;
    rows?: number;
}) {
    return (
        <div className="flex flex-col gap-2">
            <Label htmlFor={id}>{label}</Label>
            {multiline ? (
                <Textarea
                    id={id}
                    rows={rows}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                />
            ) : (
                <Input
                    id={id}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                />
            )}
            <InputError message={error} />
        </div>
    );
}

function FormCopyFields({
    form,
    setForm,
    errors,
    prefix,
    labelFields,
    placeholderFields,
}: {
    form: Record<string, unknown>;
    setForm: (next: Record<string, unknown>) => void;
    errors: Record<string, string>;
    prefix: string;
    labelFields: FieldDef[];
    placeholderFields: FieldDef[];
}) {
    const renderGroup = (title: string, fields: FieldDef[]) => (
        <div className="space-y-4">
            <p className="text-sm font-medium">{title}</p>
            <div className="grid gap-4 sm:grid-cols-2">
                {fields.map((field) => (
                    <StringField
                        key={field.key}
                        id={`${prefix}-${field.key}`}
                        label={field.label}
                        value={String(form?.[field.key] ?? '')}
                        onChange={(next) =>
                            setForm({ ...form, [field.key]: next })
                        }
                        error={errors[`${prefix}.${field.key}`]}
                    />
                ))}
            </div>
        </div>
    );

    return (
        <div className="space-y-6">
            {renderGroup('Field labels', labelFields)}
            {renderGroup('Placeholders', placeholderFields)}
        </div>
    );
}

function IndexBadge({ index }: { index: number }) {
    return (
        <span className="bg-muted text-muted-foreground inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-xs font-semibold tracking-wide">
            {padNumber(index)}
        </span>
    );
}

function LegalFields({
    payload,
    setPayload,
    errors,
}: Omit<CmsPayloadFieldsProps, 'slug' | 'media'>) {
    const summaryPoints: Array<{ id: string; title: string; body: string }> =
        Array.isArray(payload.summaryPoints) ? payload.summaryPoints : [];

    const updatePoint = (
        index: number,
        field: 'title' | 'body',
        value: string,
    ) => {
        setPayload({
            ...payload,
            summaryPoints: summaryPoints.map((point, i) =>
                i === index ? { ...point, [field]: value } : point,
            ),
        });
    };

    return (
        <div className="space-y-6">
            <section className={cardClass}>
                <SectionHeader
                    title="Page intro"
                    description="Eyebrow, summary, and version metadata."
                />
                <div className="grid gap-5 sm:grid-cols-2">
                    <StringField
                        id="eyebrow"
                        label="Eyebrow"
                        value={String(payload.eyebrow ?? '')}
                        onChange={(value) =>
                            setPayload({ ...payload, eyebrow: value })
                        }
                        error={errors['payload.eyebrow']}
                    />
                    <StringField
                        id="versionLabel"
                        label="Version label"
                        value={String(payload.versionLabel ?? '')}
                        onChange={(value) =>
                            setPayload({ ...payload, versionLabel: value })
                        }
                        error={errors['payload.versionLabel']}
                    />
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="updatedAt">Updated at</Label>
                        <DatePicker
                            id="updatedAt"
                            value={String(payload.updatedAt ?? '') || null}
                            onChange={(value) =>
                                setPayload({ ...payload, updatedAt: value })
                            }
                        />
                        <InputError message={errors['payload.updatedAt']} />
                    </div>
                    <StringField
                        id="applicableEntity"
                        label="Applicable entity"
                        value={String(payload.applicableEntity ?? '')}
                        onChange={(value) =>
                            setPayload({ ...payload, applicableEntity: value })
                        }
                        error={errors['payload.applicableEntity']}
                    />
                    <div className="sm:col-span-2">
                        <StringField
                            id="intro"
                            label="Intro"
                            multiline
                            rows={4}
                            value={String(payload.intro ?? '')}
                            onChange={(value) =>
                                setPayload({ ...payload, intro: value })
                            }
                            error={errors['payload.intro']}
                        />
                    </div>
                </div>
            </section>

            <section className={cardClass}>
                <SectionHeader
                    title="Summary points"
                    description="Short cards shown above the full policy body."
                    action={
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                setPayload({
                                    ...payload,
                                    summaryPoints: [
                                        ...summaryPoints,
                                        {
                                            id: newId('point'),
                                            title: '',
                                            body: '',
                                        },
                                    ],
                                })
                            }
                        >
                            <Plus className="size-4" />
                            Add point
                        </Button>
                    }
                />
                <div className="space-y-4">
                    {summaryPoints.map((point, index) => (
                        <div
                            key={point.id || `point-${index}`}
                            className="space-y-3 rounded-xl border p-4"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <p className="text-sm font-medium">
                                    Point {padNumber(index)}
                                </p>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Remove summary point"
                                    onClick={() =>
                                        setPayload({
                                            ...payload,
                                            summaryPoints: summaryPoints.filter(
                                                (_, i) => i !== index,
                                            ),
                                        })
                                    }
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                            <StringField
                                label="Title"
                                value={point.title ?? ''}
                                onChange={(value) =>
                                    updatePoint(index, 'title', value)
                                }
                            />
                            <StringField
                                label="Body"
                                multiline
                                value={point.body ?? ''}
                                onChange={(value) =>
                                    updatePoint(index, 'body', value)
                                }
                            />
                        </div>
                    ))}
                    <FieldError errors={errors} name="payload.summaryPoints" />
                </div>
            </section>

            <section className={cardClass}>
                <SectionHeader
                    title="Full body"
                    description="Rich content for the policy page."
                />
                <RichTextEditor
                    value={String(payload.body_html ?? '')}
                    onChange={(html) =>
                        setPayload({ ...payload, body_html: html })
                    }
                    placeholder="Write the full page content…"
                />
                <FieldError errors={errors} name="payload.body_html" />
            </section>
        </div>
    );
}

function ContactFields({
    payload,
    setPayload,
    errors,
    media,
}: Omit<CmsPayloadFieldsProps, 'slug'>) {
    const hero = payload.hero ?? {};
    const info = payload.info ?? {};
    const form = payload.form ?? {};

    return (
        <div className="space-y-6">
            <section className={cardClass}>
                <SectionHeader title="Hero" />
                <div className="grid gap-5 sm:grid-cols-2">
                    <StringField
                        label="Title"
                        value={String(hero.title ?? '')}
                        onChange={(value) =>
                            setPayload({
                                ...payload,
                                hero: { ...hero, title: value },
                            })
                        }
                        error={errors['payload.hero.title']}
                    />
                    <CmsImageField
                        label="Hero image"
                        path={hero.image}
                        url={hero.image_url}
                        value={media.heroImage}
                        onChange={(file) => {
                            media.setHeroImage(file);
                            media.setRemoveHeroImage(false);
                        }}
                        onRemove={() => {
                            media.setRemoveHeroImage(true);
                            media.setHeroImage(null);
                        }}
                        error={
                            errors['payload.hero.image'] ?? errors.hero_image
                        }
                    />
                    <div className="sm:col-span-2">
                        <StringField
                            label="Subtitle"
                            multiline
                            value={String(hero.subtitle ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    hero: { ...hero, subtitle: value },
                                })
                            }
                            error={errors['payload.hero.subtitle']}
                        />
                    </div>
                </div>
            </section>

            <section className={cardClass}>
                <SectionHeader title="Info block" />
                <div className="grid gap-5 sm:grid-cols-2">
                    <StringField
                        label="Title"
                        value={String(info.title ?? '')}
                        onChange={(value) =>
                            setPayload({
                                ...payload,
                                info: { ...info, title: value },
                            })
                        }
                        error={errors['payload.info.title']}
                    />
                    <StringField
                        label="Subtitle"
                        value={String(info.subtitle ?? '')}
                        onChange={(value) =>
                            setPayload({
                                ...payload,
                                info: { ...info, subtitle: value },
                            })
                        }
                        error={errors['payload.info.subtitle']}
                    />
                </div>
            </section>

            <section className={cardClass}>
                <SectionHeader
                    title="Form copy"
                    description="Labels and placeholders shown on the contact form."
                />
                <FormCopyFields
                    form={form}
                    prefix="payload.form"
                    errors={errors}
                    labelFields={CONTACT_LABEL_FIELDS}
                    placeholderFields={CONTACT_PLACEHOLDER_FIELDS}
                    setForm={(next) => setPayload({ ...payload, form: next })}
                />
            </section>
        </div>
    );
}

function WholesaleFields({
    payload,
    setPayload,
    errors,
}: Omit<CmsPayloadFieldsProps, 'slug' | 'media'>) {
    const intro = payload.intro ?? {};
    const inquiry = payload.inquiry ?? {};
    const form = payload.form ?? {};
    const benefits: Array<{ title: string; description: string }> =
        Array.isArray(intro.benefits) ? intro.benefits : [];

    return (
        <div className="space-y-6">
            <section className={cardClass}>
                <SectionHeader title="Intro" />
                <div className="grid gap-5">
                    <StringField
                        label="Title"
                        value={String(intro.title ?? '')}
                        onChange={(value) =>
                            setPayload({
                                ...payload,
                                intro: { ...intro, title: value },
                            })
                        }
                        error={errors['payload.intro.title']}
                    />
                    <StringField
                        label="Body"
                        multiline
                        rows={4}
                        value={String(intro.body ?? '')}
                        onChange={(value) =>
                            setPayload({
                                ...payload,
                                intro: { ...intro, body: value },
                            })
                        }
                        error={errors['payload.intro.body']}
                    />
                </div>
            </section>

            <section className={cardClass}>
                <SectionHeader
                    title="Benefits"
                    action={
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                setPayload({
                                    ...payload,
                                    intro: {
                                        ...intro,
                                        benefits: [
                                            ...benefits,
                                            { title: '', description: '' },
                                        ],
                                    },
                                })
                            }
                        >
                            <Plus className="size-4" />
                            Add benefit
                        </Button>
                    }
                />
                <div className="space-y-4">
                    {benefits.map((benefit, index) => (
                        <div
                            key={`benefit-${index}`}
                            className="grid gap-3 rounded-xl border p-4 sm:grid-cols-[1fr_1.5fr_auto]"
                        >
                            <StringField
                                label="Title"
                                value={benefit.title ?? ''}
                                onChange={(value) =>
                                    setPayload({
                                        ...payload,
                                        intro: {
                                            ...intro,
                                            benefits: benefits.map((item, i) =>
                                                i === index
                                                    ? { ...item, title: value }
                                                    : item,
                                            ),
                                        },
                                    })
                                }
                            />
                            <StringField
                                label="Description"
                                multiline
                                rows={2}
                                value={benefit.description ?? ''}
                                onChange={(value) =>
                                    setPayload({
                                        ...payload,
                                        intro: {
                                            ...intro,
                                            benefits: benefits.map((item, i) =>
                                                i === index
                                                    ? {
                                                          ...item,
                                                          description: value,
                                                      }
                                                    : item,
                                            ),
                                        },
                                    })
                                }
                            />
                            <div className="flex items-end">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Remove benefit"
                                    onClick={() =>
                                        setPayload({
                                            ...payload,
                                            intro: {
                                                ...intro,
                                                benefits: benefits.filter(
                                                    (_, i) => i !== index,
                                                ),
                                            },
                                        })
                                    }
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <section className={cardClass}>
                <SectionHeader title="Inquiry" />
                <div className="grid gap-5 sm:grid-cols-2">
                    <StringField
                        label="Title"
                        value={String(inquiry.title ?? '')}
                        onChange={(value) =>
                            setPayload({
                                ...payload,
                                inquiry: { ...inquiry, title: value },
                            })
                        }
                        error={errors['payload.inquiry.title']}
                    />
                    <StringField
                        label="Subtitle"
                        value={String(inquiry.subtitle ?? '')}
                        onChange={(value) =>
                            setPayload({
                                ...payload,
                                inquiry: { ...inquiry, subtitle: value },
                            })
                        }
                        error={errors['payload.inquiry.subtitle']}
                    />
                </div>
            </section>

            <section className={cardClass}>
                <SectionHeader
                    title="Form copy"
                    description="Labels and placeholders for the wholesale enquiry form."
                />
                <FormCopyFields
                    form={form}
                    prefix="payload.form"
                    errors={errors}
                    labelFields={WHOLESALE_LABEL_FIELDS}
                    placeholderFields={WHOLESALE_PLACEHOLDER_FIELDS}
                    setForm={(next) => setPayload({ ...payload, form: next })}
                />
            </section>
        </div>
    );
}

function FaqFields({
    payload,
    setPayload,
    errors,
}: Omit<CmsPayloadFieldsProps, 'slug' | 'media'>) {
    const [tab, setTab] = useState<(typeof FAQ_TABS)[number]['id']>('hero');
    const hero = payload.hero ?? {};
    const categories: Array<{
        id: string;
        title: string;
        icon: string;
        items: Array<{
            id: string;
            question: string;
            answer: string;
            defaultOpen?: boolean;
        }>;
    }> = Array.isArray(payload.categories) ? payload.categories : [];

    const setCategories = (next: typeof categories) => {
        setPayload({ ...payload, categories: next });
    };

    const updateCategory = (
        categoryIndex: number,
        patch: Partial<(typeof categories)[number]>,
    ) => {
        setCategories(
            categories.map((category, i) =>
                i === categoryIndex ? { ...category, ...patch } : category,
            ),
        );
    };

    const updateItem = (
        categoryIndex: number,
        itemIndex: number,
        patch: Partial<(typeof categories)[number]['items'][number]>,
    ) => {
        const category = categories[categoryIndex];
        const items = (category.items ?? []).map((item, i) =>
            i === itemIndex ? { ...item, ...patch } : item,
        );
        updateCategory(categoryIndex, { items });
    };

    return (
        <div className="space-y-6">
            <SectionTabs tabs={FAQ_TABS} active={tab} onChange={setTab} />

            {tab === 'hero' ? (
                <section className={cardClass}>
                    <SectionHeader title="Hero" />
                    <div className="grid gap-5 sm:grid-cols-2">
                        <StringField
                            label="Badge"
                            value={String(hero.badge ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    hero: { ...hero, badge: value },
                                })
                            }
                            error={errors['payload.hero.badge']}
                        />
                        <StringField
                            label="Title"
                            value={String(hero.title ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    hero: { ...hero, title: value },
                                })
                            }
                            error={errors['payload.hero.title']}
                        />
                        <div className="sm:col-span-2">
                            <StringField
                                label="Subtitle"
                                multiline
                                value={String(hero.subtitle ?? '')}
                                onChange={(value) =>
                                    setPayload({
                                        ...payload,
                                        hero: { ...hero, subtitle: value },
                                    })
                                }
                                error={errors['payload.hero.subtitle']}
                            />
                        </div>
                    </div>
                </section>
            ) : null}

            {tab === 'categories' ? (
                <section className={cardClass}>
                    <SectionHeader
                        title="Categories"
                        description="FAQ hubs with nested questions and answers."
                        action={
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setCategories([
                                        ...categories,
                                        {
                                            id: newId('category'),
                                            title: '',
                                            icon: '',
                                            items: [],
                                        },
                                    ])
                                }
                            >
                                <Plus className="size-4" />
                                Add category
                            </Button>
                        }
                    />

                    <div className="space-y-4">
                        {categories.map((category, categoryIndex) => (
                            <Collapsible
                                key={category.id || `category-${categoryIndex}`}
                                defaultOpen={categoryIndex === 0}
                                className="rounded-xl border"
                            >
                                <div className="flex items-center gap-2 px-4 py-3">
                                    <CollapsibleTrigger asChild>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="flex-1 justify-start gap-2 px-0"
                                        >
                                            <ChevronDown className="size-4" />
                                            <span className="font-medium">
                                                {category.title ||
                                                    `Category ${padNumber(categoryIndex)}`}
                                            </span>
                                        </Button>
                                    </CollapsibleTrigger>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove category"
                                        onClick={() =>
                                            setCategories(
                                                categories.filter(
                                                    (_, i) =>
                                                        i !== categoryIndex,
                                                ),
                                            )
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>

                                <CollapsibleContent className="space-y-4 border-t px-4 py-4">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <StringField
                                            label="Title"
                                            value={category.title ?? ''}
                                            onChange={(value) =>
                                                updateCategory(categoryIndex, {
                                                    title: value,
                                                })
                                            }
                                        />
                                        <div className="flex flex-col gap-2">
                                            <Label>Icon</Label>
                                            <LucideIconPicker
                                                mode="dialog"
                                                triggerVariant="field"
                                                allowedIcons={CMS_ICON_KEYS}
                                                value={category.icon ?? ''}
                                                onChange={(icon) =>
                                                    updateCategory(
                                                        categoryIndex,
                                                        { icon },
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-3 border-t pt-4">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <p className="text-muted-foreground text-sm font-medium">
                                                Questions
                                            </p>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    updateCategory(
                                                        categoryIndex,
                                                        {
                                                            items: [
                                                                ...(category.items ??
                                                                    []),
                                                                {
                                                                    id: newId(
                                                                        'faq',
                                                                    ),
                                                                    question:
                                                                        '',
                                                                    answer: '',
                                                                    defaultOpen:
                                                                        false,
                                                                },
                                                            ],
                                                        },
                                                    )
                                                }
                                            >
                                                <Plus className="size-4" />
                                                Add question
                                            </Button>
                                        </div>

                                        {(category.items ?? []).map(
                                            (item, itemIndex) => (
                                                <div
                                                    key={
                                                        item.id ||
                                                        `item-${categoryIndex}-${itemIndex}`
                                                    }
                                                    className="bg-muted/20 space-y-3 rounded-lg border p-3"
                                                >
                                                    <div className="flex items-start justify-between gap-3">
                                                        <p className="text-sm font-medium">
                                                            Question{' '}
                                                            {padNumber(
                                                                itemIndex,
                                                            )}
                                                        </p>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label="Remove question"
                                                            onClick={() =>
                                                                updateCategory(
                                                                    categoryIndex,
                                                                    {
                                                                        items: (
                                                                            category.items ??
                                                                            []
                                                                        ).filter(
                                                                            (
                                                                                _,
                                                                                i,
                                                                            ) =>
                                                                                i !==
                                                                                itemIndex,
                                                                        ),
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    </div>
                                                    <StringField
                                                        label="Question"
                                                        value={
                                                            item.question ?? ''
                                                        }
                                                        onChange={(value) =>
                                                            updateItem(
                                                                categoryIndex,
                                                                itemIndex,
                                                                {
                                                                    question:
                                                                        value,
                                                                },
                                                            )
                                                        }
                                                    />
                                                    <div className="flex flex-col gap-2">
                                                        <Label>Answer</Label>
                                                        <RichTextEditor
                                                            value={
                                                                item.answer ??
                                                                ''
                                                            }
                                                            onChange={(html) =>
                                                                updateItem(
                                                                    categoryIndex,
                                                                    itemIndex,
                                                                    {
                                                                        answer: html,
                                                                    },
                                                                )
                                                            }
                                                            placeholder="Write the answer…"
                                                        />
                                                    </div>
                                                    <div className="flex items-center justify-between gap-3 rounded-xl border p-3">
                                                        <Label
                                                            htmlFor={`faq-open-${categoryIndex}-${itemIndex}`}
                                                        >
                                                            Default open
                                                        </Label>
                                                        <Switch
                                                            id={`faq-open-${categoryIndex}-${itemIndex}`}
                                                            checked={Boolean(
                                                                item.defaultOpen,
                                                            )}
                                                            onCheckedChange={(
                                                                checked,
                                                            ) =>
                                                                updateItem(
                                                                    categoryIndex,
                                                                    itemIndex,
                                                                    {
                                                                        defaultOpen:
                                                                            checked,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>
                        ))}
                        <FieldError
                            errors={errors}
                            name="payload.categories"
                        />
                    </div>
                </section>
            ) : null}
        </div>
    );
}

function AboutFields({
    payload,
    setPayload,
    errors,
    media,
}: Omit<CmsPayloadFieldsProps, 'slug'>) {
    const [tab, setTab] =
        useState<(typeof ABOUT_TABS)[number]['id']>('hero');

    const hero = payload.hero ?? {};
    const who = payload.who_we_are ?? {};
    const how = payload.how_it_works ?? {};
    const multi = payload.multi_branch ?? {};
    const delivery = payload.delivery ?? {};
    const cta = payload.cta ?? {};

    const features: Array<{ title: string; description: string }> =
        Array.isArray(who.features) ? who.features : [];
    const steps: Array<{
        number: string;
        title: string;
        description: string;
        icon: string;
    }> = Array.isArray(how.steps) ? how.steps : [];
    const factors: Array<{
        number: string;
        title: string;
        description: string;
    }> = Array.isArray(multi.factors) ? multi.factors : [];
    const paragraphs: string[] = Array.isArray(delivery.paragraphs)
        ? delivery.paragraphs
        : [];
    const statuses: Array<{ label: string; state: string }> = Array.isArray(
        delivery.statuses,
    )
        ? delivery.statuses
        : [];

    return (
        <div className="space-y-6">
            <SectionTabs tabs={ABOUT_TABS} active={tab} onChange={setTab} />

            {tab === 'hero' ? (
                <section className={cardClass}>
                    <SectionHeader title="Hero" />
                    <div className="grid gap-5 sm:grid-cols-2">
                        <StringField
                            label="Title"
                            value={String(hero.title ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    hero: { ...hero, title: value },
                                })
                            }
                            error={errors['payload.hero.title']}
                        />
                        <CmsImageField
                            label="Hero image"
                            path={hero.image}
                            url={hero.image_url}
                            value={media.heroImage}
                            onChange={(file) => {
                                media.setHeroImage(file);
                                media.setRemoveHeroImage(false);
                            }}
                            onRemove={() => {
                                media.setRemoveHeroImage(true);
                                media.setHeroImage(null);
                            }}
                            error={
                                errors['payload.hero.image'] ??
                                errors.hero_image
                            }
                        />
                        <div className="sm:col-span-2">
                            <StringField
                                label="Subtitle"
                                multiline
                                value={String(hero.subtitle ?? '')}
                                onChange={(value) =>
                                    setPayload({
                                        ...payload,
                                        hero: { ...hero, subtitle: value },
                                    })
                                }
                                error={errors['payload.hero.subtitle']}
                            />
                        </div>
                    </div>
                </section>
            ) : null}

            {tab === 'who' ? (
                <section className={cardClass}>
                    <SectionHeader title="Who we are" />
                    <div className="grid gap-5 sm:grid-cols-2">
                        <StringField
                            label="Eyebrow"
                            value={String(who.eyebrow ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    who_we_are: { ...who, eyebrow: value },
                                })
                            }
                        />
                        <StringField
                            label="Heading"
                            value={String(who.heading ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    who_we_are: { ...who, heading: value },
                                })
                            }
                        />
                        <div className="sm:col-span-2">
                            <StringField
                                label="Body"
                                multiline
                                rows={4}
                                value={String(who.body ?? '')}
                                onChange={(value) =>
                                    setPayload({
                                        ...payload,
                                        who_we_are: { ...who, body: value },
                                    })
                                }
                            />
                        </div>
                        <CmsImageField
                            label="Media image"
                            path={who.mediaImage}
                            url={who.mediaImage_url}
                            value={media.mediaImage}
                            onChange={(file) => {
                                media.setMediaImage(file);
                                media.setRemoveMediaImage(false);
                            }}
                            onRemove={() => {
                                media.setRemoveMediaImage(true);
                                media.setMediaImage(null);
                            }}
                            error={
                                errors['payload.who_we_are.mediaImage'] ??
                                errors.media_image
                            }
                        />
                        <StringField
                            label="Media title"
                            value={String(who.mediaTitle ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    who_we_are: { ...who, mediaTitle: value },
                                })
                            }
                        />
                        <div className="sm:col-span-2">
                            <StringField
                                label="Media caption"
                                value={String(who.mediaCaption ?? '')}
                                onChange={(value) =>
                                    setPayload({
                                        ...payload,
                                        who_we_are: {
                                            ...who,
                                            mediaCaption: value,
                                        },
                                    })
                                }
                            />
                        </div>
                    </div>

                    <div className="mt-6 space-y-4 border-t pt-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <p className="text-sm font-medium">Features</p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setPayload({
                                        ...payload,
                                        who_we_are: {
                                            ...who,
                                            features: [
                                                ...features,
                                                { title: '', description: '' },
                                            ],
                                        },
                                    })
                                }
                            >
                                <Plus className="size-4" />
                                Add feature
                            </Button>
                        </div>
                        {features.map((feature, index) => (
                            <div
                                key={`feature-${index}`}
                                className="grid gap-3 rounded-xl border p-4 sm:grid-cols-[1fr_1.5fr_auto]"
                            >
                                <StringField
                                    label="Title"
                                    value={feature.title ?? ''}
                                    onChange={(value) =>
                                        setPayload({
                                            ...payload,
                                            who_we_are: {
                                                ...who,
                                                features: features.map(
                                                    (item, i) =>
                                                        i === index
                                                            ? {
                                                                  ...item,
                                                                  title: value,
                                                              }
                                                            : item,
                                                ),
                                            },
                                        })
                                    }
                                />
                                <StringField
                                    label="Description"
                                    multiline
                                    rows={2}
                                    value={feature.description ?? ''}
                                    onChange={(value) =>
                                        setPayload({
                                            ...payload,
                                            who_we_are: {
                                                ...who,
                                                features: features.map(
                                                    (item, i) =>
                                                        i === index
                                                            ? {
                                                                  ...item,
                                                                  description:
                                                                      value,
                                                              }
                                                            : item,
                                                ),
                                            },
                                        })
                                    }
                                />
                                <div className="flex items-end">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove feature"
                                        onClick={() =>
                                            setPayload({
                                                ...payload,
                                                who_we_are: {
                                                    ...who,
                                                    features: features.filter(
                                                        (_, i) => i !== index,
                                                    ),
                                                },
                                            })
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            ) : null}

            {tab === 'how' ? (
                <section className={cardClass}>
                    <SectionHeader
                        title="How it works"
                        action={
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setPayload({
                                        ...payload,
                                        how_it_works: {
                                            ...how,
                                            steps: [
                                                ...steps,
                                                {
                                                    number: padNumber(
                                                        steps.length,
                                                    ),
                                                    title: '',
                                                    description: '',
                                                    icon: '',
                                                },
                                            ],
                                        },
                                    })
                                }
                            >
                                <Plus className="size-4" />
                                Add step
                            </Button>
                        }
                    />
                    <div className="mb-5 grid gap-5 sm:grid-cols-2">
                        <StringField
                            label="Eyebrow"
                            value={String(how.eyebrow ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    how_it_works: { ...how, eyebrow: value },
                                })
                            }
                        />
                        <StringField
                            label="Heading"
                            value={String(how.heading ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    how_it_works: { ...how, heading: value },
                                })
                            }
                        />
                        <div className="sm:col-span-2">
                            <StringField
                                label="Body"
                                multiline
                                value={String(how.body ?? '')}
                                onChange={(value) =>
                                    setPayload({
                                        ...payload,
                                        how_it_works: { ...how, body: value },
                                    })
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-4">
                        {steps.map((step, index) => (
                            <div
                                key={`step-${index}`}
                                className="space-y-3 rounded-xl border p-4"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex items-center gap-3">
                                        <IndexBadge index={index} />
                                        <p className="text-sm font-medium">
                                            Step {padNumber(index)}
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove step"
                                        onClick={() =>
                                            setPayload({
                                                ...payload,
                                                how_it_works: {
                                                    ...how,
                                                    steps: steps
                                                        .filter(
                                                            (_, i) =>
                                                                i !== index,
                                                        )
                                                        .map((item, i) => ({
                                                            ...item,
                                                            number: padNumber(
                                                                i,
                                                            ),
                                                        })),
                                                },
                                            })
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <StringField
                                        label="Title"
                                        value={step.title ?? ''}
                                        onChange={(value) =>
                                            setPayload({
                                                ...payload,
                                                how_it_works: {
                                                    ...how,
                                                    steps: steps.map(
                                                        (item, i) =>
                                                            i === index
                                                                ? {
                                                                      ...item,
                                                                      number: padNumber(
                                                                          index,
                                                                      ),
                                                                      title: value,
                                                                  }
                                                                : item,
                                                    ),
                                                },
                                            })
                                        }
                                    />
                                    <div className="flex flex-col gap-2">
                                        <Label>Icon</Label>
                                        <LucideIconPicker
                                            mode="dialog"
                                            triggerVariant="field"
                                            allowedIcons={CMS_ICON_KEYS}
                                            value={step.icon ?? ''}
                                            onChange={(icon) =>
                                                setPayload({
                                                    ...payload,
                                                    how_it_works: {
                                                        ...how,
                                                        steps: steps.map(
                                                            (item, i) =>
                                                                i === index
                                                                    ? {
                                                                          ...item,
                                                                          number: padNumber(
                                                                              index,
                                                                          ),
                                                                          icon,
                                                                      }
                                                                    : item,
                                                        ),
                                                    },
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <StringField
                                            label="Description"
                                            multiline
                                            rows={2}
                                            value={step.description ?? ''}
                                            onChange={(value) =>
                                                setPayload({
                                                    ...payload,
                                                    how_it_works: {
                                                        ...how,
                                                        steps: steps.map(
                                                            (item, i) =>
                                                                i === index
                                                                    ? {
                                                                          ...item,
                                                                          number: padNumber(
                                                                              index,
                                                                          ),
                                                                          description:
                                                                              value,
                                                                      }
                                                                    : item,
                                                        ),
                                                    },
                                                })
                                            }
                                        />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            ) : null}

            {tab === 'multi' ? (
                <section className={cardClass}>
                    <SectionHeader
                        title="Multi-branch"
                        action={
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setPayload({
                                        ...payload,
                                        multi_branch: {
                                            ...multi,
                                            factors: [
                                                ...factors,
                                                {
                                                    number: padNumber(
                                                        factors.length,
                                                    ),
                                                    title: '',
                                                    description: '',
                                                },
                                            ],
                                        },
                                    })
                                }
                            >
                                <Plus className="size-4" />
                                Add factor
                            </Button>
                        }
                    />
                    <div className="mb-5 grid gap-5 sm:grid-cols-2">
                        <StringField
                            label="Eyebrow"
                            value={String(multi.eyebrow ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    multi_branch: {
                                        ...multi,
                                        eyebrow: value,
                                    },
                                })
                            }
                        />
                        <StringField
                            label="Heading"
                            value={String(multi.heading ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    multi_branch: {
                                        ...multi,
                                        heading: value,
                                    },
                                })
                            }
                        />
                        <div className="sm:col-span-2">
                            <StringField
                                label="Body"
                                multiline
                                value={String(multi.body ?? '')}
                                onChange={(value) =>
                                    setPayload({
                                        ...payload,
                                        multi_branch: {
                                            ...multi,
                                            body: value,
                                        },
                                    })
                                }
                            />
                        </div>
                        <div className="sm:col-span-2">
                            <StringField
                                label="Promise"
                                value={String(multi.promise ?? '')}
                                onChange={(value) =>
                                    setPayload({
                                        ...payload,
                                        multi_branch: {
                                            ...multi,
                                            promise: value,
                                        },
                                    })
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-4">
                        {factors.map((factor, index) => (
                            <div
                                key={`factor-${index}`}
                                className="grid gap-3 rounded-xl border p-4 sm:grid-cols-[auto_1fr_1.5fr_auto]"
                            >
                                <div className="flex items-end pb-2">
                                    <IndexBadge index={index} />
                                </div>
                                <StringField
                                    label="Title"
                                    value={factor.title ?? ''}
                                    onChange={(value) =>
                                        setPayload({
                                            ...payload,
                                            multi_branch: {
                                                ...multi,
                                                factors: factors.map(
                                                    (item, i) =>
                                                        i === index
                                                            ? {
                                                                  ...item,
                                                                  number: padNumber(
                                                                      index,
                                                                  ),
                                                                  title: value,
                                                              }
                                                            : item,
                                                ),
                                            },
                                        })
                                    }
                                />
                                <StringField
                                    label="Description"
                                    multiline
                                    rows={2}
                                    value={factor.description ?? ''}
                                    onChange={(value) =>
                                        setPayload({
                                            ...payload,
                                            multi_branch: {
                                                ...multi,
                                                factors: factors.map(
                                                    (item, i) =>
                                                        i === index
                                                            ? {
                                                                  ...item,
                                                                  number: padNumber(
                                                                      index,
                                                                  ),
                                                                  description:
                                                                      value,
                                                              }
                                                            : item,
                                                ),
                                            },
                                        })
                                    }
                                />
                                <div className="flex items-end">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove factor"
                                        onClick={() =>
                                            setPayload({
                                                ...payload,
                                                multi_branch: {
                                                    ...multi,
                                                    factors: factors
                                                        .filter(
                                                            (_, i) =>
                                                                i !== index,
                                                        )
                                                        .map((item, i) => ({
                                                            ...item,
                                                            number: padNumber(
                                                                i,
                                                            ),
                                                        })),
                                                },
                                            })
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            ) : null}

            {tab === 'delivery' ? (
                <section className={cardClass}>
                    <SectionHeader title="Delivery" />
                    <div className="mb-5 grid gap-5 sm:grid-cols-2">
                        <StringField
                            label="Eyebrow"
                            value={String(delivery.eyebrow ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    delivery: { ...delivery, eyebrow: value },
                                })
                            }
                        />
                        <StringField
                            label="Heading"
                            value={String(delivery.heading ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    delivery: { ...delivery, heading: value },
                                })
                            }
                        />
                    </div>

                    <div className="mb-5 space-y-3">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <p className="text-sm font-medium">Paragraphs</p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setPayload({
                                        ...payload,
                                        delivery: {
                                            ...delivery,
                                            paragraphs: [...paragraphs, ''],
                                        },
                                    })
                                }
                            >
                                <Plus className="size-4" />
                                Add paragraph
                            </Button>
                        </div>
                        {paragraphs.map((paragraph, index) => (
                            <div
                                key={`paragraph-${index}`}
                                className="flex gap-2"
                            >
                                <Textarea
                                    rows={2}
                                    className="flex-1"
                                    value={paragraph}
                                    onChange={(event) =>
                                        setPayload({
                                            ...payload,
                                            delivery: {
                                                ...delivery,
                                                paragraphs: paragraphs.map(
                                                    (item, i) =>
                                                        i === index
                                                            ? event.target
                                                                  .value
                                                            : item,
                                                ),
                                            },
                                        })
                                    }
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Remove paragraph"
                                    onClick={() =>
                                        setPayload({
                                            ...payload,
                                            delivery: {
                                                ...delivery,
                                                paragraphs: paragraphs.filter(
                                                    (_, i) => i !== index,
                                                ),
                                            },
                                        })
                                    }
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        ))}
                    </div>

                    <div className="space-y-3 border-t pt-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <p className="text-sm font-medium">Statuses</p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setPayload({
                                        ...payload,
                                        delivery: {
                                            ...delivery,
                                            statuses: [
                                                ...statuses,
                                                {
                                                    label: '',
                                                    state: 'pending',
                                                },
                                            ],
                                        },
                                    })
                                }
                            >
                                <Plus className="size-4" />
                                Add status
                            </Button>
                        </div>
                        {statuses.map((status, index) => (
                            <div
                                key={`status-${index}`}
                                className="grid gap-3 rounded-xl border p-4 sm:grid-cols-[1fr_1fr_auto]"
                            >
                                <StringField
                                    label="Label"
                                    value={status.label ?? ''}
                                    onChange={(value) =>
                                        setPayload({
                                            ...payload,
                                            delivery: {
                                                ...delivery,
                                                statuses: statuses.map(
                                                    (item, i) =>
                                                        i === index
                                                            ? {
                                                                  ...item,
                                                                  label: value,
                                                              }
                                                            : item,
                                                ),
                                            },
                                        })
                                    }
                                />
                                <div className="flex flex-col gap-2">
                                    <Label>State</Label>
                                    <Select
                                        value={status.state || 'pending'}
                                        onValueChange={(value) =>
                                            setPayload({
                                                ...payload,
                                                delivery: {
                                                    ...delivery,
                                                    statuses: statuses.map(
                                                        (item, i) =>
                                                            i === index
                                                                ? {
                                                                      ...item,
                                                                      state: value,
                                                                  }
                                                                : item,
                                                    ),
                                                },
                                            })
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Select state" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="done">
                                                Done
                                            </SelectItem>
                                            <SelectItem value="active">
                                                Active
                                            </SelectItem>
                                            <SelectItem value="pending">
                                                Pending
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex items-end">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove status"
                                        onClick={() =>
                                            setPayload({
                                                ...payload,
                                                delivery: {
                                                    ...delivery,
                                                    statuses: statuses.filter(
                                                        (_, i) => i !== index,
                                                    ),
                                                },
                                            })
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            ) : null}

            {tab === 'cta' ? (
                <section className={cardClass}>
                    <SectionHeader title="CTA" />
                    <div className="grid gap-5 sm:grid-cols-2">
                        <StringField
                            label="Eyebrow"
                            value={String(cta.eyebrow ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    cta: { ...cta, eyebrow: value },
                                })
                            }
                        />
                        <StringField
                            label="Heading"
                            value={String(cta.heading ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    cta: { ...cta, heading: value },
                                })
                            }
                        />
                        <div className="sm:col-span-2">
                            <StringField
                                label="Body"
                                multiline
                                value={String(cta.body ?? '')}
                                onChange={(value) =>
                                    setPayload({
                                        ...payload,
                                        cta: { ...cta, body: value },
                                    })
                                }
                            />
                        </div>
                        <StringField
                            label="Primary label"
                            value={String(cta.primaryLabel ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    cta: { ...cta, primaryLabel: value },
                                })
                            }
                        />
                        <StringField
                            label="Secondary label"
                            value={String(cta.secondaryLabel ?? '')}
                            onChange={(value) =>
                                setPayload({
                                    ...payload,
                                    cta: { ...cta, secondaryLabel: value },
                                })
                            }
                        />
                    </div>
                </section>
            ) : null}
        </div>
    );
}

export default function CmsPayloadFields({
    slug,
    payload,
    setPayload,
    errors,
    media,
}: CmsPayloadFieldsProps) {
    if (slug === 'privacy' || slug === 'terms') {
        return (
            <LegalFields
                payload={payload}
                setPayload={setPayload}
                errors={errors}
            />
        );
    }

    if (slug === 'contact') {
        return (
            <ContactFields
                payload={payload}
                setPayload={setPayload}
                errors={errors}
                media={media}
            />
        );
    }

    if (slug === 'wholesale') {
        return (
            <WholesaleFields
                payload={payload}
                setPayload={setPayload}
                errors={errors}
            />
        );
    }

    if (slug === 'faqs') {
        return (
            <FaqFields
                payload={payload}
                setPayload={setPayload}
                errors={errors}
            />
        );
    }

    if (slug === 'about') {
        return (
            <AboutFields
                payload={payload}
                setPayload={setPayload}
                errors={errors}
                media={media}
            />
        );
    }

    return (
        <section className={cardClass}>
            <SectionHeader title="Unsupported page" />
            <p className="text-muted-foreground text-sm">
                This page type cannot be edited here.
            </p>
        </section>
    );
}
