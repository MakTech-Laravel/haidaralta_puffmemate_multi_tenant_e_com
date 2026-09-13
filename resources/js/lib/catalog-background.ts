import type { CSSProperties } from 'react';

export type CatalogColorMode = 'transparent' | 'solid' | 'gradient';

export type CatalogColorCode =
    | { mode: 'transparent' }
    | { mode: 'solid'; color: string }
    | { mode: 'gradient'; from: string; to: string; angle: number };

/** Quick-select palette: white, gray, black, red (+ related shades). */
export const CATALOG_COLOR_PRESETS = [
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

export const CATALOG_QUICK_SELECT = [
    { label: 'White', color: '#FFFFFF' },
    { label: 'Light gray', color: '#E5E7EB' },
    { label: 'Gray', color: '#6B7280' },
    { label: 'Dark gray', color: '#1F2937' },
    { label: 'Black', color: '#000000' },
    { label: 'Light red', color: '#FECACA' },
    { label: 'Red', color: '#EF4444' },
    { label: 'Deep red', color: '#991B1B' },
] as const;

/** Gradient quick-select pairs (white / gray / black / red family). */
export const CATALOG_GRADIENT_QUICK_SELECT = [
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

export const CATALOG_GRADIENT_ANGLES = [0, 45, 90, 135, 180, 225, 270] as const;

export function normalizeCatalogColorCode(
    value?: CatalogColorCode | null,
): CatalogColorCode {
    if (!value || !value.mode) {
        return { mode: 'transparent' };
    }

    if (value.mode === 'solid') {
        return {
            mode: 'solid',
            color: normalizeHex(value.color) ?? '#E5E7EB',
        };
    }

    if (value.mode === 'gradient') {
        return {
            mode: 'gradient',
            from: normalizeHex(value.from) ?? '#E5E7EB',
            to: normalizeHex(value.to) ?? '#FFFFFF',
            angle: clampAngle(value.angle ?? 135),
        };
    }

    return { mode: 'transparent' };
}

export function catalogBackgroundStyle(
    value?: CatalogColorCode | null,
): CSSProperties {
    const normalized = normalizeCatalogColorCode(value);

    if (normalized.mode === 'solid') {
        return { background: normalized.color };
    }

    if (normalized.mode === 'gradient') {
        return {
            backgroundImage: `linear-gradient(${normalized.angle}deg, ${normalized.from}, ${normalized.to})`,
        };
    }

    return {};
}

export function catalogBackgroundCssValue(
    value?: CatalogColorCode | null,
): string | undefined {
    const normalized = normalizeCatalogColorCode(value);

    if (normalized.mode === 'solid') {
        return normalized.color;
    }

    if (normalized.mode === 'gradient') {
        return `linear-gradient(${normalized.angle}deg, ${normalized.from}, ${normalized.to})`;
    }

    return undefined;
}

export function isTransparentCatalogColor(
    value?: CatalogColorCode | null,
): boolean {
    return normalizeCatalogColorCode(value).mode === 'transparent';
}

export function isLightCatalogHex(hex: string): boolean {
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

function normalizeHex(value?: string | null): string | null {
    if (!value) {
        return null;
    }

    const hex = value.trim().toUpperCase();

    return /^#[0-9A-F]{6}$/.test(hex) ? hex : null;
}

function clampAngle(angle: number): number {
    if (Number.isNaN(angle)) {
        return 135;
    }

    return Math.min(360, Math.max(0, Math.round(angle)));
}
