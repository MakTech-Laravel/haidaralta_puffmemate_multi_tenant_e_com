import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import GeneralSettingsController from '@/actions/App/Http/Controllers/Platform/GeneralSettingsController';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Badge } from '@/components/ui/badge';
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
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

const IMAGE_ACCEPT =
    'image/png,image/jpeg,image/webp,image/svg+xml,.png,.jpg,.jpeg,.webp,.svg';
const FAVICON_ACCEPT =
    'image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon,.ico,.png,.jpg,.jpeg,.webp,.svg';

const settingsCardClass = cn(
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
    'sm:p-6',
);

type Settings = {
    app_name: string;
    app_short_name: string;
    short_description: string | null;
    theme_color: string;
    background_color: string;
    display_timezone: string;
    date_format: string;
    time_format: string;
    logo_path: string | null;
    logo_url: string | null;
    favicon_path: string | null;
    favicon_url: string | null;
    pwa_icon_192_path: string | null;
    pwa_icon_192_url: string | null;
    pwa_icon_512_path: string | null;
    pwa_icon_512_url: string | null;
    pwa_maskable_path: string | null;
    pwa_maskable_url: string | null;
    apple_touch_icon_path: string | null;
    apple_touch_icon_url: string | null;
};

type Runtime = {
    environment: string;
    debug: boolean;
    php_version: string;
    laravel_version: string;
    maintenance_mode: boolean;
};

type Props = {
    settings: Settings;
    dateFormatPresets: string[];
    timeFormatPresets: string[];
    timezones: string[];
    runtime: Runtime;
};

type MediaKey =
    | 'logo'
    | 'favicon'
    | 'pwa_icon_192'
    | 'pwa_icon_512'
    | 'pwa_maskable'
    | 'apple_touch_icon';

type RemoveKey =
    | 'remove_logo'
    | 'remove_favicon'
    | 'remove_pwa_icon_192'
    | 'remove_pwa_icon_512'
    | 'remove_pwa_maskable'
    | 'remove_apple_touch_icon';

function toExistingFiles(
    id: string,
    path: string | null | undefined,
    url: string | null | undefined,
    fallbackName: string,
): ExistingFile[] {
    if (!path || !url) {
        return [];
    }

    return [
        {
            id,
            path,
            url,
            mime_type: 'image/png',
            name: path.split('/').pop() ?? fallbackName,
        },
    ];
}

export default function PlatformGeneralSettings({
    settings,
    dateFormatPresets,
    timeFormatPresets,
    timezones,
    runtime,
}: Props) {
    const form = useForm({
        app_name: settings.app_name,
        app_short_name: settings.app_short_name,
        short_description: settings.short_description ?? '',
        theme_color: settings.theme_color,
        background_color: settings.background_color,
        display_timezone: settings.display_timezone,
        date_format: settings.date_format,
        time_format: settings.time_format,
        logo: null as File | null,
        favicon: null as File | null,
        pwa_icon_192: null as File | null,
        pwa_icon_512: null as File | null,
        pwa_maskable: null as File | null,
        apple_touch_icon: null as File | null,
        remove_logo: false,
        remove_favicon: false,
        remove_pwa_icon_192: false,
        remove_pwa_icon_512: false,
        remove_pwa_maskable: false,
        remove_apple_touch_icon: false,
        maintenance_mode: runtime.maintenance_mode,
    });

    const [existingLogo, setExistingLogo] = useState<ExistingFile[]>(() =>
        toExistingFiles('logo', settings.logo_path, settings.logo_url, 'logo'),
    );
    const [existingFavicon, setExistingFavicon] = useState<ExistingFile[]>(
        () =>
            toExistingFiles(
                'favicon',
                settings.favicon_path,
                settings.favicon_url,
                'favicon',
            ),
    );
    const [existingPwa192, setExistingPwa192] = useState<ExistingFile[]>(() =>
        toExistingFiles(
            'pwa_icon_192',
            settings.pwa_icon_192_path,
            settings.pwa_icon_192_url,
            'pwa-192',
        ),
    );
    const [existingPwa512, setExistingPwa512] = useState<ExistingFile[]>(() =>
        toExistingFiles(
            'pwa_icon_512',
            settings.pwa_icon_512_path,
            settings.pwa_icon_512_url,
            'pwa-512',
        ),
    );
    const [existingMaskable, setExistingMaskable] = useState<ExistingFile[]>(
        () =>
            toExistingFiles(
                'pwa_maskable',
                settings.pwa_maskable_path,
                settings.pwa_maskable_url,
                'pwa-maskable',
            ),
    );
    const [existingAppleTouch, setExistingAppleTouch] = useState<
        ExistingFile[]
    >(() =>
        toExistingFiles(
            'apple_touch_icon',
            settings.apple_touch_icon_path,
            settings.apple_touch_icon_url,
            'apple-touch-icon',
        ),
    );

    const timezoneOptions = useMemo(
        () =>
            timezones.map((zone) => ({
                value: zone,
                label: zone,
            })),
        [timezones],
    );

    const setMediaFile = (
        key: MediaKey,
        removeKey: RemoveKey,
        setExisting: (files: ExistingFile[]) => void,
        file: File | File[] | null,
    ) => {
        const next = Array.isArray(file) ? (file[0] ?? null) : file;
        setExisting([]);
        form.setData((current) => ({
            ...current,
            [key]: next,
            [removeKey]: next ? false : current[removeKey],
        }));
    };

    const removeMediaFile = (
        key: MediaKey,
        removeKey: RemoveKey,
        setExisting: (files: ExistingFile[]) => void,
    ) => {
        setExisting([]);
        form.setData((current) => ({
            ...current,
            [key]: null,
            [removeKey]: true,
        }));
    };

    return (
        <>
            <Head title="General settings" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="General"
                    description="Brand identity, display timezone, formats, PWA assets, and maintenance mode."
                />

                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put(GeneralSettingsController.update.url(), {
                            forceFormData: true,
                            preserveScroll: true,
                        });
                    }}
                >
                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Branding
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                App name, description, logo, and favicon shown
                                across portals.
                            </p>
                        </div>

                        <div className="flex flex-col gap-5">
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="app_name">App name</Label>
                                    <Input
                                        id="app_name"
                                        value={form.data.app_name}
                                        onChange={(event) =>
                                            form.setData(
                                                'app_name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.app_name}
                                    />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="app_short_name">
                                        Short name
                                    </Label>
                                    <Input
                                        id="app_short_name"
                                        value={form.data.app_short_name}
                                        onChange={(event) =>
                                            form.setData(
                                                'app_short_name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.app_short_name}
                                    />
                                </div>
                                <div className="flex flex-col gap-2 sm:col-span-2">
                                    <Label htmlFor="short_description">
                                        Short description
                                    </Label>
                                    <Textarea
                                        id="short_description"
                                        value={form.data.short_description}
                                        onChange={(event) =>
                                            form.setData(
                                                'short_description',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.short_description}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <SettingsAssetUpload
                                    label="Logo"
                                    hint="PNG, JPG, WebP, or SVG · Max 2MB"
                                    accept={IMAGE_ACCEPT}
                                    maxSize={2}
                                    value={form.data.logo}
                                    existingFiles={
                                        form.data.logo || form.data.remove_logo
                                            ? []
                                            : existingLogo
                                    }
                                    error={form.errors.logo}
                                    isUploading={form.processing}
                                    uploadProgress={
                                        form.progress?.percentage ?? null
                                    }
                                    onCancel={
                                        form.processing
                                            ? form.cancel
                                            : undefined
                                    }
                                    onChange={(file) =>
                                        setMediaFile(
                                            'logo',
                                            'remove_logo',
                                            setExistingLogo,
                                            file,
                                        )
                                    }
                                    onRemoveExisting={() =>
                                        removeMediaFile(
                                            'logo',
                                            'remove_logo',
                                            setExistingLogo,
                                        )
                                    }
                                />
                                <SettingsAssetUpload
                                    label="Favicon"
                                    hint="ICO, PNG, SVG, JPG, or WebP · Max 1MB"
                                    accept={FAVICON_ACCEPT}
                                    maxSize={1}
                                    value={form.data.favicon}
                                    existingFiles={
                                        form.data.favicon ||
                                        form.data.remove_favicon
                                            ? []
                                            : existingFavicon
                                    }
                                    error={form.errors.favicon}
                                    isUploading={form.processing}
                                    uploadProgress={
                                        form.progress?.percentage ?? null
                                    }
                                    onCancel={
                                        form.processing
                                            ? form.cancel
                                            : undefined
                                    }
                                    onChange={(file) =>
                                        setMediaFile(
                                            'favicon',
                                            'remove_favicon',
                                            setExistingFavicon,
                                            file,
                                        )
                                    }
                                    onRemoveExisting={() =>
                                        removeMediaFile(
                                            'favicon',
                                            'remove_favicon',
                                            setExistingFavicon,
                                        )
                                    }
                                />
                            </div>
                        </div>
                    </section>

                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Timezone and formats
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Controls how dates and times appear across the
                                app.
                            </p>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-2 sm:col-span-2">
                                <Label htmlFor="display_timezone">
                                    Display timezone
                                </Label>
                                <InfiniteScrollCombobox
                                    id="display_timezone"
                                    staticOptions={timezoneOptions}
                                    value={form.data.display_timezone}
                                    onValueChange={(value) => {
                                        if (value) {
                                            form.setData(
                                                'display_timezone',
                                                value,
                                            );
                                        }
                                    }}
                                    selectedLabel={form.data.display_timezone}
                                    placeholder="Select timezone"
                                    searchPlaceholder="Search timezones…"
                                    emptyText="No timezones match."
                                />
                                <InputError
                                    message={form.errors.display_timezone}
                                />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label>Date format</Label>
                                <Select
                                    value={form.data.date_format}
                                    onValueChange={(value) =>
                                        form.setData('date_format', value)
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {dateFormatPresets.map((preset) => (
                                            <SelectItem
                                                key={preset}
                                                value={preset}
                                            >
                                                {preset}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={form.errors.date_format}
                                />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label>Time format</Label>
                                <Select
                                    value={form.data.time_format}
                                    onValueChange={(value) =>
                                        form.setData('time_format', value)
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {timeFormatPresets.map((preset) => (
                                            <SelectItem
                                                key={preset}
                                                value={preset}
                                            >
                                                {preset}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={form.errors.time_format}
                                />
                            </div>
                        </div>
                    </section>

                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                PWA
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Theme colors and install icons for progressive
                                web app installs.
                            </p>
                        </div>

                        <div className="flex flex-col gap-5">
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="theme_color">
                                        Theme color
                                    </Label>
                                    <Input
                                        id="theme_color"
                                        type="color"
                                        value={form.data.theme_color}
                                        onChange={(event) =>
                                            form.setData(
                                                'theme_color',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.theme_color}
                                    />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="background_color">
                                        Background color
                                    </Label>
                                    <Input
                                        id="background_color"
                                        type="color"
                                        value={form.data.background_color}
                                        onChange={(event) =>
                                            form.setData(
                                                'background_color',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.background_color}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                                <SettingsAssetUpload
                                    label="PWA 192×192"
                                    hint="PNG · Max 2MB"
                                    accept={IMAGE_ACCEPT}
                                    maxSize={2}
                                    value={form.data.pwa_icon_192}
                                    existingFiles={
                                        form.data.pwa_icon_192 ||
                                        form.data.remove_pwa_icon_192
                                            ? []
                                            : existingPwa192
                                    }
                                    error={form.errors.pwa_icon_192}
                                    isUploading={form.processing}
                                    uploadProgress={
                                        form.progress?.percentage ?? null
                                    }
                                    onCancel={
                                        form.processing
                                            ? form.cancel
                                            : undefined
                                    }
                                    onChange={(file) =>
                                        setMediaFile(
                                            'pwa_icon_192',
                                            'remove_pwa_icon_192',
                                            setExistingPwa192,
                                            file,
                                        )
                                    }
                                    onRemoveExisting={() =>
                                        removeMediaFile(
                                            'pwa_icon_192',
                                            'remove_pwa_icon_192',
                                            setExistingPwa192,
                                        )
                                    }
                                />
                                <SettingsAssetUpload
                                    label="PWA 512×512"
                                    hint="PNG · Max 4MB"
                                    accept={IMAGE_ACCEPT}
                                    maxSize={4}
                                    value={form.data.pwa_icon_512}
                                    existingFiles={
                                        form.data.pwa_icon_512 ||
                                        form.data.remove_pwa_icon_512
                                            ? []
                                            : existingPwa512
                                    }
                                    error={form.errors.pwa_icon_512}
                                    isUploading={form.processing}
                                    uploadProgress={
                                        form.progress?.percentage ?? null
                                    }
                                    onCancel={
                                        form.processing
                                            ? form.cancel
                                            : undefined
                                    }
                                    onChange={(file) =>
                                        setMediaFile(
                                            'pwa_icon_512',
                                            'remove_pwa_icon_512',
                                            setExistingPwa512,
                                            file,
                                        )
                                    }
                                    onRemoveExisting={() =>
                                        removeMediaFile(
                                            'pwa_icon_512',
                                            'remove_pwa_icon_512',
                                            setExistingPwa512,
                                        )
                                    }
                                />
                                <SettingsAssetUpload
                                    label="Maskable 512"
                                    hint="PNG · Max 4MB"
                                    accept={IMAGE_ACCEPT}
                                    maxSize={4}
                                    value={form.data.pwa_maskable}
                                    existingFiles={
                                        form.data.pwa_maskable ||
                                        form.data.remove_pwa_maskable
                                            ? []
                                            : existingMaskable
                                    }
                                    error={form.errors.pwa_maskable}
                                    isUploading={form.processing}
                                    uploadProgress={
                                        form.progress?.percentage ?? null
                                    }
                                    onCancel={
                                        form.processing
                                            ? form.cancel
                                            : undefined
                                    }
                                    onChange={(file) =>
                                        setMediaFile(
                                            'pwa_maskable',
                                            'remove_pwa_maskable',
                                            setExistingMaskable,
                                            file,
                                        )
                                    }
                                    onRemoveExisting={() =>
                                        removeMediaFile(
                                            'pwa_maskable',
                                            'remove_pwa_maskable',
                                            setExistingMaskable,
                                        )
                                    }
                                />
                                <SettingsAssetUpload
                                    label="Apple touch icon"
                                    hint="180×180 PNG · Max 2MB"
                                    accept={IMAGE_ACCEPT}
                                    maxSize={2}
                                    value={form.data.apple_touch_icon}
                                    existingFiles={
                                        form.data.apple_touch_icon ||
                                        form.data.remove_apple_touch_icon
                                            ? []
                                            : existingAppleTouch
                                    }
                                    error={form.errors.apple_touch_icon}
                                    isUploading={form.processing}
                                    uploadProgress={
                                        form.progress?.percentage ?? null
                                    }
                                    onCancel={
                                        form.processing
                                            ? form.cancel
                                            : undefined
                                    }
                                    onChange={(file) =>
                                        setMediaFile(
                                            'apple_touch_icon',
                                            'remove_apple_touch_icon',
                                            setExistingAppleTouch,
                                            file,
                                        )
                                    }
                                    onRemoveExisting={() =>
                                        removeMediaFile(
                                            'apple_touch_icon',
                                            'remove_apple_touch_icon',
                                            setExistingAppleTouch,
                                        )
                                    }
                                />
                            </div>
                        </div>
                    </section>

                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Runtime status
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Environment and debug flags are read-only.
                                Change them in deployment config.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">
                                Env: {runtime.environment}
                            </Badge>
                            <Badge variant="secondary">
                                Debug: {runtime.debug ? 'on' : 'off'}
                            </Badge>
                            <Badge variant="secondary">
                                PHP {runtime.php_version}
                            </Badge>
                            <Badge variant="secondary">
                                Laravel {runtime.laravel_version}
                            </Badge>
                        </div>
                    </section>

                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Operations
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Platform routes stay available so you can turn
                                maintenance off.
                            </p>
                        </div>
                        <div className="flex items-center justify-between gap-4 rounded-xl border p-4">
                            <div>
                                <Label htmlFor="maintenance_mode">
                                    Maintenance mode
                                </Label>
                                <p className="text-muted-foreground text-sm">
                                    When enabled, storefront and customer areas
                                    show a 503 page.
                                </p>
                            </div>
                            <Switch
                                id="maintenance_mode"
                                checked={form.data.maintenance_mode}
                                onCheckedChange={(checked) =>
                                    form.setData('maintenance_mode', checked)
                                }
                            />
                        </div>
                        <InputError message={form.errors.maintenance_mode} />
                    </section>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing}>
                            Save general settings
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

function SettingsAssetUpload({
    label,
    hint,
    accept,
    maxSize,
    value,
    existingFiles,
    error,
    isUploading,
    uploadProgress,
    onCancel,
    onChange,
    onRemoveExisting,
}: {
    label: string;
    hint: string;
    accept: string;
    maxSize: number;
    value: File | null;
    existingFiles: ExistingFile[];
    error?: string;
    isUploading: boolean;
    uploadProgress: number | null;
    onCancel?: () => void;
    onChange: (file: File | File[] | null) => void;
    onRemoveExisting: () => void;
}) {
    return (
        <div className="flex flex-col gap-1.5">
            <Label>{label}</Label>
            <div className="min-h-40">
                <FileUpload
                    value={value}
                    onChange={onChange}
                    existingFiles={existingFiles}
                    onRemoveExisting={onRemoveExisting}
                    accept={accept}
                    maxSize={maxSize}
                    maxFiles={1}
                    multiple={false}
                    lightbox
                    compactCards
                    isUploading={isUploading}
                    uploadProgress={uploadProgress}
                    onCancel={onCancel}
                    placeholder="Drop or click"
                    hint={hint}
                    error={error}
                    classNames={{
                        wrapper: 'flex h-full min-h-40 w-full flex-col',
                        dropzone:
                            'flex h-full min-h-40 w-full flex-1 border-dashed border-border/60 bg-muted/30 px-3 py-4',
                        dropzoneContent: 'gap-1.5',
                        card: 'flex h-full w-full flex-col overflow-hidden border-0 bg-transparent shadow-none hover:shadow-none',
                        cardMedia:
                            'aspect-auto min-h-40 flex-1 bg-transparent',
                        cardImage: 'size-full object-contain object-center',
                    }}
                />
            </div>
        </div>
    );
}

PlatformGeneralSettings.layout = {
    breadcrumbs: [
        {
            title: 'General',
            href: '/platform/settings/general',
        },
    ],
};
