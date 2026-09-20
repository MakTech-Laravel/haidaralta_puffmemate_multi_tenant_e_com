import { Check } from 'lucide-react';
import { useState } from 'react';

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    DEFAULT_COLOR_PRESETS,
    isLightHex,
    normalizeHex,
} from '@/lib/color-picker';
import { cn } from '@/lib/utils';

export const COLOR_PICKER_CHECKERBOARD =
    'bg-[linear-gradient(45deg,#d4d4d8_25%,transparent_25%),linear-gradient(-45deg,#d4d4d8_25%,transparent_25%),linear-gradient(45deg,transparent_75%,#d4d4d8_75%),linear-gradient(-45deg,transparent_75%,#d4d4d8_75%)] bg-[length:10px_10px] bg-[position:0_0,0_5px,5px_-5px,-5px_0px] bg-white';

type ColorSwatchPopoverProps = {
    label: string;
    value: string;
    onChange: (hex: string) => void;
    presets?: readonly string[];
    disabled?: boolean;
};

function isValidHex(value: string): boolean {
    return normalizeHex(value) !== null;
}

export function ColorSwatchPopover({
    label,
    value,
    onChange,
    presets = DEFAULT_COLOR_PRESETS,
    disabled = false,
}: ColorSwatchPopoverProps) {
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
                        disabled={disabled}
                        className={cn(
                            'group border-border/80 bg-background hover:border-border focus-visible:ring-ring',
                            'flex w-full items-center gap-3 rounded-xl border px-3 py-2.5 text-left shadow-xs transition-all',
                            'focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
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
                    className="border-border/80 z-60 w-[min(100vw-2rem,20rem)] overflow-hidden rounded-2xl p-0 shadow-lg"
                >
                    <div
                        className={cn(
                            'relative h-28',
                            COLOR_PICKER_CHECKERBOARD,
                        )}
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
                                    className="size-9 cursor-pointer appearance-none border-0 bg-transparent [&::-webkit-color-swatch]:rounded-full [&::-webkit-color-swatch]:border-0 [&::-webkit-color-swatch-wrapper]:p-0"
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
                                {presets.map((preset) => {
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
                                                        isLightHex(preset)
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
