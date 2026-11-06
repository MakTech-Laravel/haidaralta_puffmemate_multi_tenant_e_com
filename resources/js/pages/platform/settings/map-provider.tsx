import { Head, router, useForm } from '@inertiajs/react';
import MapProviderSettingsController from '@/actions/App/Http/Controllers/Platform/MapProviderSettingsController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { cn } from '@/lib/utils';

const settingsCardClass = cn(
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
    'sm:p-6',
);

type Selection = {
    display_provider: string;
    geocoding_provider: string;
    routing_provider: string;
    fallback_provider: string;
};

type CapabilityRow = {
    value: string;
    label: string;
    field: keyof Selection;
    providers: { key: string; label: string; available: boolean }[];
};

type KeySource = 'dashboard' | 'environment' | 'none';

type ProviderField = {
    name: 'public_key' | 'server_key';
    label: string;
    help: string;
    source: KeySource;
};

type ProviderOption = {
    name: string;
    label: string;
    value: string;
    default: string;
};

type Provider = {
    key: string;
    label: string;
    description: string;
    is_enabled: boolean;
    capabilities: string[];
    fields: ProviderField[];
    options: ProviderOption[];
    daily_limit: number | null;
    monthly_limit: number | null;
    usage: { today: number; month: number };
};

type Props = {
    selection: Selection;
    capabilities: CapabilityRow[];
    providers: Provider[];
};

const sourceLabel: Record<KeySource, string> = {
    dashboard: 'Saved in dashboard',
    environment: 'Using deployment environment value',
    none: 'Not set',
};

function SelectionCard({
    selection,
    capabilities,
}: Pick<Props, 'selection' | 'capabilities'>) {
    const form = useForm<Selection>({ ...selection });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.put(MapProviderSettingsController.updateSelection.url(), {
                    preserveScroll: true,
                });
            }}
        >
            <section className={settingsCardClass}>
                <div className="mb-5 space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Active providers
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Choose which provider serves each feature. Only
                        providers with a saved key for that feature can be
                        selected. If the active one is unavailable or over its
                        limit, the fallback is used automatically.
                    </p>
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                    {capabilities.map((capability) => (
                        <div
                            key={capability.value}
                            className="flex flex-col gap-2"
                        >
                            <Label>{capability.label}</Label>
                            <Select
                                value={form.data[capability.field]}
                                onValueChange={(value) =>
                                    form.setData(capability.field, value)
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {capability.providers.map((provider) => (
                                        <SelectItem
                                            key={provider.key}
                                            value={provider.key}
                                            disabled={!provider.available}
                                        >
                                            {provider.label}
                                            {provider.available
                                                ? ''
                                                : ' (no key saved)'}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={form.errors[capability.field]}
                            />
                        </div>
                    ))}

                    <div className="flex flex-col gap-2">
                        <Label>Fallback provider</Label>
                        <Select
                            value={form.data.fallback_provider}
                            onValueChange={(value) =>
                                form.setData('fallback_provider', value)
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {capabilities[0]?.providers.map((provider) => (
                                    <SelectItem
                                        key={provider.key}
                                        value={provider.key}
                                    >
                                        {provider.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.fallback_provider} />
                    </div>
                </div>

                <div className="mt-5 flex justify-end">
                    <Button type="submit" disabled={form.processing}>
                        Save active providers
                    </Button>
                </div>
            </section>
        </form>
    );
}

function ProviderCard({ provider }: { provider: Provider }) {
    const form = useForm({
        is_enabled: provider.is_enabled,
        public_key: '',
        server_key: '',
        clear_public_key: false,
        clear_server_key: false,
        options: Object.fromEntries(
            provider.options.map((option) => [option.name, option.value]),
        ) as Record<string, string>,
        daily_limit: provider.daily_limit?.toString() ?? '',
        monthly_limit: provider.monthly_limit?.toString() ?? '',
    });

    const errors = form.errors as Record<string, string | undefined>;

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.put(
                    MapProviderSettingsController.updateProvider.url({
                        provider: provider.key,
                    }),
                    {
                        preserveScroll: true,
                        onSuccess: () =>
                            form.setData((data) => ({
                                ...data,
                                public_key: '',
                                server_key: '',
                                clear_public_key: false,
                                clear_server_key: false,
                            })),
                    },
                );
            }}
        >
            <section className={settingsCardClass}>
                <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="text-base font-semibold tracking-tight">
                                {provider.label}
                            </h2>
                            {provider.capabilities.length === 0 ? (
                                <Badge variant="outline">Not configured</Badge>
                            ) : (
                                provider.capabilities.map((capability) => (
                                    <Badge key={capability} variant="secondary">
                                        {capability}
                                    </Badge>
                                ))
                            )}
                        </div>
                        <p className="text-muted-foreground max-w-prose text-sm">
                            {provider.description}
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Label htmlFor={`${provider.key}_enabled`}>
                            Enabled
                        </Label>
                        <Switch
                            id={`${provider.key}_enabled`}
                            checked={form.data.is_enabled}
                            onCheckedChange={(checked) =>
                                form.setData('is_enabled', checked)
                            }
                        />
                    </div>
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                    {provider.fields.map((field) => {
                        const clearName =
                            field.name === 'public_key'
                                ? 'clear_public_key'
                                : 'clear_server_key';

                        return (
                            <div
                                key={field.name}
                                className="flex flex-col gap-2 sm:col-span-2"
                            >
                                <Label
                                    htmlFor={`${provider.key}_${field.name}`}
                                >
                                    {field.label}
                                </Label>
                                <Input
                                    id={`${provider.key}_${field.name}`}
                                    type="password"
                                    autoComplete="off"
                                    placeholder={
                                        field.source === 'none'
                                            ? ''
                                            : 'Leave blank to keep the current key'
                                    }
                                    value={form.data[field.name]}
                                    onChange={(event) =>
                                        form.setData(
                                            field.name,
                                            event.target.value,
                                        )
                                    }
                                />
                                <p className="text-muted-foreground text-xs">
                                    {sourceLabel[field.source]}. {field.help}
                                </p>
                                {field.source === 'dashboard' && (
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id={`${provider.key}_${clearName}`}
                                            checked={form.data[clearName]}
                                            onCheckedChange={(checked) =>
                                                form.setData(
                                                    clearName,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label
                                            htmlFor={`${provider.key}_${clearName}`}
                                            className="text-sm font-normal"
                                        >
                                            Remove the saved key
                                        </Label>
                                    </div>
                                )}
                                <InputError message={errors[field.name]} />
                            </div>
                        );
                    })}

                    {provider.options.map((option) => (
                        <div
                            key={option.name}
                            className="flex flex-col gap-2 sm:col-span-2"
                        >
                            <Label htmlFor={`${provider.key}_${option.name}`}>
                                {option.label}
                            </Label>
                            <Input
                                id={`${provider.key}_${option.name}`}
                                placeholder={option.default}
                                value={form.data.options[option.name] ?? ''}
                                onChange={(event) =>
                                    form.setData('options', {
                                        ...form.data.options,
                                        [option.name]: event.target.value,
                                    })
                                }
                            />
                            <InputError
                                message={errors[`options.${option.name}`]}
                            />
                        </div>
                    ))}

                    <div className="flex flex-col gap-2">
                        <Label htmlFor={`${provider.key}_daily`}>
                            Daily request limit
                        </Label>
                        <Input
                            id={`${provider.key}_daily`}
                            type="number"
                            min={1}
                            placeholder="No limit"
                            value={form.data.daily_limit}
                            onChange={(event) =>
                                form.setData('daily_limit', event.target.value)
                            }
                        />
                        <p className="text-muted-foreground text-xs">
                            Used today: {provider.usage.today}
                        </p>
                        <InputError message={form.errors.daily_limit} />
                    </div>
                    <div className="flex flex-col gap-2">
                        <Label htmlFor={`${provider.key}_monthly`}>
                            Monthly request limit
                        </Label>
                        <Input
                            id={`${provider.key}_monthly`}
                            type="number"
                            min={1}
                            placeholder="No limit"
                            value={form.data.monthly_limit}
                            onChange={(event) =>
                                form.setData(
                                    'monthly_limit',
                                    event.target.value,
                                )
                            }
                        />
                        <p className="text-muted-foreground text-xs">
                            Used this month: {provider.usage.month}
                        </p>
                        <InputError message={form.errors.monthly_limit} />
                    </div>
                </div>

                <p className="text-muted-foreground mt-4 text-xs">
                    Limits count paid server-side requests (address search and
                    routing). Map loads in the browser are billed by the
                    provider and are not counted here.
                </p>

                <div className="mt-5 flex flex-wrap justify-end gap-3">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() =>
                            router.post(
                                MapProviderSettingsController.test.url({
                                    provider: provider.key,
                                }),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Test saved connection
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        Save {provider.label}
                    </Button>
                </div>
            </section>
        </form>
    );
}

export default function PlatformMapProviderSettings({
    selection,
    capabilities,
    providers,
}: Props) {
    return (
        <>
            <Head title="Map provider" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Map provider"
                    description="Choose which map service powers maps, address search and routing, and manage its keys. Keys are stored encrypted and are never shown again."
                />

                <SelectionCard
                    selection={selection}
                    capabilities={capabilities}
                />

                {providers.map((provider) => (
                    <ProviderCard key={provider.key} provider={provider} />
                ))}
            </div>
        </>
    );
}

PlatformMapProviderSettings.layout = {
    breadcrumbs: [
        {
            title: 'Map provider',
            href: '/platform/settings/map-provider',
        },
    ],
};
