import { forwardRef, useImperativeHandle } from 'react';

import { ColorPickerCollapsible } from '@/components/color-picker/color-picker-collapsible';
import { ColorPickerDialog } from '@/components/color-picker/color-picker-dialog';
import { ColorPickerSheet } from '@/components/color-picker/color-picker-sheet';
import type {
    ColorPickerHandle,
    ColorPickerPresentationMode,
    ColorPickerProps,
} from '@/components/color-picker/color-picker-types';
import { useColorPickerState } from '@/components/color-picker/use-color-picker-state';
import { cn } from '@/lib/utils';

export type {
    ColorPickerClassNames,
    ColorPickerHandle,
    ColorPickerLabels,
    ColorPickerPanelAlign,
    ColorPickerPanelBehavior,
    ColorPickerPresentationMode,
    ColorPickerProps,
    ColorPickerSize,
    ColorPickerTriggerVariant,
} from '@/components/color-picker/color-picker-types';

function resolveMode(
    mode: ColorPickerPresentationMode | undefined,
    triggerVariant: ColorPickerProps['triggerVariant'],
): ColorPickerPresentationMode {
    if (mode) {
        return mode;
    }

    if (triggerVariant === 'compact' || triggerVariant === 'ghost') {
        return 'dialog';
    }

    return 'collapsible';
}

export const ColorPicker = forwardRef<ColorPickerHandle, ColorPickerProps>(
    function ColorPicker(
        {
            name,
            id,
            className,
            classNames,
            defaultValue,
            value,
            onChange,
            onPendingChange,
            label = 'Color',
            description = '',
            defaultOpen = false,
            open,
            onOpenChange,
            disabled = false,
            required = false,
            error,
            labels,
            size = 'md',
            panelAlign = 'auto',
            mode: modeProp,
            triggerVariant = 'field',
            panelBehavior,
            confirmSelection,
            enabledModes,
            defaultMode,
            colorPresets,
            solidQuickSelect,
            gradientQuickSelect,
            showQuickSelect = true,
            showCustomAngle = true,
            dialogTitle,
            dialogDescription,
        },
        ref,
    ) {
        const mode = resolveMode(modeProp, triggerVariant);

        const state = useColorPickerState({
            id,
            defaultValue,
            value,
            onChange,
            onPendingChange,
            defaultOpen,
            open,
            onOpenChange,
            disabled,
            labels,
            confirmSelection,
            enabledModes,
            defaultMode,
            colorPresets,
            solidQuickSelect,
            gradientQuickSelect,
            showQuickSelect,
            showCustomAngle,
            mode,
        });

        const { setOpen, focus, committedValue, triggerRef } = state;

        useImperativeHandle(
            ref,
            () => ({
                open: () => setOpen(true),
                close: () => setOpen(false),
                focus,
            }),
            [focus, setOpen],
        );

        const resolvedClassNames = {
            ...classNames,
            wrapper: cn(className, classNames?.wrapper),
        };

        const resolvedDialogTitle = dialogTitle ?? label;
        const resolvedDialogDescription =
            dialogDescription ||
            description ||
            'Choose a solid color or fill style.';

        const sharedShellProps = {
            id,
            description,
            label,
            disabled,
            error,
            triggerVariant,
            classNames: resolvedClassNames,
            state,
            triggerRef,
        };

        const serialized = JSON.stringify(committedValue);

        return (
            <div className={cn('grid gap-2', resolvedClassNames.wrapper)}>
                {name ? (
                    <input
                        type="hidden"
                        name={name}
                        value={serialized}
                        required={required}
                        disabled={disabled}
                    />
                ) : null}

                {mode === 'dialog' ? (
                    <ColorPickerDialog
                        {...sharedShellProps}
                        size={size}
                        dialogTitle={resolvedDialogTitle}
                        dialogDescription={resolvedDialogDescription}
                    />
                ) : mode === 'sheet' ? (
                    <ColorPickerSheet
                        {...sharedShellProps}
                        size={size}
                        dialogTitle={resolvedDialogTitle}
                        dialogDescription={resolvedDialogDescription}
                    />
                ) : (
                    <ColorPickerCollapsible
                        {...sharedShellProps}
                        panelBehavior={panelBehavior}
                        panelAlign={panelAlign}
                    />
                )}

                {error ? (
                    <p
                        className={cn(
                            'text-destructive text-sm',
                            resolvedClassNames.error,
                        )}
                        role="alert"
                    >
                        {error}
                    </p>
                ) : null}
            </div>
        );
    },
);
