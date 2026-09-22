import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import CmsPayloadFields from '@/components/platform/cms/cms-payload-fields';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-react';

const settingsCardClass = cn(
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
    'sm:p-6',
);

type CmsPageForm = {
    id: number;
    slug: string;
    title: string;
    meta_title: string | null;
    meta_description: string | null;
    is_published: boolean;
    payload: Record<string, unknown>;
    updated_at: string | null;
};

type Props = {
    page: CmsPageForm;
};

export default function PlatformPagesEdit({ page }: Props) {
    const [seoOpen, setSeoOpen] = useState(false);
    const form = useForm({
        title: page.title ?? '',
        meta_title: page.meta_title ?? '',
        meta_description: page.meta_description ?? '',
        is_published: page.is_published,
        payload: page.payload ?? {},
        hero_image: null as File | null,
        media_image: null as File | null,
        remove_hero_image: false,
        remove_media_image: false,
    });

    return (
        <>
            <Head title={`Edit ${page.title}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${page.title}`}
                    description="Change the text and images visitors see. Save when you are done."
                />

                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put(`/platform/pages/${page.slug}`, {
                            forceFormData: true,
                            preserveScroll: true,
                            onSuccess: () => {
                                form.setData((current) => ({
                                    ...current,
                                    hero_image: null,
                                    media_image: null,
                                    remove_hero_image: false,
                                    remove_media_image: false,
                                }));
                            },
                        });
                    }}
                >
                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Page settings
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Publish status and the page title shown in the
                                browser.
                            </p>
                        </div>

                        <div className="flex flex-col gap-5">
                            <div className="flex items-center justify-between gap-4 rounded-xl border p-4">
                                <div>
                                    <Label htmlFor="is_published">
                                        Published
                                    </Label>
                                    <p className="text-muted-foreground text-sm">
                                        Turn off to hide this page from the
                                        website.
                                    </p>
                                </div>
                                <Switch
                                    id="is_published"
                                    checked={form.data.is_published}
                                    onCheckedChange={(checked) =>
                                        form.setData('is_published', checked)
                                    }
                                />
                            </div>
                            <InputError message={form.errors.is_published} />

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    value={form.data.title}
                                    onChange={(event) =>
                                        form.setData(
                                            'title',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.title} />
                            </div>

                            <p className="text-muted-foreground text-sm">
                                Web address:{' '}
                                <span className="text-foreground font-medium">
                                    /{page.slug === 'faqs' ? 'faqs' : page.slug}
                                </span>
                            </p>

                            <Collapsible open={seoOpen} onOpenChange={setSeoOpen}>
                                <CollapsibleTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="justify-between px-0"
                                    >
                                        SEO (optional)
                                        <ChevronDown
                                            className={cn(
                                                'size-4 transition-transform',
                                                seoOpen && 'rotate-180',
                                            )}
                                        />
                                    </Button>
                                </CollapsibleTrigger>
                                <CollapsibleContent className="grid gap-5 pt-3 sm:grid-cols-2">
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="meta_title">
                                            Meta title
                                        </Label>
                                        <Input
                                            id="meta_title"
                                            value={form.data.meta_title}
                                            onChange={(event) =>
                                                form.setData(
                                                    'meta_title',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={form.errors.meta_title}
                                        />
                                    </div>
                                    <div className="flex flex-col gap-2 sm:col-span-2">
                                        <Label htmlFor="meta_description">
                                            Meta description
                                        </Label>
                                        <Textarea
                                            id="meta_description"
                                            rows={3}
                                            value={form.data.meta_description}
                                            onChange={(event) =>
                                                form.setData(
                                                    'meta_description',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                form.errors.meta_description
                                            }
                                        />
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>
                        </div>
                    </section>

                    <CmsPayloadFields
                        slug={page.slug}
                        payload={form.data.payload}
                        setPayload={(next) => form.setData('payload', next)}
                        errors={form.errors as Record<string, string>}
                        media={{
                            heroImage: form.data.hero_image,
                            setHeroImage: (file) => {
                                form.setData('hero_image', file);
                                if (file) {
                                    form.setData('remove_hero_image', false);
                                }
                            },
                            removeHeroImage: form.data.remove_hero_image,
                            setRemoveHeroImage: (value) => {
                                form.setData('remove_hero_image', value);
                                if (value) {
                                    form.setData('hero_image', null);
                                }
                            },
                            mediaImage: form.data.media_image,
                            setMediaImage: (file) => {
                                form.setData('media_image', file);
                                if (file) {
                                    form.setData('remove_media_image', false);
                                }
                            },
                            removeMediaImage: form.data.remove_media_image,
                            setRemoveMediaImage: (value) => {
                                form.setData('remove_media_image', value);
                                if (value) {
                                    form.setData('media_image', null);
                                }
                            },
                        }}
                    />

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing}>
                            Save page
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PlatformPagesEdit.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Pages', href: '/platform/pages' },
        {
            title: `Edit ${props.page.title}`,
            href: `/platform/pages/${props.page.slug}/edit`,
        },
    ],
});
