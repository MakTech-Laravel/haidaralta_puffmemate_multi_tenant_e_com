import type { CSSProperties } from 'react';

export type ColorPickerMode =
    | 'transparent'
    | 'solid'
    | 'gradient'
    | 'radial';

export type ColorPickerValue =
    | { mode: 'transparent' }
    | { mode: 'solid'; color: string }
    | { mode: 'gradient'; from: string; to: string; angle: number }
    | {
          mode: 'radial';
          from: string;
          to: string;
          position?: string;
      };

export type SolidQuickSelectOption = {
    label: string;
    color: string;
};

export type GradientQuickSelectOption = {
    label: string;
    from: string;
    to: string;
    angle: number;
};

export type NormalizeColorPickerOptions = {
    enabledModes?: readonly ColorPickerMode[];
    defaultMode?: ColorPickerMode;
};

/** Stable mode card order. */
export const COLOR_PICKER_MODE_ORDER: readonly ColorPickerMode[] = [
    'transparent',
    'solid',
    'gradient',
    'radial',
] as const;

export const DEFAULT_ENABLED_MODES: readonly ColorPickerMode[] = [
    'solid',
] as const;

export const DEFAULT_RADIAL_POSITION = 'circle at center';

/** Default swatch palette: white, gray, black, red (+ related shades). */
export const DEFAULT_COLOR_PRESETS = [
    '#FFFFFF',
    '#FAFAFA',
    '#F5F5F5',
    '#E5E7EB',
    '#D1D5DB',
    '#9CA3AF',
    '#6B7280',
    '#4B5563',
    '#1F2937',
    '#111827',
    '#000000',
    '#FEF2F2',
    '#FECACA',
    '#F87171',
    '#EF4444',
    '#DC2626',
    '#B91C1C',
    '#991B1B',
] as const;

export const DEFAULT_SOLID_QUICK_SELECT: readonly SolidQuickSelectOption[] = [
    { label: 'White', color: '#FFFFFF' },
    { label: 'Light gray', color: '#E5E7EB' },
    { label: 'Gray', color: '#6B7280' },
    { label: 'Dark gray', color: '#1F2937' },
    { label: 'Black', color: '#000000' },
    { label: 'Light red', color: '#FECACA' },
    { label: 'Red', color: '#EF4444' },
    { label: 'Deep red', color: '#991B1B' },
] as const;

/** Product create form default — matches the ColorPicker “Red” quick select. */
export const DEFAULT_CATALOG_SOLID_COLOR = '#EF4444';

export function defaultCatalogColorPickerValue(): ColorPickerValue {
    return { mode: 'solid', color: DEFAULT_CATALOG_SOLID_COLOR };
}

export const DEFAULT_GRADIENT_QUICK_SELECT: readonly GradientQuickSelectOption[] =
    [
        {
            label: 'White wash',
            from: '#FFFFFF',
            to: '#E5E7EB',
            angle: 135,
        },
        {
            label: 'Soft gray',
            from: '#E5E7EB',
            to: '#FFFFFF',
            angle: 135,
        },
        {
            label: 'Steel fade',
            from: '#6B7280',
            to: '#E5E7EB',
            angle: 135,
        },
        {
            label: 'Charcoal',
            from: '#1F2937',
            to: '#6B7280',
            angle: 135,
        },
        {
            label: 'Black fade',
            from: '#000000',
            to: '#4B5563',
            angle: 135,
        },
        {
            label: 'Blush',
            from: '#FECACA',
            to: '#FFFFFF',
            angle: 135,
        },
        {
            label: 'Red glow',
            from: '#EF4444',
            to: '#FEE2E2',
            angle: 135,
        },
        {
            label: 'Deep crimson',
            from: '#991B1B',
            to: '#FECACA',
            angle: 135,
        },
    ] as const;

export const DEFAULT_GRADIENT_ANGLE_PRESETS = [
    0, 45, 90, 135, 180, 225, 270,
] as const;

export function normalizeHex(value?: string | null): string | null {
    if (!value) {
        return null;
    }

    const hex = value.trim().toUpperCase();

    return /^#[0-9A-F]{6}$/.test(hex) ? hex : null;
}

export function clampAngle(angle: number): number {
    if (Number.isNaN(angle)) {
        return 135;
    }

    return Math.min(360, Math.max(0, Math.round(angle)));
}

export function resolveEnabledModes(
    enabledModes?: readonly ColorPickerMode[] | null,
): ColorPickerMode[] {
    const requested =
        enabledModes && enabledModes.length > 0
            ? enabledModes
            : DEFAULT_ENABLED_MODES;

    const unique = new Set(requested);

    return COLOR_PICKER_MODE_ORDER.filter((mode) => unique.has(mode));
}

export function resolveDefaultMode(
    enabledModes: readonly ColorPickerMode[],
    defaultMode?: ColorPickerMode | null,
): ColorPickerMode {
    if (defaultMode && enabledModes.includes(defaultMode)) {
        return defaultMode;
    }

    return enabledModes[0] ?? 'solid';
}

function valueForMode(
    mode: ColorPickerMode,
    source?: ColorPickerValue | null,
): ColorPickerValue {
    if (mode === 'transparent') {
        return { mode: 'transparent' };
    }

    if (mode === 'solid') {
        const color =
            source?.mode === 'solid'
                ? (normalizeHex(source.color) ?? '#E5E7EB')
                : source?.mode === 'gradient' || source?.mode === 'radial'
                  ? (normalizeHex(source.from) ?? '#E5E7EB')
                  : '#E5E7EB';

        return { mode: 'solid', color };
    }

    if (mode === 'radial') {
        const from =
            source?.mode === 'radial' || source?.mode === 'gradient'
                ? (normalizeHex(source.from) ?? '#E5E7EB')
                : source?.mode === 'solid'
                  ? (normalizeHex(source.color) ?? '#E5E7EB')
                  : '#E5E7EB';
        const to =
            source?.mode === 'radial' || source?.mode === 'gradient'
                ? (normalizeHex(source.to) ?? '#FFFFFF')
                : '#FFFFFF';
        const position =
            source?.mode === 'radial'
                ? (source.position ?? DEFAULT_RADIAL_POSITION)
                : DEFAULT_RADIAL_POSITION;

        return { mode: 'radial', from, to, position };
    }

    const from =
        source?.mode === 'gradient' || source?.mode === 'radial'
            ? (normalizeHex(source.from) ?? '#E5E7EB')
            : source?.mode === 'solid'
              ? (normalizeHex(source.color) ?? '#E5E7EB')
              : '#E5E7EB';
    const to =
        source?.mode === 'gradient' || source?.mode === 'radial'
            ? (normalizeHex(source.to) ?? '#FFFFFF')
            : '#FFFFFF';
    const angle =
        source?.mode === 'gradient' ? clampAngle(source.angle ?? 135) : 135;

    return { mode: 'gradient', from, to, angle };
}

/**
 * Structural coerce for any stored value (no enabledModes filter).
 * Empty / unknown → transparent. Use for display helpers.
 */
export function parseColorPickerValue(
    value?: ColorPickerValue | null,
): ColorPickerValue {
    if (!value || !value.mode) {
        return { mode: 'transparent' };
    }

    if (
        value.mode === 'transparent' ||
        value.mode === 'solid' ||
        value.mode === 'gradient' ||
        value.mode === 'radial'
    ) {
        return valueForMode(value.mode, value);
    }

    return { mode: 'transparent' };
}

/**
 * Coerce for picker state: disabled / invalid modes fall back to defaultMode.
 */
export function normalizeColorPickerValue(
    value?: ColorPickerValue | null,
    options?: NormalizeColorPickerOptions,
): ColorPickerValue {
    const enabledModes = resolveEnabledModes(options?.enabledModes);
    const defaultMode = resolveDefaultMode(enabledModes, options?.defaultMode);
    const parsed = parseColorPickerValue(value);

    if (!enabledModes.includes(parsed.mode)) {
        return valueForMode(defaultMode, parsed);
    }

    return parsed;
}

export function switchColorPickerMode(
    current: ColorPickerValue,
    nextMode: ColorPickerMode,
): ColorPickerValue {
    return valueForMode(nextMode, current);
}

export function colorPickerStyle(
    value?: ColorPickerValue | null,
): CSSProperties {
    const normalized = parseColorPickerValue(value);

    if (normalized.mode === 'solid') {
        return { background: normalized.color };
    }

    if (normalized.mode === 'gradient') {
        return {
            backgroundImage: `linear-gradient(${normalized.angle}deg, ${normalized.from}, ${normalized.to})`,
        };
    }

    if (normalized.mode === 'radial') {
        const position = normalized.position ?? DEFAULT_RADIAL_POSITION;

        return {
            backgroundImage: `radial-gradient(${position}, ${normalized.from}, ${normalized.to})`,
        };
    }

    return {};
}

export function colorPickerCssValue(
    value?: ColorPickerValue | null,
): string | undefined {
    const normalized = parseColorPickerValue(value);

    if (normalized.mode === 'solid') {
        return normalized.color;
    }

    if (normalized.mode === 'gradient') {
        return `linear-gradient(${normalized.angle}deg, ${normalized.from}, ${normalized.to})`;
    }

    if (normalized.mode === 'radial') {
        const position = normalized.position ?? DEFAULT_RADIAL_POSITION;

        return `radial-gradient(${position}, ${normalized.from}, ${normalized.to})`;
    }

    return undefined;
}

export function isTransparentColorPickerValue(
    value?: ColorPickerValue | null,
): boolean {
    return parseColorPickerValue(value).mode === 'transparent';
}

/** Catalog listings only persist transparent | solid | gradient. */
export const CATALOG_COLOR_PICKER_MODES: readonly ColorPickerMode[] = [
    'transparent',
    'solid',
    'gradient',
] as const;

/**
 * Display / stored values: empty → transparent (do not invent a fill).
 * Product *create* uses defaultCatalogColorPickerValue() instead.
 */
export function normalizeCatalogColorPickerValue(
    value?: ColorPickerValue | null,
): ColorPickerValue {
    return normalizeColorPickerValue(value, {
        enabledModes: [...CATALOG_COLOR_PICKER_MODES],
        defaultMode: 'transparent',
    });
}

export function productFormColorPickerValue(
    stored?: ColorPickerValue | null,
    isEdit = false,
): ColorPickerValue {
    if (isEdit || stored) {
        return normalizeCatalogColorPickerValue(stored);
    }

    return defaultCatalogColorPickerValue();
}

export function isLightHex(hex: string): boolean {
    const value = hex.replace('#', '');

    if (value.length !== 6) {
        return true;
    }

    const r = Number.parseInt(value.slice(0, 2), 16);
    const g = Number.parseInt(value.slice(2, 4), 16);
    const b = Number.parseInt(value.slice(4, 6), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    return luminance > 0.65;
}

export function colorPickerSummary(value: ColorPickerValue): string {
    if (value.mode === 'transparent') {
        return 'Transparent';
    }

    if (value.mode === 'solid') {
        return `Solid · ${value.color}`;
    }

    if (value.mode === 'radial') {
        return 'Radial';
    }

    return `Gradient · ${value.angle}°`;
}
