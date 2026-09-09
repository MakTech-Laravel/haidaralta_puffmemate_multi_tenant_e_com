import { useSyncExternalStore } from 'react';

export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

/** Site is light-only; dark/system selections always coerce to light. */
const FORCED_APPEARANCE: Appearance = 'light';

const listeners = new Set<() => void>();
let currentAppearance: Appearance = FORCED_APPEARANCE;

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyLightTheme = (): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';
};

const persistLightAppearance = (): void => {
    currentAppearance = FORCED_APPEARANCE;

    if (typeof window !== 'undefined') {
        localStorage.setItem('appearance', FORCED_APPEARANCE);
    }

    setCookie('appearance', FORCED_APPEARANCE);
    applyLightTheme();
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

const notify = (): void => listeners.forEach((listener) => listener());

/**
 * Always force light mode. Ignores stored dark/system prefs and OS preference.
 */
export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    persistLightAppearance();
}

export function useAppearance(): UseAppearanceReturn {
    const appearance: Appearance = useSyncExternalStore(
        subscribe,
        () => currentAppearance,
        () => FORCED_APPEARANCE,
    );

    const updateAppearance = (_mode: Appearance): void => {
        persistLightAppearance();
        notify();
    };

    return {
        appearance,
        resolvedAppearance: 'light',
        updateAppearance,
    } as const;
}
