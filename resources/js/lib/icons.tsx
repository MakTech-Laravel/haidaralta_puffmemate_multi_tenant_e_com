import type { LucideProps } from 'lucide-react';
import { iconNames } from 'lucide-react/dynamic';
import { createElement, useMemo } from 'react';

import { CachedLucideIcon } from '@/components/icons/cached-lucide-icon';

const VALID_LUCIDE_KEYS = new Set<string>(iconNames);
const DEFAULT_ICON_KEY = 'pen-line';

export type IconKey = string;

const ICON_ALIASES: Record<string, string> = {
    funnel: 'filter',
};

/** PascalCase / camelCase Lucide component names → kebab-case catalog keys. */
function toKebabIconKey(value: string): string {
    return value
        .replace(/([a-z\d])([A-Z])/g, '$1-$2')
        .replace(/([A-Z]+)([A-Z][a-z])/g, '$1-$2')
        .replace(/([a-zA-Z])(\d+)/g, '$1-$2')
        .toLowerCase();
}

function normalizeIconKey(value: string): string {
    const aliased = ICON_ALIASES[value] ?? value;

    if (VALID_LUCIDE_KEYS.has(aliased)) {
        return aliased;
    }

    const kebab = toKebabIconKey(aliased);

    if (VALID_LUCIDE_KEYS.has(kebab)) {
        return kebab;
    }

    return aliased;
}

export function isIconKey(value: string | null | undefined): value is IconKey {
    if (!value) {
        return false;
    }

    return VALID_LUCIDE_KEYS.has(normalizeIconKey(value));
}

export function isValidLucideIconKey(
    value: string | null | undefined,
): boolean {
    if (!value) {
        return false;
    }

    return VALID_LUCIDE_KEYS.has(normalizeIconKey(value));
}

/**
 * Normalize PascalCase / aliases to a Lucide catalog key, or null if unknown.
 */
export function coerceValidLucideIconKey(
    value: string | null | undefined,
): string | null {
    if (!value) {
        return null;
    }

    const normalized = normalizeIconKey(value);

    return VALID_LUCIDE_KEYS.has(normalized) ? normalized : null;
}

export function resolveIconKey(
    value: string | null | undefined,
    fallback: string = DEFAULT_ICON_KEY,
): string {
    const safeFallback = VALID_LUCIDE_KEYS.has(fallback)
        ? fallback
        : DEFAULT_ICON_KEY;

    if (!value) {
        return safeFallback;
    }

    const normalized = normalizeIconKey(value);

    if (VALID_LUCIDE_KEYS.has(normalized)) {
        return normalized;
    }

    return safeFallback;
}

export function getIconLabel(key: string): string {
    return key
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

export function Icon({
    icon,
    className,
    ...props
}: {
    icon?: string | null;
    className?: string;
} & Omit<LucideProps, 'ref'>) {
    const name = useMemo(() => resolveIconKey(icon), [icon]);

    return (
        <CachedLucideIcon
            name={name}
            className={className}
            aria-hidden={props['aria-hidden'] ?? true}
            {...props}
        />
    );
}

/** @deprecated Use <Icon /> for lazy-loaded icons. */
export function getIcon(icon?: string | null) {
    const name = resolveIconKey(icon);

    return function IconComponent(props: LucideProps) {
        return createElement(Icon, { icon: name, ...props });
    };
}
