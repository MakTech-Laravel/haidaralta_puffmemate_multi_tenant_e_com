import { Check, CircleDot, Droplets, Layers2, Sparkles } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import type { ColorPickerLabels } from '@/components/color-picker/color-picker-types';
import { ColorSwatchPopover } from '@/components/color-picker/color-swatch-popover';
import { COLOR_PICKER_CHECKERBOARD } from '@/components/color-picker/color-swatch-popover';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    type ColorPickerMode,
    type ColorPickerValue,
    type GradientQuickSelectOption,
    type SolidQuickSelectOption,
    DEFAULT_GRADIENT_ANGLE_PRESETS,
    colorPickerStyle,
    colorPickerSummary,
    isLightHex,
    isTransparentColorPickerValue,
} from '@/lib/color-picker';
import { cn } from '@/lib/utils';

type ModeMeta = {
    value: ColorPickerMode;
    labelKey: keyof ColorPickerLabels;
    descriptionKey: keyof ColorPickerLabels;
    icon: LucideIcon;
};

const MODE_META: ModeMeta[] = [
    {
        value: 'transparent',
        labelKey: 'none',
        descriptionKey: 'noneDescription',
        icon: Layers2,
    },
    {
        value: 'solid',
        labelKey: 'solid',
        descriptionKey: 'solidDescription',
        icon: Droplets,
    },
    {
        value: 'gradient',
        labelKey: 'gradient',
        descriptionKey: 'gradientDescription',
        icon: Sparkles,
    },
    {
        value: 'radial',
        labelKey: 'radial',
        descriptionKey: 'radialDescription',
        icon: CircleDot,
    },
];

type ColorPickerPanelProps = {
    value: ColorPickerValue;
    onChange: (value: ColorPickerValue) => void;
    onModeChange: (mode: ColorPickerMode) => void;
    enabledModes: readonly ColorPickerMode[];
    labels: ColorPickerLabels;
    disabled?: boolean;
    colorPresets: readonly string[];
    solidQuickSelect: readonly SolidQuickSelectOption[];
    gradientQuickSelect: readonly GradientQuickSelectOption[];
    showQuickSelect?: boolean;
    showCustomAngle?: boolean;
    className?: string;
};

function SolidQuickSelect({
    options,
    selectedHex,
    onSelect,
    label,
    disabled,
}: {
    options: readonly SolidQuickSelectOption[];
    selectedHex?: string;
    onSelect: (color: string) => void;
    label: string;
    disabled?: boolean;
}) {
    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-2">
                <span className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                    {label}
                </span>
                <span className="text-muted-foreground text-[11px]">
                    White · gray · black · red
                </span>
            </div>
            <div className="flex flex-wrap gap-2">
                {options.map((preset) => {
                    const selected = selectedHex === preset.color;

                    return (
                        <button
                            key={preset.color}
                            type="button"
                            title={preset.label}
                            aria-label={preset.label}
                            aria-pressed={selected}
                            disabled={disabled}
                            onClick={() => onSelect(preset.color)}
                            className={cn(
                                'relative size-8 overflow-hidden rounded-full border border-black/10 shadow-xs transition-transform hover:scale-105 disabled:opacity-50',
                                selected &&
                                    'ring-foreground/80 ring-2 ring-offset-2',
                            )}
                            style={{ background: preset.color }}
                        >
                            {selected ? (
                                <Check
                                    className={cn(
                                        'absolute inset-0 m-auto size-3.5 drop-shadow',
                                        isLightHex(preset.color)
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
    options,
    selected,
    onSelect,
    label,
    disabled,
}: {
    options: readonly GradientQuickSelectOption[];
    selected?: { from: string; to: string; angle: number };
    onSelect: (preset: GradientQuickSelectOption) => void;
    label: string;
    disabled?: boolean;
}) {
    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-2">
                <span className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                    {label}
                </span>
                <span className="text-muted-foreground text-[11px]">
                    White · gray · black · red
                </span>
            </div>
            <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                {options.map((preset) => {
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
                            disabled={disabled}
                            onClick={() => onSelect(preset)}
                            className={cn(
                                'group relative overflow-hidden rounded-xl border border-border/70 p-2 text-left shadow-xs transition-all hover:border-border disabled:opacity-50',
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

export function ColorPickerPanel({
    value,
    onChange,
    onModeChange,
    enabledModes,
    labels,
    disabled = false,
    colorPresets,
    solidQuickSelect,
    gradientQuickSelect,
    showQuickSelect = true,
    showCustomAngle = true,
    className,
}: ColorPickerPanelProps) {
    const transparent = isTransparentColorPickerValue(value);
    const previewStyle = colorPickerStyle(value);
    const showModeCards = enabledModes.length > 1;
    const angleIsCustom =
        value.mode === 'gradient' &&
        !(DEFAULT_GRADIENT_ANGLE_PRESETS as readonly number[]).includes(
            value.angle,
        );

    return (
        <div className={cn('space-y-5', className)}>
            <div
                className={cn(
                    'relative overflow-hidden rounded-2xl border border-border/70 shadow-inner',
                    transparent ? COLOR_PICKER_CHECKERBOARD : null,
                )}
                style={transparent ? undefined : previewStyle}
            >
                <div className="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-black/5" />
                <div className="relative flex aspect-[16/9] items-center justify-center p-6 sm:aspect-[2/1]">
                    <div className="border-border/50 bg-background/80 flex size-20 items-center justify-center rounded-2xl border border-dashed shadow-sm backdrop-blur-md sm:size-24">
                        <div className="bg-muted/80 size-10 rounded-xl sm:size-12" />
                    </div>
                </div>
                <div className="absolute right-3 bottom-3 rounded-full border border-border/60 bg-background/85 px-2.5 py-1 text-[11px] font-medium tracking-wide shadow-xs backdrop-blur-sm">
                    {colorPickerSummary(value)}
                </div>
            </div>

            {showModeCards ? (
                <div
                    role="radiogroup"
                    aria-label="Color mode"
                    className={cn(
                        'grid gap-2',
                        enabledModes.length === 2 && 'grid-cols-2',
                        enabledModes.length === 3 && 'grid-cols-3',
                        enabledModes.length >= 4 && 'grid-cols-2 sm:grid-cols-4',
                    )}
                >
                    {MODE_META.filter((option) =>
                        enabledModes.includes(option.value),
                    ).map((option) => {
                        const Icon = option.icon;
                        const active = value.mode === option.value;

                        return (
                            <button
                                key={option.value}
                                type="button"
                                role="radio"
                                aria-checked={active}
                                disabled={disabled}
                                onClick={() => onModeChange(option.value)}
                                className={cn(
                                    'rounded-xl border px-2.5 py-3 text-left transition-all disabled:opacity-50',
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
                                    {labels[option.labelKey]}
                                </span>
                                <span
                                    className={cn(
                                        'mt-0.5 block text-[10px] leading-snug',
                                        active
                                            ? 'text-background/70'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {labels[option.descriptionKey]}
                                </span>
                            </button>
                        );
                    })}
                </div>
            ) : null}

            {showQuickSelect && value.mode === 'solid' ? (
                <SolidQuickSelect
                    options={solidQuickSelect}
                    selectedHex={value.color}
                    label={labels.solidQuickSelect}
                    disabled={disabled}
                    onSelect={(color) => onChange({ mode: 'solid', color })}
                />
            ) : null}

            {showQuickSelect && value.mode === 'gradient' ? (
                <GradientQuickSelect
                    options={gradientQuickSelect}
                    selected={{
                        from: value.from,
                        to: value.to,
                        angle: value.angle,
                    }}
                    label={labels.gradientQuickSelect}
                    disabled={disabled}
                    onSelect={(preset) =>
                        onChange({
                            mode: 'gradient',
                            from: preset.from,
                            to: preset.to,
                            angle: preset.angle,
                        })
                    }
                />
            ) : null}

            {value.mode === 'solid' ? (
                <ColorSwatchPopover
                    label="Fill"
                    value={value.color}
                    presets={colorPresets}
                    disabled={disabled}
                    onChange={(color) => onChange({ mode: 'solid', color })}
                />
            ) : null}

            {value.mode === 'gradient' ? (
                <div className="space-y-4">
                    <div
                        className="h-3 overflow-hidden rounded-full border border-border/60 shadow-inner"
                        style={{
                            backgroundImage: `linear-gradient(${value.angle}deg, ${value.from}, ${value.to})`,
                        }}
                    />

                    <div className="grid gap-3 sm:grid-cols-2">
                        <ColorSwatchPopover
                            label="Start"
                            value={value.from}
                            presets={colorPresets}
                            disabled={disabled}
                            onChange={(from) => onChange({ ...value, from })}
                        />
                        <ColorSwatchPopover
                            label="End"
                            value={value.to}
                            presets={colorPresets}
                            disabled={disabled}
                            onChange={(to) => onChange({ ...value, to })}
                        />
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                                {labels.direction}
                            </span>
                            <span className="text-muted-foreground font-mono text-[11px]">
                                {value.angle}°
                            </span>
                        </div>
                        <div className="flex flex-wrap gap-1.5">
                            {DEFAULT_GRADIENT_ANGLE_PRESETS.map((angle) => {
                                const active = value.angle === angle;

                                return (
                                    <Button
                                        key={angle}
                                        type="button"
                                        size="sm"
                                        variant={active ? 'default' : 'outline'}
                                        disabled={disabled}
                                        className={cn(
                                            'h-8 min-w-12 rounded-lg px-2.5 font-mono text-xs',
                                            !active && 'bg-background',
                                        )}
                                        onClick={() =>
                                            onChange({ ...value, angle })
                                        }
                                    >
                                        {angle}°
                                    </Button>
                                );
                            })}
                            {showCustomAngle ? (
                                <Button
                                    type="button"
                                    size="sm"
                                    variant={
                                        angleIsCustom ? 'default' : 'outline'
                                    }
                                    disabled={disabled}
                                    className={cn(
                                        'h-8 rounded-lg px-2.5 text-xs',
                                        !angleIsCustom && 'bg-background',
                                    )}
                                    onClick={() => {
                                        if (!angleIsCustom) {
                                            onChange({
                                                ...value,
                                                angle: value.angle,
                                            });
                                        }
                                    }}
                                >
                                    {labels.customAngle}
                                </Button>
                            ) : null}
                        </div>
                        {showCustomAngle ? (
                            <div className="flex items-center gap-2">
                                <Input
                                    type="number"
                                    min={0}
                                    max={360}
                                    value={value.angle}
                                    disabled={disabled}
                                    className="h-9 w-24 font-mono"
                                    aria-label={labels.customAngle}
                                    onChange={(event) => {
                                        const next = Number(event.target.value);
                                        onChange({
                                            ...value,
                                            angle: Number.isNaN(next)
                                                ? value.angle
                                                : Math.min(
                                                      360,
                                                      Math.max(0, next),
                                                  ),
                                        });
                                    }}
                                />
                                <span className="text-muted-foreground text-xs">
                                    0–360°
                                </span>
                            </div>
                        ) : null}
                    </div>
                </div>
            ) : null}

            {value.mode === 'radial' ? (
                <div className="space-y-4">
                    <div
                        className="h-3 overflow-hidden rounded-full border border-border/60 shadow-inner"
                        style={{
                            backgroundImage: `radial-gradient(${value.position ?? 'circle at center'}, ${value.from}, ${value.to})`,
                        }}
                    />
                    <div className="grid gap-3 sm:grid-cols-2">
                        <ColorSwatchPopover
                            label="Start"
                            value={value.from}
                            presets={colorPresets}
                            disabled={disabled}
                            onChange={(from) => onChange({ ...value, from })}
                        />
                        <ColorSwatchPopover
                            label="End"
                            value={value.to}
                            presets={colorPresets}
                            disabled={disabled}
                            onChange={(to) => onChange({ ...value, to })}
                        />
                    </div>
                </div>
            ) : null}

            {value.mode === 'transparent' ? (
                <p className="text-muted-foreground rounded-xl border border-dashed border-border/70 bg-muted/20 px-3 py-2.5 text-xs leading-relaxed">
                    {labels.transparentHint}
                </p>
            ) : null}
        </div>
    );
}
