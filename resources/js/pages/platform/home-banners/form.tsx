import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { CmsImageField } from '@/components/platform/cms/cms-image-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

const settingsCardClass = cn(
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
    'sm:p-6',
);

type BannerForm = {
    id: number;
    is_active: boolean;
    eyebrow: string;
    title: string;
    description: string;
    image_path: string | null;
    image_url: string | null;
    primary_cta_label: string;
    primary_cta_href: string;
    secondary_cta_label: string;
    secondary_cta_href: string;
};

type Props = {
    banner: BannerForm | null;
};

export default function PlatformHomeBannerForm({ banner }: Props) {
    const isEdit = banner !== null;

    const form = useForm({
        is_active: banner?.is_active ?? true,
        eyebrow: banner?.eyebrow ?? 'PUFFMEMATE AUSTRALIA',
        title: banner?.title ?? '',
        description: banner?.description ?? '',
        primary_cta_label: banner?.primary_cta_label ?? 'Shop',
        primary_cta_href: banner?.primary_cta_href ?? '/shop',
        secondary_cta_label: banner?.secondary_cta_label ?? 'Explore Deals',
        secondary_cta_href: banner?.secondary_cta_href ?? '#home-deals',
        image: null as File | null,
        remove_image: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            form.put(`/platform/home-banners/${banner.id}`, {
                forceFormData: true,
                preserveScroll: true,
            });

            return;
        }

        form.post('/platform/home-banners', {
            forceFormData: true,
        });
    };

    return (
        <>
            <Head title={isEdit ? 'Edit banner' : 'Add banner'} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={isEdit ? 'Edit banner' : 'Add banner'}
                    description="Upload a wide photo, write short text, and set the two buttons visitors will tap."
                />

                <form className="space-y-6" onSubmit={submit}>
                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Banner
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Visibility and the wide photo shown behind the
                                text.
                            </p>
                        </div>

                        <div className="flex flex-col gap-5">
                            <div className="flex items-center justify-between gap-4 rounded-xl border p-4">
                                <div>
                                    <Label htmlFor="is_active">
                                        Show on homepage
                                    </Label>
                                    <p className="text-muted-foreground text-sm">
                                        Turn off to hide this slide without
                                        deleting it.
                                    </p>
                                </div>
                                <Switch
                                    id="is_active"
                                    checked={form.data.is_active}
                                    onCheckedChange={(checked) =>
                                        form.setData('is_active', checked)
                                    }
                                />
                            </div>
                            <InputError message={form.errors.is_active} />

                            <CmsImageField
                                label="Banner image"
                                hint="Wide photo works best (about 1600×900). PNG, JPG or WEBP up to 5MB."
                                path={
                                    form.data.remove_image
                                        ? null
                                        : banner?.image_path
                                }
                                url={
                                    form.data.remove_image
                                        ? null
                                        : banner?.image_url
                                }
                                value={form.data.image}
                                onChange={(file) => {
                                    form.setData((current) => ({
                                        ...current,
                                        image: file,
                                        remove_image: false,
                                    }));
                                }}
                                onRemove={() => {
                                    form.setData((current) => ({
                                        ...current,
                                        image: null,
                                        remove_image: true,
                                    }));
                                }}
                                error={form.errors.image}
                            />
                        </div>
                    </section>

                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Text
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Keep the title short so it fits on phones.
                            </p>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-2 sm:col-span-2">
                                <Label htmlFor="eyebrow">Small label</Label>
                                <Input
                                    id="eyebrow"
                                    value={form.data.eyebrow}
                                    onChange={(event) =>
                                        form.setData(
                                            'eyebrow',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="PUFFMEMATE AUSTRALIA"
                                />
                                <InputError message={form.errors.eyebrow} />
                            </div>

                            <div className="flex flex-col gap-2 sm:col-span-2">
                                <Label htmlFor="title">Headline</Label>
                                <Input
                                    id="title"
                                    value={form.data.title}
                                    onChange={(event) =>
                                        form.setData(
                                            'title',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Premium picks. Local stock."
                                />
                                <InputError message={form.errors.title} />
                            </div>

                            <div className="flex flex-col gap-2 sm:col-span-2">
                                <Label htmlFor="description">
                                    Short description
                                </Label>
                                <Textarea
                                    id="description"
                                    rows={3}
                                    value={form.data.description}
                                    onChange={(event) =>
                                        form.setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Browse featured packs and new arrivals from nearby branches."
                                />
                                <InputError message={form.errors.description} />
                            </div>
                        </div>
                    </section>

                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Buttons
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Use a site path like /shop, or a section link
                                like #home-deals.
                            </p>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="primary_cta_label">
                                    Main button text
                                </Label>
                                <Input
                                    id="primary_cta_label"
                                    value={form.data.primary_cta_label}
                                    onChange={(event) =>
                                        form.setData(
                                            'primary_cta_label',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.primary_cta_label}
                                />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="primary_cta_href">
                                    Main button link
                                </Label>
                                <Input
                                    id="primary_cta_href"
                                    value={form.data.primary_cta_href}
                                    onChange={(event) =>
                                        form.setData(
                                            'primary_cta_href',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="/shop"
                                />
                                <InputError
                                    message={form.errors.primary_cta_href}
                                />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="secondary_cta_label">
                                    Second button text
                                </Label>
                                <Input
                                    id="secondary_cta_label"
                                    value={form.data.secondary_cta_label}
                                    onChange={(event) =>
                                        form.setData(
                                            'secondary_cta_label',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.secondary_cta_label}
                                />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="secondary_cta_href">
                                    Second button link
                                </Label>
                                <Input
                                    id="secondary_cta_href"
                                    value={form.data.secondary_cta_href}
                                    onChange={(event) =>
                                        form.setData(
                                            'secondary_cta_href',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="#home-deals"
                                />
                                <InputError
                                    message={form.errors.secondary_cta_href}
                                />
                            </div>
                        </div>
                    </section>

                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/platform/home-banners">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {isEdit ? 'Save banner' : 'Add banner'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PlatformHomeBannerForm.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Home banners', href: '/platform/home-banners' },
        {
            title: props.banner ? 'Edit' : 'Add',
            href: props.banner
                ? `/platform/home-banners/${props.banner.id}/edit`
                : '/platform/home-banners/create',
        },
    ],
});
