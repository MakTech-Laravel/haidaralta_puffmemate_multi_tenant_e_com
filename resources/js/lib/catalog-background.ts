import type { CSSProperties } from 'react';

export type CatalogColorMode = 'transparent' | 'solid' | 'gradient';

export type CatalogColorCode =
    | { mode: 'transparent' }
    | { mode: 'solid'; color: string }
    | { mode: 'gradient'; from: string; to: string; angle: number };

export const CATALOG_COLOR_PRESETS = [
    '#E8F0FE',
    '#DBEAFE',
    '#F3E8FF',
    '#FCE7F3',
    '#FEE2E2',
    '#FFEDD5',
    '#FEF9C3',
    '#DCFCE7',
    '#CCFBF1',
    '#E5E7EB',
    '#FFFFFF',
    '#111827',
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
