import type { ColorPickerMode, ColorPickerValue } from '@/lib/color-picker';
import type {
    GradientQuickSelectOption,
    SolidQuickSelectOption,
} from '@/lib/color-picker';

export type ColorPickerPresentationMode = 'collapsible' | 'dialog' | 'sheet';

export type ColorPickerTriggerVariant =
    | 'field'
    | 'compact'
    | 'button'
    | 'ghost';

export type ColorPickerPanelBehavior = 'inline' | 'overlay';

export type ColorPickerSize = 'sm' | 'md' | 'lg';

export type ColorPickerPanelAlign = 'start' | 'end' | 'auto';

export type ColorPickerLabels = {
    customize: string;
    hide: string;
    change: string;
    close: string;
    browse: string;
    cancel: string;
    confirm: string;
    reset: string;
    preview: string;
    solidQuickSelect: string;
    gradientQuickSelect: string;
    direction: string;
    customAngle: string;
    transparentHint: string;
    none: string;
    solid: string;
    gradient: string;
    radial: string;
    noneDescription: string;
    solidDescription: string;
    gradientDescription: string;
    radialDescription: string;
};

export interface ColorPickerClassNames {
    wrapper?: string;
    shell?: string;
    trigger?: string;
    triggerPreview?: string;
    triggerLabel?: string;
    triggerDescription?: string;
    triggerAction?: string;
    panel?: string;
    preview?: string;
    confirmButton?: string;
    dialogContent?: string;
    dialogHeader?: string;
    sheetContent?: string;
    error?: string;
}

export interface ColorPickerHandle {
    open: () => void;
    close: () => void;
    focus: () => void;
}

export type ColorPickerProps = {
    name?: string;
    id?: string;
    className?: string;
    classNames?: ColorPickerClassNames;
    defaultValue?: ColorPickerValue | null;
    value?: ColorPickerValue | null;
    onChange?: (value: ColorPickerValue) => void;
    onPendingChange?: (value: ColorPickerValue | null) => void;
    label?: string;
    description?: string;
    defaultOpen?: boolean;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    disabled?: boolean;
    required?: boolean;
    error?: string;
    labels?: Partial<ColorPickerLabels>;
    size?: ColorPickerSize;
    panelAlign?: ColorPickerPanelAlign;
    mode?: ColorPickerPresentationMode;
    triggerVariant?: ColorPickerTriggerVariant;
    panelBehavior?: ColorPickerPanelBehavior;
    /**
     * Require Confirm in dialog/sheet before committing.
     * @default true when mode is dialog or sheet; false for collapsible
     */
    confirmSelection?: boolean;
    enabledModes?: ColorPickerMode[];
    defaultMode?: ColorPickerMode;
    colorPresets?: readonly string[];
    solidQuickSelect?: readonly SolidQuickSelectOption[];
    gradientQuickSelect?: readonly GradientQuickSelectOption[];
    showQuickSelect?: boolean;
    showCustomAngle?: boolean;
    dialogTitle?: string;
    dialogDescription?: string;
};

export const DEFAULT_COLOR_PICKER_LABELS: ColorPickerLabels = {
    customize: 'Customize',
    hide: 'Hide',
    change: 'Change',
    close: 'Close',
    browse: 'Browse',
    cancel: 'Cancel',
    confirm: 'Apply',
    reset: 'Reset',
    preview: 'Preview',
    solidQuickSelect: 'Solid quick select',
    gradientQuickSelect: 'Gradient quick select',
    direction: 'Direction',
    customAngle: 'Custom',
    transparentHint:
        'No custom fill — keep the default muted surface behind imagery.',
    none: 'None',
    solid: 'Solid',
    gradient: 'Gradient',
    radial: 'Radial',
    noneDescription: 'Default muted tile',
    solidDescription: 'Single fill color',
    gradientDescription: 'Two-tone wash',
    radialDescription: 'Radial wash',
};
