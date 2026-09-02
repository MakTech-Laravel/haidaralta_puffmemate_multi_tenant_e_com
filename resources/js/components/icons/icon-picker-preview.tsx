import { Check, ClipboardCopy } from 'lucide-react';
import { useState } from 'react';

import { CachedLucideIcon } from '@/components/icons/cached-lucide-icon';
import type {
    IconPickerLabels,
    LucideIconPickerClassNames,
} from '@/components/icons/lucide-icon-picker-types';
import { DEFAULT_ICON_PICKER_LABELS } from '@/components/icons/lucide-icon-picker-types';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type IconPickerPreviewProps = {
    icon: string;
    label: string;
    committedIcon?: string;
    layout?: 'rail' | 'bar';
    confirmSelection?: boolean;
    onConfirm?: () => void;
    onCancel?: () => void;
    labels?: Partial<IconPickerLabels>;
    classNames?: LucideIconPickerClassNames;
};

export function IconPickerPreview({
    icon,
    label,
    committedIcon,
    layout = 'rail',
    confirmSelection = false,
    onConfirm,
    onCancel,
    labels: labelsProp,
    classNames,
}: IconPickerPreviewProps) {
    const labels = { ...DEFAULT_ICON_PICKER_LABELS, ...labelsProp };
    const [copied, setCopied] = useState(false);
    const isPreview =
        confirmSelection && committedIcon != null && committedIcon !== icon;

    const copyKey = async () => {
        try {
            await navigator.clipboard.writeText(icon);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 1500);
        } catch {
            setCopied(false);
        }
    };

    if (layout === 'bar') {
        return (
            <div
                className={cn(
                    'flex items-center gap-3 border-t border-border bg-background px-5 py-4',
                    classNames?.preview,
                )}
            >
                <div className="flex size-11 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/40">
                    <CachedLucideIcon
                        key={icon}
                        name={icon}
                        className="size-5"
                    />
                </div>
                <div className="min-w-0 flex-1">
                    <p className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                        {isPreview ? labels.preview : labels.selected}
                    </p>
                    <p className="truncate text-sm font-medium text-foreground">
                        {label}
                    </p>
                    <p className="truncate font-mono text-[11px] text-muted-foreground">
                        {icon}
                    </p>
                </div>
                {confirmSelection ? (
                    <div className="flex shrink-0 gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={onCancel}
                        >
                            {labels.cancel}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            onClick={onConfirm}
                            className={classNames?.confirmButton}
                        >
                            {labels.confirm}
                        </Button>
                    </div>
                ) : null}
            </div>
        );
    }

    return (
        <aside
            className={cn(
                'flex h-full flex-col gap-4 border-l border-border bg-muted/20 p-5',
                classNames?.preview,
            )}
        >
            <div className="flex flex-1 flex-col items-center justify-center gap-4">
                <p className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                    {isPreview ? labels.preview : labels.selected}
                </p>
                <div className="flex size-24 items-center justify-center rounded-2xl border border-border bg-background shadow-xs">
                    <CachedLucideIcon
                        key={icon}
                        name={icon}
                        className="size-12"
                    />
                </div>
                <div className="space-y-1 text-center">
                    <p className="text-sm font-semibold text-foreground">
                        {label}
                    </p>
                    <p className="font-mono text-xs text-muted-foreground">
                        {icon}
                    </p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="gap-1.5"
                    onClick={() => void copyKey()}
                >
                    {copied ? (
                        <Check className="size-3.5" />
                    ) : (
                        <ClipboardCopy className="size-3.5" />
                    )}
                    {copied ? labels.copied : labels.copyKey}
                </Button>
            </div>

            {confirmSelection ? (
                <div className="flex flex-col gap-2">
                    <Button
                        type="button"
                        onClick={onConfirm}
                        className={cn('w-full', classNames?.confirmButton)}
                    >
                        {labels.confirm}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={onCancel}
                        className="w-full"
                    >
                        {labels.cancel}
                    </Button>
                </div>
            ) : null}
        </aside>
    );
}
