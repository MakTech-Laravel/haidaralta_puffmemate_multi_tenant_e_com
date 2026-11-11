import { Head, router, useForm } from '@inertiajs/react';
import { Check, ChevronDown, ExternalLink } from 'lucide-react';
import { useState } from 'react';
import MapProviderSettingsController from '@/actions/App/Http/Controllers/Platform/MapProviderSettingsController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
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
    tagline: string;
    help: {
        url: string;
        url_label: string;
        steps: string[];
    } | null;
    is_enabled: boolean;
    capabilities: string[];
    fields: ProviderField[];
    options: ProviderOption[];
    daily_limit: number | null;
    monthly_limit: number | null;
    usage: {
        today: number;
        month: number;
        today_percent: number | null;
        month_percent: number | null;
    };
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

function UsageMeter({
    label,
    count,
    percent,
}: {
    label: string;
    count: number;
    percent: number | null;
}) {
    return (
        <div className="space-y-1.5">
            <p className="text-muted-foreground text-xs">
                {label}: {count}
                {percent !== null ? ` (${percent}% of limit)` : ''}
            </p>
            {percent !== null ? (
                <Progress
                    value={percent}
                    className={cn(
                        'h-1.5',
                        percent >= 100 && '[&>div]:bg-destructive',
                        percent >= 80 &&
                            percent < 100 &&
                            '[&>div]:bg-primary/50',
                    )}
                />
            ) : null}
        </div>
    );
}

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
                        <UsageMeter
                            label="Used today"
                            count={provider.usage.today}
                            percent={provider.usage.today_percent}
                        />
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
                        <UsageMeter
                            label="Used this month"
                            count={provider.usage.month}
                            percent={provider.usage.month_percent}
                        />
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

type SimpleField = {
    name: 'public_key' | 'server_key';
    label: string;
    hint: string;
};

// Plain-language wording for the keys each service needs (the technical names stay in Advanced).
const SIMPLE_FIELDS: Record<string, SimpleField[]> = {
    mapbox: [
        {
            name: 'public_key',
            label: 'Your Mapbox token',
            hint: 'A long text that starts with "pk."',
        },
    ],
    google: [
        {
            name: 'public_key',
            label: 'Key 1: for the map',
            hint: 'The key you limited to your website address.',
        },
        {
            name: 'server_key',
            label: 'Key 2: for address search and routes',
            hint: 'The key you limited to your server IP address.',
        },
    ],
};

function InUseSummary({
    selection,
    providers,
}: Pick<Props, 'selection' | 'providers'>) {
    const labelOf = (key: string) =>
        providers.find((provider) => provider.key === key)?.label ?? key;
    const routingOk = providers
        .find((provider) => provider.key === selection.routing_provider)
        ?.capabilities.includes('routing');

    const rows = [
        ['Map on your website', labelOf(selection.display_provider)],
        ['Address search', labelOf(selection.geocoding_provider)],
        [
            'Road routes for drivers',
            routingOk
                ? labelOf(selection.routing_provider)
                : 'Not set up (drivers see straight-line distance)',
        ],
    ];

    return (
        <section className={settingsCardClass}>
            <h2 className="text-base font-semibold tracking-tight">
                What is being used right now
            </h2>
            <dl className="mt-3 grid gap-3 sm:grid-cols-3">
                {rows.map(([label, value]) => (
                    <div key={label} className="rounded-xl border p-3">
                        <dt className="text-muted-foreground text-xs">
                            {label}
                        </dt>
                        <dd className="mt-0.5 text-sm font-medium">{value}</dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}

function SetupPanel({ provider }: { provider: Provider }) {
    const needsKey = provider.fields.length > 0;
    const fields: SimpleField[] =
        SIMPLE_FIELDS[provider.key] ??
        provider.fields.map((field) => ({
            name: field.name,
            label: field.label,
            hint: field.help,
        }));
    const form = useForm({
        provider: provider.key,
        public_key: '',
        server_key: '',
        daily_limit: provider.daily_limit?.toString() ?? '',
    });
    const errors = form.errors as Record<string, string | undefined>;
    const saved = (name: SimpleField['name']) => {
        const source = provider.fields.find(
            (field) => field.name === name,
        )?.source;

        return source !== undefined && source !== 'none';
    };

    return (
        <form
            className={settingsCardClass}
            onSubmit={(event) => {
                event.preventDefault();
                form.post(MapProviderSettingsController.activate.url(), {
                    preserveScroll: true,
                    onSuccess: () => form.reset('public_key', 'server_key'),
                });
            }}
        >
            <h2 className="text-base font-semibold tracking-tight">
                {needsKey
                    ? `Step 2. Set up ${provider.label}`
                    : 'Step 2. Confirm'}
            </h2>

            {provider.help ? (
                <Collapsible defaultOpen={provider.capabilities.length === 0}>
                    <CollapsibleTrigger className="text-primary mt-3 flex items-center gap-1.5 text-sm font-medium">
                        <ChevronDown className="size-4" aria-hidden />
                        Where do I get my key?
                    </CollapsibleTrigger>
                    <CollapsibleContent className="bg-muted/40 mt-3 space-y-3 rounded-xl p-4">
                        <ol className="list-decimal space-y-1.5 pl-5 text-sm">
                            {provider.help.steps.map((step) => (
                                <li key={step}>{step}</li>
                            ))}
                        </ol>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            asChild
                        >
                            <a
                                href={provider.help.url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <ExternalLink data-icon="inline-start" />
                                {provider.help.url_label}
                            </a>
                        </Button>
                    </CollapsibleContent>
                </Collapsible>
            ) : (
                <p className="text-muted-foreground mt-2 text-sm">
                    OpenStreetMap is free and needs no key. It is a good choice
                    to start with. Drivers will see straight-line distances
                    instead of road routes.
                </p>
            )}

            {needsKey ? (
                <div className="mt-4 grid gap-4">
                    {fields.map((field) => (
                        <div key={field.name} className="flex flex-col gap-1.5">
                            <Label
                                htmlFor={`simple_${provider.key}_${field.name}`}
                            >
                                {field.label}
                            </Label>
                            <Input
                                id={`simple_${provider.key}_${field.name}`}
                                type="password"
                                autoComplete="off"
                                placeholder={
                                    saved(field.name)
                                        ? 'Already saved. Leave empty to keep it.'
                                        : 'Paste your key here'
                                }
                                value={form.data[field.name]}
                                onChange={(event) =>
                                    form.setData(field.name, event.target.value)
                                }
                            />
                            <p className="text-muted-foreground text-xs">
                                {field.hint}
                            </p>
                            <InputError message={errors[field.name]} />
                        </div>
                    ))}

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`simple_${provider.key}_limit`}>
                            Daily safety limit (optional)
                        </Label>
                        <Input
                            id={`simple_${provider.key}_limit`}
                            type="number"
                            min={1}
                            placeholder="No limit"
                            value={form.data.daily_limit}
                            onChange={(event) =>
                                form.setData('daily_limit', event.target.value)
                            }
                        />
                        <p className="text-muted-foreground text-xs">
                            To control your bill: after this many address
                            searches or routes in one day, the free
                            OpenStreetMap takes over and you get a notification.
                            Used today: {provider.usage.today}.
                        </p>
                        <InputError message={errors.daily_limit} />
                    </div>
                </div>
            ) : null}

            <div className="mt-5 flex flex-wrap items-center gap-3">
                <Button type="submit" disabled={form.processing}>
                    {form.processing
                        ? 'Checking...'
                        : needsKey
                          ? `Save and use ${provider.label}`
                          : `Use ${provider.label}`}
                </Button>
                {needsKey ? (
                    <span className="text-muted-foreground text-xs">
                        We check the key first. If it does not work, nothing
                        changes.
                    </span>
                ) : null}
            </div>
        </form>
    );
}

function SimpleSetup({
    selection,
    providers,
}: Pick<Props, 'selection' | 'providers'>) {
    const [chosen, setChosen] = useState(selection.display_provider);
    const provider =
        providers.find((candidate) => candidate.key === chosen) ?? providers[0];

    return (
        <div className="flex flex-col gap-4">
            <InUseSummary selection={selection} providers={providers} />

            <section className={settingsCardClass}>
                <h2 className="text-base font-semibold tracking-tight">
                    Step 1. Choose your map service
                </h2>
                <p className="text-muted-foreground mt-1 text-sm">
                    You can change this any time. Your website and driver app
                    switch automatically.
                </p>

                <div
                    role="radiogroup"
                    aria-label="Map service"
                    className="mt-4 grid gap-3 sm:grid-cols-3"
                >
                    {providers.map((candidate) => {
                        const inUse =
                            selection.display_provider === candidate.key;
                        const ready = candidate.capabilities.length > 0;

                        return (
                            <button
                                key={candidate.key}
                                type="button"
                                role="radio"
                                aria-checked={chosen === candidate.key}
                                onClick={() => setChosen(candidate.key)}
                                className={cn(
                                    'hover:bg-muted/40 flex flex-col gap-2 rounded-xl border p-4 text-left transition-colors',
                                    chosen === candidate.key &&
                                        'border-primary ring-primary/30 ring-2',
                                )}
                            >
                                <span className="flex items-center justify-between gap-2">
                                    <span className="font-semibold">
                                        {candidate.label}
                                    </span>
                                    {inUse ? (
                                        <Badge className="gap-1">
                                            <Check
                                                className="size-3"
                                                aria-hidden
                                            />
                                            In use
                                        </Badge>
                                    ) : ready ? (
                                        <Badge variant="secondary">
                                            {candidate.fields.length === 0
                                                ? 'Free'
                                                : 'Key saved'}
                                        </Badge>
                                    ) : (
                                        <Badge variant="outline">
                                            Needs a key
                                        </Badge>
                                    )}
                                </span>
                                <span className="text-muted-foreground text-sm">
                                    {candidate.tagline}
                                </span>
                            </button>
                        );
                    })}
                </div>
            </section>

            <SetupPanel key={provider.key} provider={provider} />
        </div>
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
                    title="Map service"
                    description="Pick the map service your website and driver app use. Two steps: choose one, then paste its key. Keys are stored encrypted and are never shown again."
                />

                <SimpleSetup selection={selection} providers={providers} />

                <Collapsible>
                    <CollapsibleTrigger className="text-muted-foreground hover:text-foreground flex items-center gap-1.5 text-sm font-medium">
                        <ChevronDown className="size-4" aria-hidden />
                        Advanced settings (for developers)
                    </CollapsibleTrigger>
                    <CollapsibleContent className="mt-4 flex flex-col gap-4">
                        <SelectionCard
                            selection={selection}
                            capabilities={capabilities}
                        />

                        {providers.map((provider) => (
                            <ProviderCard
                                key={provider.key}
                                provider={provider}
                            />
                        ))}
                    </CollapsibleContent>
                </Collapsible>
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
