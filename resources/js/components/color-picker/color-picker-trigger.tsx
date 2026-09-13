import { ChevronDown } from 'lucide-react';
import type { ComponentPropsWithoutRef, Ref } from 'react';

import type {
    ColorPickerClassNames,
    ColorPickerLabels,
    ColorPickerPresentationMode,
    ColorPickerTriggerVariant,
} from '@/components/color-picker/color-picker-types';
import { COLOR_PICKER_CHECKERBOARD } from '@/components/color-picker/color-swatch-popover';
import {
    type ColorPickerValue,
    colorPickerStyle,
    colorPickerSummary,
    isTransparentColorPickerValue,
} from '@/lib/color-picker';
import { cn } from '@/lib/utils';

type ColorPickerTriggerProps = {
    open: boolean;
    value: ColorPickerValue;
    label: string;
    description: string;
    error?: string;
    panelId: string;
    classNames?: ColorPickerClassNames;
    mode: ColorPickerPresentationMode;
    triggerVariant?: ColorPickerTriggerVariant;
    labels: ColorPickerLabels;
    ref?: Ref<HTMLButtonElement>;
} & Omit<ComponentPropsWithoutRef<'button'>, 'children' | 'value'>;

export function ColorPickerTrigger({
    id,
    open,
    disabled,
    value,
    label,
    description,
    error,
    panelId,
    classNames,
    mode,
    triggerVariant = 'field',
    labels,
    className,
    ref,
    type = 'button',
    ...props
}: ColorPickerTriggerProps) {
    const transparent = isTransparentColorPickerValue(value);
    const previewStyle = colorPickerStyle(value);
    const summary = colorPickerSummary(value);

    const actionLabel =
        mode === 'collapsible'
            ? open
                ? labels.hide
                : labels.change
            : open
              ? labels.close
              : labels.customize;

    const swatch = (
        <span
            className={cn(
                'relative size-9 shrink-0 overflow-hidden rounded-lg border border-border/70 shadow-inner',
                transparent ? COLOR_PICKER_CHECKERBOARD : null,
                classNames?.triggerPreview,
            )}
            style={transparent ? undefined : previewStyle}
            aria-hidden
        >
            <span className="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-black/5" />
        </span>
    );

    if (triggerVariant === 'compact') {
        return (
            <button
                {...props}
                ref={ref}
                id={id}
                type={type}
                disabled={disabled}
                aria-expanded={open}
                aria-controls={panelId}
                aria-haspopup={mode !== 'collapsible' ? 'dialog' : undefined}
                aria-invalid={error ? true : undefined}
                aria-label={`${summary} — ${actionLabel}`}
                className={cn(
                    'border-border/70 bg-background text-foreground hover:bg-muted/50 focus-visible:ring-ring inline-flex size-10 items-center justify-center rounded-lg border shadow-xs transition-colors focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                    open && 'ring-ring/40 ring-2',
                    error && 'border-destructive/60',
                    classNames?.trigger,
                    className,
                )}
            >
                {swatch}
            </button>
        );
    }

    if (triggerVariant === 'button') {
        return (
            <button
                {...props}
                ref={ref}
                id={id}
                type={type}
                disabled={disabled}
                aria-expanded={open}
                aria-controls={panelId}
                aria-haspopup={mode !== 'collapsible' ? 'dialog' : undefined}
                aria-invalid={error ? true : undefined}
                className={cn(
                    'border-border bg-background hover:bg-muted/40 focus-visible:ring-ring inline-flex h-10 items-center gap-2.5 rounded-lg border px-2.5 pr-3 text-sm font-medium shadow-xs transition-colors focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                    open && 'ring-ring/40 ring-2',
                    error && 'border-destructive/60',
                    classNames?.trigger,
                    className,
                )}
            >
                <span
                    className={cn(
                        'relative size-6 shrink-0 overflow-hidden rounded-md border border-border/70 shadow-inner',
                        transparent ? COLOR_PICKER_CHECKERBOARD : null,
                        classNames?.triggerPreview,
                    )}
                    style={transparent ? undefined : previewStyle}
                    aria-hidden
                >
                    <span className="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-black/5" />
                </span>
                <span className={cn('min-w-0 truncate', classNames?.triggerLabel)}>
                    {summary}
                </span>
                <ChevronDown
                    className={cn(
                        'text-muted-foreground size-3.5 shrink-0 transition-transform',
                        open && mode === 'collapsible' && 'rotate-180',
                    )}
                />
            </button>
        );
    }

    if (triggerVariant === 'ghost') {
        return (
            <button
                {...props}
                ref={ref}
                id={id}
                type={type}
                disabled={disabled}
                aria-expanded={open}
                aria-controls={panelId}
                aria-haspopup={mode !== 'collapsible' ? 'dialog' : undefined}
                aria-invalid={error ? true : undefined}
                className={cn(
                    'hover:bg-muted/40 focus-visible:ring-ring inline-flex h-9 items-center gap-2 rounded-lg px-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                    open && 'bg-muted/40',
                    error && 'text-destructive',
                    classNames?.trigger,
                    className,
                )}
            >
                {swatch}
                <span className={cn(classNames?.triggerLabel)}>{summary}</span>
            </button>
        );
    }

    return (
        <button
            {...props}
            ref={ref}
            id={id}
            type={type}
            disabled={disabled}
            aria-expanded={open}
            aria-controls={panelId}
            aria-haspopup={mode !== 'collapsible' ? 'dialog' : undefined}
            aria-invalid={error ? true : undefined}
            className={cn(
                'border-border/80 bg-card/60 hover:bg-card focus-visible:ring-ring flex h-full w-full flex-col items-stretch overflow-hidden rounded-2xl border p-0 text-left shadow-sm transition-colors focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                open && 'ring-ring/40 ring-2',
                error && 'border-destructive/60',
                classNames?.trigger,
                className,
            )}
        >
            <div className="flex min-h-0 w-full flex-1 flex-col">
                <div className="border-border/60 flex shrink-0 items-start justify-between gap-3 border-b px-4 py-3">
                    <div className="space-y-0.5">
                        <span
                            className={cn(
                                'block text-sm font-semibold',
                                classNames?.triggerLabel,
                            )}
                        >
                            {label}
                        </span>
                        {description ? (
                            <span
                                className={cn(
                                    'text-muted-foreground block text-xs',
                                    classNames?.triggerDescription,
                                )}
                            >
                                {description}
                            </span>
                        ) : null}
                    </div>
                    <span
                        className={cn(
                            'border-border/80 bg-background text-foreground inline-flex h-8 shrink-0 items-center rounded-lg border px-3 text-xs font-medium shadow-xs',
                            classNames?.triggerAction,
                        )}
                    >
                        {actionLabel}
                    </span>
                </div>
                <div className="flex flex-1 items-center gap-3 p-3.5">
                    <span
                        className={cn(
                            'relative size-12 shrink-0 overflow-hidden rounded-xl border border-border/70 shadow-inner',
                            transparent ? COLOR_PICKER_CHECKERBOARD : null,
                            classNames?.triggerPreview,
                        )}
                        style={transparent ? undefined : previewStyle}
                        aria-hidden
                    >
                        <span className="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-black/5" />
                    </span>
                    <span className="min-w-0 flex-1">
                        <span className="block truncate text-sm font-medium">
                            {summary}
                        </span>
                        <span className="text-muted-foreground block text-xs">
                            Open the studio to change mode, palette, or fill.
                        </span>
                    </span>
                </div>
            </div>
        </button>
    );
}
