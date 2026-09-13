import { Check, Droplets, Layers2, Sparkles } from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    CATALOG_COLOR_PRESETS,
    CATALOG_GRADIENT_ANGLES,
    CATALOG_GRADIENT_QUICK_SELECT,
    CATALOG_QUICK_SELECT,
    catalogBackgroundStyle,
    isLightCatalogHex,
    isTransparentCatalogColor,
    normalizeCatalogColorCode,
    type CatalogColorCode,
    type CatalogColorMode,
} from '@/lib/catalog-background';
import { cn } from '@/lib/utils';

type CatalogBackgroundColorFieldProps = {
    id?: string;
    value?: CatalogColorCode | null;
    onChange: (value: CatalogColorCode) => void;
    error?: string;
    className?: string;
};

const MODE_OPTIONS: {
    value: CatalogColorMode;
    label: string;
    description: string;
    icon: typeof Droplets;
}[] = [
    {
        value: 'transparent',
        label: 'None',
        description: 'Default muted tile',
        icon: Layers2,
    },
    {
        value: 'solid',
        label: 'Solid',
        description: 'Single fill color',
        icon: Droplets,
    },
    {
        value: 'gradient',
        label: 'Gradient',
        description: 'Two-tone wash',
        icon: Sparkles,
    },
];

const CHECKERBOARD =
    'bg-[linear-gradient(45deg,#d4d4d8_25%,transparent_25%),linear-gradient(-45deg,#d4d4d8_25%,transparent_25%),linear-gradient(45deg,transparent_75%,#d4d4d8_75%),linear-gradient(-45deg,transparent_75%,#d4d4d8_75%)] bg-[length:10px_10px] bg-[position:0_0,0_5px,5px_-5px,-5px_0px] bg-white';

function isValidHex(value: string): boolean {
    return /^#[0-9A-F]{6}$/.test(value);
}

function modeSummary(value: CatalogColorCode): string {
    if (value.mode === 'transparent') {
        return 'Transparent';
    }

    if (value.mode === 'solid') {
        return `Solid · ${value.color}`;
    }

    return `Gradient · ${value.angle}°`;
}

function ColorSwatchPicker({
    label,
    value,
    onChange,
}: {
    label: string;
    value: string;
    onChange: (hex: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const hex = value.toUpperCase();
    const safeHex = isValidHex(hex) ? hex : '#E5E7EB';

    const commitHex = (raw: string) => {
        const next = raw
            .toUpperCase()
            .replace(/[^#0-9A-F]/g, '')
            .slice(0, 7);
        onChange(next.startsWith('#') ? next : `#${next}`);
    };

    return (
        <div className="min-w-0 space-y-2">
            <div className="flex items-center justify-between gap-2">
                <span className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                    {label}
                </span>
                <span className="text-muted-foreground/80 font-mono text-[11px]">
                    {isValidHex(hex) ? hex : '—'}
                </span>
            </div>

            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <button
                        type="button"
                        className={cn(
                            'group border-border/80 bg-background hover:border-border focus-visible:ring-ring',
                            'flex w-full items-center gap-3 rounded-xl border px-3 py-2.5 text-left shadow-xs transition-all',
                            'focus-visible:ring-2 focus-visible:outline-none',
                        )}
                        aria-label={`Edit ${label} color`}
                    >
                        <span
                            className="ring-border/60 relative size-9 shrink-0 overflow-hidden rounded-full shadow-inner ring-1"
                            style={{ background: safeHex }}
                        >
                            <span className="absolute inset-0 bg-gradient-to-br from-white/35 to-transparent opacity-80" />
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="block truncate text-sm font-medium">
                                {label}
                            </span>
                            <span className="text-muted-foreground block font-mono text-xs tracking-wide">
                                {safeHex}
                            </span>
                        </span>
                        <span className="text-muted-foreground group-hover:text-foreground text-xs font-medium transition-colors">
                            Edit
                        </span>
                    </button>
                </PopoverTrigger>

                <PopoverContent
                    align="start"
                    className="z-60 w-[min(100vw-2rem,20rem)] overflow-hidden rounded-2xl border-border/80 p-0 shadow-lg"
                >
                    <div
                        className={cn('relative h-28', CHECKERBOARD)}
                        style={{ background: safeHex }}
                    >
                        <div className="absolute inset-0 bg-gradient-to-t from-black/25 via-transparent to-white/10" />
                        <div className="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 p-3">
                            <div>
                                <p className="text-[11px] font-medium tracking-wide text-white/80 uppercase drop-shadow">
                                    {label}
                                </p>
                                <p className="font-mono text-sm font-semibold text-white drop-shadow">
                                    {safeHex}
                                </p>
                            </div>
                            <label className="relative overflow-hidden rounded-full border border-white/40 bg-white/15 p-0.5 shadow-sm backdrop-blur-sm">
                                <span className="sr-only">Pick color</span>
                                <input
                                    type="color"
                                    value={safeHex}
                                    onChange={(event) =>
                                        onChange(
                                            event.target.value.toUpperCase(),
                                        )
                                    }
                                    className="size-9 cursor-pointer appearance-none border-0 bg-transparent [&::-webkit-color-swatch-wrapper]:p-0 [&::-webkit-color-swatch]:rounded-full [&::-webkit-color-swatch]:border-0"
                                />
                            </label>
                        </div>
                    </div>

                    <div className="space-y-4 p-4">
                        <div className="space-y-1.5">
                            <Label className="text-muted-foreground text-[11px] tracking-wide uppercase">
                                Hex
                            </Label>
                            <div className="relative">
                                <span className="text-muted-foreground absolute top-1/2 left-3 -translate-y-1/2 font-mono text-sm">
                                    #
                                </span>
                                <Input
                                    value={hex.replace(/^#/, '')}
                                    onChange={(event) =>
                                        commitHex(`#${event.target.value}`)
                                    }
                                    maxLength={6}
                                    spellCheck={false}
                                    className="h-10 pl-7 font-mono tracking-wider uppercase"
                                    placeholder="E5E7EB"
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label className="text-muted-foreground text-[11px] tracking-wide uppercase">
                                Palette
                            </Label>
                            <div className="grid grid-cols-6 gap-2">
                                {CATALOG_COLOR_PRESETS.map((preset) => {
                                    const selected = safeHex === preset;

                                    return (
                                        <button
                                            key={preset}
                                            type="button"
                                            title={preset}
                                            aria-label={`Use ${preset}`}
                                            aria-pressed={selected}
                                            onClick={() => {
                                                onChange(preset);
                                                setOpen(false);
                                            }}
                                            className={cn(
                                                'relative aspect-square rounded-lg border border-black/10 shadow-xs transition-transform hover:scale-105',
                                                selected &&
                                                    'ring-foreground/80 ring-2 ring-offset-2',
                                            )}
                                            style={{ background: preset }}
                                        >
                                            {selected ? (
                                                <Check
                                                    className={cn(
                                                        'absolute inset-0 m-auto size-3.5 drop-shadow',
                                                        isLightCatalogHex(
                                                            preset,
                                                        )
                                                            ? 'text-foreground'
                                                            : 'text-white',
                                                    )}
                                                />
                                            ) : null}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </PopoverContent>
            </Popover>
        </div>
    );
}

function SolidQuickSelect({
    selectedHex,
    onSelect,
}: {
    selectedHex?: string;
    onSelect: (color: string) => void;
}) {
    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-2">
                <span className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                    Solid quick select
                </span>
                <span className="text-muted-foreground text-[11px]">
                    White · gray · black · red
                </span>
            </div>
            <div className="flex flex-wrap gap-2">
                {CATALOG_QUICK_SELECT.map((preset) => {
                    const selected = selectedHex === preset.color;

                    return (
                        <button
                            key={preset.color}
                            type="button"
                            title={preset.label}
                            aria-label={preset.label}
                            aria-pressed={selected}
                            onClick={() => onSelect(preset.color)}
                            className={cn(
                                'relative size-8 overflow-hidden rounded-full border border-black/10 shadow-xs transition-transform hover:scale-105',
                                selected &&
                                    'ring-foreground/80 ring-2 ring-offset-2',
                            )}
                            style={{ background: preset.color }}
                        >
                            {selected ? (
                                <Check
                                    className={cn(
                                        'absolute inset-0 m-auto size-3.5 drop-shadow',
                                        isLightCatalogHex(preset.color)
                                            ? 'text-foreground'
                                            : 'text-white',
                                    )}
                                />
                            ) : null}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

function GradientQuickSelect({
    selected,
    onSelect,
}: {
    selected?: { from: string; to: string; angle: number };
    onSelect: (preset: {
        from: string;
        to: string;
        angle: number;
    }) => void;
}) {
    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-2">
                <span className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                    Gradient quick select
                </span>
                <span className="text-muted-foreground text-[11px]">
                    White · gray · black · red
                </span>
            </div>
            <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                {CATALOG_GRADIENT_QUICK_SELECT.map((preset) => {
                    const isSelected =
                        selected?.from === preset.from &&
                        selected?.to === preset.to &&
                        selected?.angle === preset.angle;

                    return (
                        <button
                            key={preset.label}
                            type="button"
                            title={preset.label}
                            aria-label={preset.label}
                            aria-pressed={isSelected}
                            onClick={() =>
                                onSelect({
                                    from: preset.from,
                                    to: preset.to,
                                    angle: preset.angle,
                                })
                            }
                            className={cn(
                                'group relative overflow-hidden rounded-xl border border-border/70 p-2 text-left shadow-xs transition-all hover:border-border',
                                isSelected &&
                                    'ring-foreground/80 border-transparent ring-2 ring-offset-2',
                            )}
                        >
                            <span
                                className="mb-2 block h-8 rounded-lg border border-black/5"
                                style={{
                                    backgroundImage: `linear-gradient(${preset.angle}deg, ${preset.from}, ${preset.to})`,
                                }}
                            />
                            <span className="text-muted-foreground block truncate text-[10px] font-medium">
                                {preset.label}
                            </span>
                            {isSelected ? (
                                <Check className="text-foreground absolute top-2 right-2 size-3.5 drop-shadow" />
                            ) : null}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

export function CatalogBackgroundColorField({
    id = 'color_code',
    value,
    onChange,
    error,
    className,
}: CatalogBackgroundColorFieldProps) {
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState<CatalogColorCode>(() =>
        normalizeCatalogColorCode(value),
    );

    const normalized = useMemo(
        () => normalizeCatalogColorCode(value),
        [value],
    );
    const previewStyle = catalogBackgroundStyle(normalized);
    const transparent = isTransparentCatalogColor(normalized);
    const draftPreviewStyle = catalogBackgroundStyle(draft);
    const draftTransparent = isTransparentCatalogColor(draft);

    const openDialog = () => {
        setDraft(normalizeCatalogColorCode(value));
        setOpen(true);
    };

    const applyDraft = () => {
        onChange(normalizeCatalogColorCode(draft));
        setOpen(false);
    };

    const setMode = (mode: CatalogColorMode) => {
        if (mode === 'transparent') {
            setDraft({ mode: 'transparent' });
            return;
        }

        if (mode === 'solid') {
            setDraft({
                mode: 'solid',
                color:
                    draft.mode === 'solid'
                        ? draft.color
                        : draft.mode === 'gradient'
                          ? draft.from
                          : '#E5E7EB',
            });
            return;
        }

        setDraft({
            mode: 'gradient',
            from:
                draft.mode === 'gradient'
                    ? draft.from
                    : draft.mode === 'solid'
                      ? draft.color
                      : '#E5E7EB',
            to: draft.mode === 'gradient' ? draft.to : '#FFFFFF',
            angle: draft.mode === 'gradient' ? draft.angle : 135,
        });
    };

    const applyQuickSolid = (color: string) => {
        setDraft({ mode: 'solid', color });
    };

    const applyQuickGradient = (preset: {
        from: string;
        to: string;
        angle: number;
    }) => {
        setDraft({
            mode: 'gradient',
            from: preset.from,
            to: preset.to,
            angle: preset.angle,
        });
    };

    return (
        <div className={cn('space-y-2', className)}>
            <div className="border-border/80 bg-card/60 overflow-hidden rounded-2xl border shadow-sm">
                <div className="border-border/60 flex items-start justify-between gap-3 border-b px-4 py-3.5">
                    <div className="space-y-0.5">
                        <Label htmlFor={id} className="text-sm font-semibold">
                            Image background
                        </Label>
                        <p className="text-muted-foreground text-xs">
                            Soft fill behind product and category imagery
                        </p>
                    </div>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        size="sm"
                        className="shrink-0 rounded-lg"
                        onClick={openDialog}
                    >
                        Customize
                    </Button>
                </div>

                <div className="flex items-center gap-3 p-4">
                    <div
                        className={cn(
                            'relative size-14 shrink-0 overflow-hidden rounded-xl border border-border/70 shadow-inner',
                            transparent ? CHECKERBOARD : null,
                        )}
                        style={transparent ? undefined : previewStyle}
                        aria-hidden
                    >
                        <div className="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-black/5" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium">
                            {modeSummary(normalized)}
                        </p>
                        <p className="text-muted-foreground text-xs">
                            Open the studio to change mode, palette, or
                            gradient.
                        </p>
                    </div>
                </div>
            </div>

            <InputError message={error} />

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[min(92vh,760px)] gap-0 overflow-hidden rounded-2xl p-0 sm:max-w-xl">
                    <DialogHeader className="border-border/60 space-y-1 border-b px-5 py-4 text-left">
                        <DialogTitle>Image background</DialogTitle>
                        <DialogDescription>
                            Choose none, a solid fill, or a two-tone gradient
                            for catalog imagery.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="max-h-[min(70vh,560px)] space-y-5 overflow-y-auto px-5 py-4">
                        <div
                            className={cn(
                                'relative overflow-hidden rounded-2xl border border-border/70 shadow-inner',
                                draftTransparent ? CHECKERBOARD : null,
                            )}
                            style={
                                draftTransparent ? undefined : draftPreviewStyle
                            }
                        >
                            <div className="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-black/5" />
                            <div className="relative flex aspect-[16/9] items-center justify-center p-6 sm:aspect-[2/1]">
                                <div className="border-border/50 bg-background/80 flex size-20 items-center justify-center rounded-2xl border border-dashed shadow-sm backdrop-blur-md sm:size-24">
                                    <div className="bg-muted/80 size-10 rounded-xl sm:size-12" />
                                </div>
                            </div>
                            <div className="absolute right-3 bottom-3 rounded-full border border-border/60 bg-background/85 px-2.5 py-1 text-[11px] font-medium tracking-wide shadow-xs backdrop-blur-sm">
                                {modeSummary(draft)}
                            </div>
                        </div>

                        <div
                            role="radiogroup"
                            aria-label="Background mode"
                            className="grid grid-cols-3 gap-2"
                        >
                            {MODE_OPTIONS.map((option) => {
                                const Icon = option.icon;
                                const active = draft.mode === option.value;

                                return (
                                    <button
                                        key={option.value}
                                        type="button"
                                        role="radio"
                                        aria-checked={active}
                                        onClick={() => setMode(option.value)}
                                        className={cn(
                                            'rounded-xl border px-2.5 py-3 text-left transition-all',
                                            active
                                                ? 'border-foreground/15 bg-foreground text-background shadow-sm'
                                                : 'border-border/70 bg-background/80 text-foreground hover:border-border hover:bg-muted/40',
                                        )}
                                    >
                                        <Icon
                                            className={cn(
                                                'mb-2 size-4',
                                                active
                                                    ? 'opacity-90'
                                                    : 'text-muted-foreground',
                                            )}
                                        />
                                        <span className="block text-xs font-semibold">
                                            {option.label}
                                        </span>
                                        <span
                                            className={cn(
                                                'mt-0.5 block text-[10px] leading-snug',
                                                active
                                                    ? 'text-background/70'
                                                    : 'text-muted-foreground',
                                            )}
                                        >
                                            {option.description}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>

                        {draft.mode === 'solid' ? (
                            <SolidQuickSelect
                                selectedHex={draft.color}
                                onSelect={applyQuickSolid}
                            />
                        ) : null}

                        {draft.mode === 'gradient' ? (
                            <GradientQuickSelect
                                selected={{
                                    from: draft.from,
                                    to: draft.to,
                                    angle: draft.angle,
                                }}
                                onSelect={applyQuickGradient}
                            />
                        ) : null}

                        {draft.mode === 'solid' ? (
                            <ColorSwatchPicker
                                label="Fill"
                                value={draft.color}
                                onChange={(color) =>
                                    setDraft({ mode: 'solid', color })
                                }
                            />
                        ) : null}

                        {draft.mode === 'gradient' ? (
                            <div className="space-y-4">
                                <div
                                    className="h-3 overflow-hidden rounded-full border border-border/60 shadow-inner"
                                    style={{
                                        backgroundImage: `linear-gradient(${draft.angle}deg, ${draft.from}, ${draft.to})`,
                                    }}
                                />

                                <div className="grid gap-3 sm:grid-cols-2">
                                    <ColorSwatchPicker
                                        label="Start"
                                        value={draft.from}
                                        onChange={(from) =>
                                            setDraft({ ...draft, from })
                                        }
                                    />
                                    <ColorSwatchPicker
                                        label="End"
                                        value={draft.to}
                                        onChange={(to) =>
                                            setDraft({ ...draft, to })
                                        }
                                    />
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                                            Direction
                                        </span>
                                        <span className="text-muted-foreground font-mono text-[11px]">
                                            {draft.angle}°
                                        </span>
                                    </div>
                                    <div className="flex flex-wrap gap-1.5">
                                        {CATALOG_GRADIENT_ANGLES.map(
                                            (angle) => {
                                                const active =
                                                    draft.angle === angle;

                                                return (
                                                    <Button
                                                        key={angle}
                                                        type="button"
                                                        size="sm"
                                                        variant={
                                                            active
                                                                ? 'default'
                                                                : 'outline'
                                                        }
                                                        className={cn(
                                                            'h-8 min-w-12 rounded-lg px-2.5 font-mono text-xs',
                                                            !active &&
                                                                'bg-background',
                                                        )}
                                                        onClick={() =>
                                                            setDraft({
                                                                ...draft,
                                                                angle,
                                                            })
                                                        }
                                                    >
                                                        {angle}°
                                                    </Button>
                                                );
                                            },
                                        )}
                                    </div>
                                </div>
                            </div>
                        ) : null}

                        {draft.mode === 'transparent' ? (
                            <p className="text-muted-foreground rounded-xl border border-dashed border-border/70 bg-muted/20 px-3 py-2.5 text-xs leading-relaxed">
                                No custom fill — listings keep the default muted
                                surface behind images.
                            </p>
                        ) : null}
                    </div>

                    <DialogFooter className="border-border/60 border-t px-5 py-4 sm:justify-between">
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => setDraft({ mode: 'transparent' })}
                        >
                            Reset
                        </Button>
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="button" onClick={applyDraft}>
                                Apply
                            </Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
