import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import ContactSettingsController from '@/actions/App/Http/Controllers/Platform/ContactSettingsController';
import Heading from '@/components/heading';
import { LucideIconPicker } from '@/components/icons/lucide-icon-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

const settingsCardClass = cn(
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
    'sm:p-6',
);

const SOCIAL_NETWORKS = [
    { key: 'instagram', label: 'Instagram', icon: 'Instagram' },
    { key: 'twitter', label: 'Twitter / X', icon: 'Twitter' },
    { key: 'facebook', label: 'Facebook', icon: 'Facebook' },
    { key: 'youtube', label: 'YouTube', icon: 'Youtube' },
    { key: 'linkedin', label: 'LinkedIn', icon: 'Linkedin' },
    { key: 'discord', label: 'Discord', icon: 'MessageCircle' },
] as const;

type Social = {
    key: string;
    label: string;
    url: string;
    icon: string;
};

type Settings = {
    phone: string | null;
    fax: string | null;
    email: string | null;
    whatsapp: string | null;
    address: string | null;
    map_embed_url: string | null;
    socials: Array<{
        key: string;
        label: string;
        url: string;
        icon?: string | null;
    }>;
};

type Props = {
    settings: Settings;
};

function defaultsForKey(key: string): { label: string; icon: string } {
    const network = SOCIAL_NETWORKS.find((item) => item.key === key);

    return {
        label: network?.label ?? key,
        icon: network?.icon ?? 'Share2',
    };
}

function toSocialRows(
    socials: Settings['socials'],
): Social[] {
    if (socials.length === 0) {
        return [
            {
                key: 'instagram',
                label: 'Instagram',
                url: '',
                icon: 'Instagram',
            },
        ];
    }

    return socials.map((social) => {
        const defaults = defaultsForKey(social.key || 'instagram');

        return {
            key: social.key || 'instagram',
            label: social.label || defaults.label,
            url: social.url ?? '',
            icon: social.icon || defaults.icon,
        };
    });
}

export default function PlatformContactSettings({ settings }: Props) {
    const form = useForm({
        phone: settings.phone ?? '',
        fax: settings.fax ?? '',
        email: settings.email ?? '',
        whatsapp: settings.whatsapp ?? '',
        address: settings.address ?? '',
        map_embed_url: settings.map_embed_url ?? '',
        socials: toSocialRows(settings.socials),
    });

    const updateSocial = (index: number, patch: Partial<Social>) => {
        const next = form.data.socials.map((social, i) => {
            if (i !== index) {
                return social;
            }

            const merged = { ...social, ...patch };

            if (patch.key !== undefined) {
                const defaults = defaultsForKey(patch.key);
                merged.label = defaults.label;
                if (!patch.icon) {
                    merged.icon = defaults.icon;
                }
            }

            return merged;
        });
        form.setData('socials', next);
    };

    return (
        <>
            <Head title="Contact settings" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Contacts"
                    description="Phone, email, address, and social links used across the website."
                />

                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put(ContactSettingsController.update.url(), {
                            preserveScroll: true,
                        });
                    }}
                >
                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Primary contacts
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Shown on Contact, Wholesale, and the footer.
                            </p>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    value={form.data.phone}
                                    onChange={(event) =>
                                        form.setData(
                                            'phone',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.phone} />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="fax">Fax</Label>
                                <Input
                                    id="fax"
                                    value={form.data.fax}
                                    onChange={(event) =>
                                        form.setData('fax', event.target.value)
                                    }
                                />
                                <InputError message={form.errors.fax} />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) =>
                                        form.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.email} />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="whatsapp">WhatsApp</Label>
                                <Input
                                    id="whatsapp"
                                    value={form.data.whatsapp}
                                    onChange={(event) =>
                                        form.setData(
                                            'whatsapp',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.whatsapp} />
                            </div>
                            <div className="flex flex-col gap-2 sm:col-span-2">
                                <Label htmlFor="address">Address</Label>
                                <Textarea
                                    id="address"
                                    rows={3}
                                    value={form.data.address}
                                    onChange={(event) =>
                                        form.setData(
                                            'address',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.address} />
                            </div>
                            <div className="flex flex-col gap-2 sm:col-span-2">
                                <Label htmlFor="map_embed_url">
                                    Map link (optional)
                                </Label>
                                <Input
                                    id="map_embed_url"
                                    value={form.data.map_embed_url}
                                    onChange={(event) =>
                                        form.setData(
                                            'map_embed_url',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Paste a Google Maps link"
                                />
                                <p className="text-muted-foreground text-xs">
                                    Optional. Paste a map share link if you
                                    want to show a map later.
                                </p>
                                <InputError
                                    message={form.errors.map_embed_url}
                                />
                            </div>
                        </div>
                    </section>

                    <section className={settingsCardClass}>
                        <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div className="space-y-1">
                                <h2 className="text-base font-semibold tracking-tight">
                                    Social links
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    Pick a network, choose an icon, and paste
                                    the profile URL.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    form.setData('socials', [
                                        ...form.data.socials,
                                        {
                                            key: 'instagram',
                                            label: 'Instagram',
                                            url: '',
                                            icon: 'Instagram',
                                        },
                                    ])
                                }
                            >
                                <Plus className="size-4" />
                                Add link
                            </Button>
                        </div>

                        <div className="space-y-4">
                            {form.data.socials.map((social, index) => (
                                <div
                                    key={`social-${index}`}
                                    className="grid gap-3 rounded-xl border p-4 sm:grid-cols-[minmax(0,12rem)_auto_minmax(0,1fr)_auto]"
                                >
                                    <div className="flex min-w-0 flex-col gap-2">
                                        <Label>Network</Label>
                                        <Select
                                            value={social.key || 'instagram'}
                                            onValueChange={(value) =>
                                                updateSocial(index, {
                                                    key: value,
                                                })
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Choose network" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {SOCIAL_NETWORKS.map(
                                                    (network) => (
                                                        <SelectItem
                                                            key={network.key}
                                                            value={network.key}
                                                        >
                                                            {network.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <Label>Icon</Label>
                                        <LucideIconPicker
                                            mode="dialog"
                                            triggerVariant="compact"
                                            value={social.icon || 'Share2'}
                                            onChange={(iconKey) =>
                                                updateSocial(index, {
                                                    icon: iconKey,
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="flex min-w-0 flex-col gap-2">
                                        <Label>Profile URL</Label>
                                        <Input
                                            value={social.url}
                                            placeholder="https://"
                                            onChange={(event) =>
                                                updateSocial(index, {
                                                    url: event.target.value,
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="flex items-end">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Remove social link"
                                            disabled={
                                                form.data.socials.length <= 1
                                            }
                                            onClick={() =>
                                                form.setData(
                                                    'socials',
                                                    form.data.socials.filter(
                                                        (_, i) => i !== index,
                                                    ),
                                                )
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                            <InputError message={form.errors.socials} />
                        </div>
                    </section>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing}>
                            Save contacts
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PlatformContactSettings.layout = {
    breadcrumbs: [
        {
            title: 'Contacts',
            href: '/platform/settings/contacts',
        },
    ],
};
