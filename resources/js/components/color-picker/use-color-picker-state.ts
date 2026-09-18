import {
    useCallback,
    useEffect,
    useId,
    useMemo,
    useRef,
    useState,
} from 'react';

import type {
    ColorPickerLabels,
    ColorPickerPresentationMode,
    ColorPickerProps,
} from '@/components/color-picker/color-picker-types';
import { DEFAULT_COLOR_PICKER_LABELS } from '@/components/color-picker/color-picker-types';
import {
    type ColorPickerMode,
    type ColorPickerValue,
    DEFAULT_COLOR_PRESETS,
    DEFAULT_ENABLED_MODES,
    DEFAULT_GRADIENT_QUICK_SELECT,
    DEFAULT_SOLID_QUICK_SELECT,
    normalizeColorPickerValue,
    resolveDefaultMode,
    resolveEnabledModes,
    switchColorPickerMode,
} from '@/lib/color-picker';

type UseColorPickerStateOptions = Pick<
    ColorPickerProps,
    | 'id'
    | 'defaultValue'
    | 'value'
    | 'onChange'
    | 'onPendingChange'
    | 'defaultOpen'
    | 'open'
    | 'onOpenChange'
    | 'disabled'
    | 'labels'
    | 'confirmSelection'
    | 'enabledModes'
    | 'defaultMode'
    | 'colorPresets'
    | 'solidQuickSelect'
    | 'gradientQuickSelect'
    | 'showQuickSelect'
    | 'showCustomAngle'
> & {
    mode: ColorPickerPresentationMode;
};

function mergeLabels(
    overrides?: Partial<ColorPickerLabels>,
): ColorPickerLabels {
    return { ...DEFAULT_COLOR_PICKER_LABELS, ...overrides };
}

export function useColorPickerState({
    id,
    defaultValue,
    value,
    onChange,
    onPendingChange,
    defaultOpen = false,
    open: openProp,
    onOpenChange,
    disabled = false,
    labels: labelsProp,
    confirmSelection: confirmSelectionProp,
    enabledModes: enabledModesProp,
    defaultMode: defaultModeProp,
    colorPresets = DEFAULT_COLOR_PRESETS,
    solidQuickSelect = DEFAULT_SOLID_QUICK_SELECT,
    gradientQuickSelect = DEFAULT_GRADIENT_QUICK_SELECT,
    showQuickSelect = true,
    showCustomAngle = true,
    mode,
}: UseColorPickerStateOptions) {
    const reactId = useId();
    const panelId = id ? `${id}-panel` : `${reactId}-panel`;
    const triggerRef = useRef<HTMLButtonElement>(null);

    const labels = useMemo(() => mergeLabels(labelsProp), [labelsProp]);

    const enabledModesKey = (enabledModesProp ?? DEFAULT_ENABLED_MODES).join(
        ',',
    );

    const enabledModes = useMemo(
        () =>
            resolveEnabledModes(
                enabledModesKey.split(',') as ColorPickerMode[],
            ),
        [enabledModesKey],
    );

    const defaultMode = useMemo(
        () => resolveDefaultMode(enabledModes, defaultModeProp),
        [defaultModeProp, enabledModes],
    );

    const normalizeOptions = useMemo(
        () => ({ enabledModes, defaultMode }),
        [defaultMode, enabledModes],
    );

    const confirmSelection =
        confirmSelectionProp ?? (mode === 'dialog' || mode === 'sheet');

    const isControlledValue = value !== undefined;
    const isControlledOpen = openProp !== undefined;

    const [uncontrolledValue, setUncontrolledValue] = useState(() =>
        normalizeColorPickerValue(defaultValue, normalizeOptions),
    );
    const [uncontrolledOpen, setUncontrolledOpen] = useState(defaultOpen);
    const [pendingValue, setPendingValue] = useState<ColorPickerValue | null>(
        null,
    );

    const open = isControlledOpen ? openProp : uncontrolledOpen;

    const committedValue = normalizeColorPickerValue(
        isControlledValue ? value : uncontrolledValue,
        normalizeOptions,
    );

    const workingValue =
        confirmSelection && pendingValue !== null
            ? pendingValue
            : committedValue;

    const setOpen = useCallback(
        (next: boolean) => {
            if (disabled) {
                return;
            }

            if (!isControlledOpen) {
                setUncontrolledOpen(next);
            }

            onOpenChange?.(next);

            if (next && confirmSelection) {
                setPendingValue(committedValue);
                onPendingChange?.(committedValue);
            }

            if (!next) {
                setPendingValue(null);
                onPendingChange?.(null);
            }
        },
        [
            committedValue,
            confirmSelection,
            disabled,
            isControlledOpen,
            onOpenChange,
            onPendingChange,
        ],
    );

    const commitValue = useCallback(
        (next: ColorPickerValue) => {
            const normalized = normalizeColorPickerValue(
                next,
                normalizeOptions,
            );

            if (!isControlledValue) {
                setUncontrolledValue(normalized);
            }

            onChange?.(normalized);
        },
        [isControlledValue, normalizeOptions, onChange],
    );

    const updateWorkingValue = useCallback(
        (next: ColorPickerValue) => {
            const normalized = normalizeColorPickerValue(
                next,
                normalizeOptions,
            );

            if (confirmSelection) {
                setPendingValue(normalized);
                onPendingChange?.(normalized);
                return;
            }

            commitValue(normalized);
        },
        [commitValue, confirmSelection, normalizeOptions, onPendingChange],
    );

    const setMode = useCallback(
        (nextMode: ColorPickerMode) => {
            if (!enabledModes.includes(nextMode)) {
                return;
            }

            updateWorkingValue(switchColorPickerMode(workingValue, nextMode));
        },
        [enabledModes, updateWorkingValue, workingValue],
    );

    const confirmPending = useCallback(() => {
        if (!confirmSelection) {
            setOpen(false);
            return;
        }

        const next = pendingValue ?? committedValue;
        commitValue(next);
        setPendingValue(null);
        onPendingChange?.(null);

        if (!isControlledOpen) {
            setUncontrolledOpen(false);
        }

        onOpenChange?.(false);
    }, [
        commitValue,
        committedValue,
        confirmSelection,
        isControlledOpen,
        onOpenChange,
        onPendingChange,
        pendingValue,
        setOpen,
    ]);

    const resetWorkingValue = useCallback(() => {
        updateWorkingValue(
            normalizeColorPickerValue(null, {
                ...normalizeOptions,
                defaultMode,
            }),
        );
    }, [defaultMode, normalizeOptions, updateWorkingValue]);

    const focus = useCallback(() => {
        triggerRef.current?.focus();
    }, []);

    useEffect(() => {
        if (!isControlledValue) {
            setUncontrolledValue((current) =>
                normalizeColorPickerValue(current, normalizeOptions),
            );
        }
    }, [isControlledValue, normalizeOptions]);

    return {
        panelId,
        triggerRef,
        labels,
        open,
        setOpen,
        disabled,
        confirmSelection,
        enabledModes,
        defaultMode,
        normalizeOptions,
        committedValue,
        workingValue,
        pendingValue,
        updateWorkingValue,
        setMode,
        confirmPending,
        resetWorkingValue,
        commitValue,
        focus,
        colorPresets,
        solidQuickSelect,
        gradientQuickSelect,
        showQuickSelect,
        showCustomAngle,
    };
}

export type ColorPickerState = ReturnType<typeof useColorPickerState>;
