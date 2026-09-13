import { Pipette } from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    CATALOG_COLOR_PRESETS,
    CATALOG_GRADIENT_ANGLES,
    catalogBackgroundStyle,
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

function HexColorPicker({
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

    return (
        <div className="flex min-w-0 flex-col gap-2">
            <Label className="text-muted-foreground text-xs">{label}</Label>
            <div className="flex items-center gap-2">
                <Popover open={open} onOpenChange={setOpen}>
                    <PopoverTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            className="size-9 shrink-0 p-0"
                            aria-label={`${label} swatch`}
                        >
                            <span
                                className="border-border size-6 rounded-sm border"
                                style={{ background: hex }}
                            />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-64 space-y-3" align="start">
                        <div className="flex items-center gap-2">
                            <input
                                type="color"
                                value={
                                    /^#[0-9A-F]{6}$/.test(hex)
                                        ? hex
                                        : '#E5E7EB'
                                }
                                onChange={(event) =>
                                    onChange(event.target.value.toUpperCase())
                                }
                                className="border-input size-10 cursor-pointer rounded-md border bg-transparent p-1"
                            />
                            <Input
                                value={hex}
                                onChange={(event) => {
                                    const next = event.target.value
                                        .toUpperCase()
                                        .replace(/[^#0-9A-F]/g, '')
                                        .slice(0, 7);
                                    onChange(
                                        next.startsWith('#') ? next : `#${next}`,
                                    );
                                }}
                                placeholder="#E5E7EB"
                                className="font-mono uppercase"
                            />
                        </div>
                        <div className="grid grid-cols-6 gap-1.5">
                            {CATALOG_COLOR_PRESETS.map((preset) => (
                                <button
                                    key={preset}
                                    type="button"
                                    className={cn(
                                        'border-border size-7 rounded-md border transition-transform hover:scale-105',
                                        hex === preset &&
                                            'ring-ring ring-2 ring-offset-1',
                                    )}
                                    style={{ background: preset }}
                                    aria-label={`Use ${preset}`}
                                    onClick={() => {
                                        onChange(preset);
                                        setOpen(false);
                                    }}
                                />
                            ))}
                        </div>
                    </PopoverContent>
                </Popover>
                <Input
                    value={hex}
                    onChange={(event) => {
                        const next = event.target.value
                            .toUpperCase()
                            .replace(/[^#0-9A-F]/g, '')
                            .slice(0, 7);
                        onChange(next.startsWith('#') ? next : `#${next}`);
                    }}
                    className="font-mono uppercase"
                    placeholder="#E5E7EB"
                />
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
    const normalized = useMemo(
        () => normalizeCatalogColorCode(value),
        [value],
    );
    const previewStyle = catalogBackgroundStyle(normalized);
    const transparent = isTransparentCatalogColor(normalized);

    const setMode = (mode: CatalogColorMode) => {
        if (mode === 'transparent') {
            onChange({ mode: 'transparent' });
            return;
        }

        if (mode === 'solid') {
            onChange({
                mode: 'solid',
                color:
                    normalized.mode === 'solid'
                        ? normalized.color
                        : normalized.mode === 'gradient'
                          ? normalized.from
                          : '#E5E7EB',
            });
            return;
        }

        onChange({
            mode: 'gradient',
            from:
                normalized.mode === 'gradient'
                    ? normalized.from
                    : normalized.mode === 'solid'
                      ? normalized.color
                      : '#E5E7EB',
            to: normalized.mode === 'gradient' ? normalized.to : '#FFFFFF',
            angle: normalized.mode === 'gradient' ? normalized.angle : 135,
        });
    };

    return (
        <div className={cn('flex w-full min-w-0 flex-col gap-3', className)}>
            <div className="flex items-center justify-between gap-3">
                <Label htmlFor={id}>Image background</Label>
                <span className="text-muted-foreground text-xs">
                    Behind catalog images
                </span>
            </div>

            <div className="flex flex-wrap items-start gap-3">
                <div
                    className={cn(
                        'border-border size-16 shrink-0 overflow-hidden rounded-xl border shadow-xs',
                        transparent &&
                            'bg-[linear-gradient(45deg,#e5e7eb_25%,transparent_25%),linear-gradient(-45deg,#e5e7eb_25%,transparent_25%),linear-gradient(45deg,transparent_75%,#e5e7eb_75%),linear-gradient(-45deg,transparent_75%,#e5e7eb_75%)] bg-size-[12px_12px] bg-position-[0_0,0_6px,6px_-6px,-6px_0]',
                    )}
                    style={transparent ? undefined : previewStyle}
                    aria-hidden
                />

                <div className="min-w-0 flex-1 space-y-3">
                    <Select
                        value={normalized.mode}
                        onValueChange={(mode) =>
                            setMode(mode as CatalogColorMode)
                        }
                    >
                        <SelectTrigger id={id} className="w-full">
                            <SelectValue placeholder="Background mode" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="transparent">
                                Transparent
                            </SelectItem>
                            <SelectItem value="solid">Solid</SelectItem>
                            <SelectItem value="gradient">Gradient</SelectItem>
                        </SelectContent>
                    </Select>

                    {normalized.mode === 'solid' ? (
                        <HexColorPicker
                            label="Color"
                            value={normalized.color}
                            onChange={(color) =>
                                onChange({ mode: 'solid', color })
                            }
                        />
                    ) : null}

                    {normalized.mode === 'gradient' ? (
                        <div className="space-y-3">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <HexColorPicker
                                    label="From"
                                    value={normalized.from}
                                    onChange={(from) =>
                                        onChange({ ...normalized, from })
                                    }
                                />
                                <HexColorPicker
                                    label="To"
                                    value={normalized.to}
                                    onChange={(to) =>
                                        onChange({ ...normalized, to })
                                    }
                                />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label className="text-muted-foreground text-xs">
                                    Angle
                                </Label>
                                <Select
                                    value={String(normalized.angle)}
                                    onValueChange={(angle) =>
                                        onChange({
                                            ...normalized,
                                            angle: Number(angle),
                                        })
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {CATALOG_GRADIENT_ANGLES.map(
                                            (angle) => (
                                                <SelectItem
                                                    key={angle}
                                                    value={String(angle)}
                                                >
                                                    {angle}°
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    ) : null}

                    {normalized.mode === 'transparent' ? (
                        <p className="text-muted-foreground flex items-center gap-1.5 text-xs">
                            <Pipette className="size-3.5" />
                            Uses the default muted tile behind images.
                        </p>
                    ) : null}
                </div>
            </div>

            <InputError message={error} />
        </div>
    );
}
