import { ChevronDown, Sparkles } from 'lucide-react';
import type { ComponentPropsWithoutRef, Ref } from 'react';

import type {
    LucideIconPickerClassNames,
    LucideIconPickerMode,
    LucideIconPickerTriggerVariant,
} from '@/components/icons/lucide-icon-picker-types';
import { Icon } from '@/lib/icons';
import { cn } from '@/lib/utils';

type IconPickerTriggerProps = {
    open: boolean;
    displayIcon: string;
    displayLabel: string;
    description: string;
    showSparkles?: boolean;
    error?: string;
    gridId: string;
    statusId: string;
    classNames?: LucideIconPickerClassNames;
    mode: LucideIconPickerMode;
    triggerVariant?: LucideIconPickerTriggerVariant;
    ref?: Ref<HTMLButtonElement>;
} & Omit<ComponentPropsWithoutRef<'button'>, 'children'>;

export function IconPickerTrigger({
    id,
    open,
    disabled,
    displayIcon,
    displayLabel,
    description,
    showSparkles = false,
    error,
    gridId,
    statusId,
    classNames,
    mode,
    triggerVariant = 'field',
    className,
    ref,
    type = 'button',
    ...props
}: IconPickerTriggerProps) {
    const actionLabel =
        mode === 'collapsible'
            ? open
                ? 'Hide'
                : 'Change'
            : open
              ? 'Close'
              : 'Browse';

    if (triggerVariant === 'compact') {
        return (
            <button
                {...props}
                ref={ref}
                id={id}
                type={type}
                disabled={disabled}
                aria-expanded={open}
                aria-controls={gridId}
                aria-haspopup={mode !== 'collapsible' ? 'dialog' : undefined}
                aria-invalid={error ? true : undefined}
                aria-label={`${displayLabel} — ${actionLabel} icon`}
                className={cn(
                    'inline-flex size-10 items-center justify-center rounded-lg border border-border/70 bg-background text-foreground shadow-xs transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                    open && 'ring-2 ring-ring/40',
                    error && 'border-destructive/60',
                    classNames?.trigger,
                    className,
                )}
            >
                <Icon icon={displayIcon} className="size-4" />
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
                aria-controls={gridId}
                aria-haspopup={mode !== 'collapsible' ? 'dialog' : undefined}
                aria-invalid={error ? true : undefined}
                className={cn(
                    'inline-flex h-9 items-center gap-2 rounded-lg border border-border bg-background px-3 text-sm font-medium shadow-xs transition-colors hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                    open && 'ring-2 ring-ring/40',
                    error && 'border-destructive/60',
                    classNames?.trigger,
                    className,
                )}
            >
                <Icon icon={displayIcon} className="size-4 text-primary" />
                <span className={cn(classNames?.triggerLabel)}>
                    {displayLabel}
                </span>
                <ChevronDown
                    className={cn(
                        'size-3.5 text-muted-foreground transition-transform',
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
                aria-controls={gridId}
                aria-haspopup={mode !== 'collapsible' ? 'dialog' : undefined}
                aria-invalid={error ? true : undefined}
                className={cn(
                    'inline-flex items-center gap-2 rounded-md px-1.5 py-1 text-sm text-foreground transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                    classNames?.trigger,
                    className,
                )}
            >
                <Icon icon={displayIcon} className="size-4" />
                <span className={cn('font-medium', classNames?.triggerLabel)}>
                    {displayLabel}
                </span>
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
            aria-controls={gridId}
            aria-haspopup={mode !== 'collapsible' ? 'dialog' : undefined}
            aria-invalid={error ? true : undefined}
            aria-describedby={error ? statusId : undefined}
            className={cn(
                'flex w-full items-center gap-3 rounded-xl border border-border/80 bg-background px-3 py-2.5 text-left shadow-xs transition-colors hover:bg-muted/30 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                open && 'ring-2 ring-ring/30',
                error && 'border-destructive/60',
                classNames?.trigger,
                className,
            )}
        >
            <div
                className={cn(
                    'relative flex size-10 shrink-0 items-center justify-center rounded-lg border border-border/60 bg-muted/30',
                    classNames?.triggerPreview,
                )}
            >
                <Icon icon={displayIcon} className="size-5 text-foreground" />
                {showSparkles ? (
                    <span className="absolute -right-1 -bottom-1 flex size-4 items-center justify-center rounded-full bg-primary text-primary-foreground">
                        <Sparkles className="size-2.5" />
                    </span>
                ) : null}
            </div>
            <div className="min-w-0 flex-1">
                <p
                    className={cn(
                        'truncate text-sm font-medium text-foreground',
                        classNames?.triggerLabel,
                    )}
                >
                    {displayLabel}
                </p>
                <p
                    className={cn(
                        'truncate font-mono text-[11px] text-muted-foreground',
                        classNames?.triggerDescription,
                    )}
                >
                    {description || displayIcon}
                </p>
            </div>
            <div
                className={cn(
                    'flex shrink-0 items-center gap-1.5 text-muted-foreground',
                    classNames?.triggerAction,
                )}
            >
                <span className="hidden text-xs font-medium sm:inline">
                    {actionLabel}
                </span>
                <ChevronDown
                    className={cn(
                        'size-4 transition-transform duration-200',
                        open && mode === 'collapsible' && 'rotate-180',
                    )}
                />
            </div>
        </button>
    );
}
