import type {
    ColorPickerClassNames,
    ColorPickerLabels,
} from '@/components/color-picker/color-picker-types';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type ColorPickerPreviewBarProps = {
    confirmSelection: boolean;
    onConfirm: () => void;
    onCancel: () => void;
    onReset?: () => void;
    labels: ColorPickerLabels;
    classNames?: ColorPickerClassNames;
    className?: string;
};

export function ColorPickerPreviewBar({
    confirmSelection,
    onConfirm,
    onCancel,
    onReset,
    labels,
    classNames,
    className,
}: ColorPickerPreviewBarProps) {
    if (!confirmSelection && !onReset) {
        return null;
    }

    return (
        <div
            className={cn(
                'border-border/60 flex items-center justify-between gap-2 border-t px-5 py-4',
                classNames?.preview,
                className,
            )}
        >
            {onReset ? (
                <Button type="button" variant="ghost" onClick={onReset}>
                    {labels.reset}
                </Button>
            ) : (
                <span />
            )}
            <div className="flex gap-2">
                <Button type="button" variant="outline" onClick={onCancel}>
                    {labels.cancel}
                </Button>
                {confirmSelection ? (
                    <Button
                        type="button"
                        className={cn(classNames?.confirmButton)}
                        onClick={onConfirm}
                    >
                        {labels.confirm}
                    </Button>
                ) : null}
            </div>
        </div>
    );
}
