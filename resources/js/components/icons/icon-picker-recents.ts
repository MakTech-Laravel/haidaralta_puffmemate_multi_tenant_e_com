const STORAGE_PREFIX = 'puffmemate.lucide-icon-picker.recents';
const MAX_RECENTS = 12;

function storageKey(scope: string): string {
    return `${STORAGE_PREFIX}:${scope || 'global'}`;
}

export function readRecentIcons(scope = 'global'): string[] {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const raw = window.localStorage.getItem(storageKey(scope));

        if (!raw) {
            return [];
        }

        const parsed = JSON.parse(raw) as unknown;

        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed.filter(
            (item): item is string => typeof item === 'string',
        );
    } catch {
        return [];
    }
}

export function pushRecentIcon(key: string, scope = 'global'): string[] {
    const next = [
        key,
        ...readRecentIcons(scope).filter((item) => item !== key),
    ].slice(0, MAX_RECENTS);

    if (typeof window !== 'undefined') {
        try {
            window.localStorage.setItem(
                storageKey(scope),
                JSON.stringify(next),
            );
        } catch {
            // Ignore quota / private-mode failures.
        }
    }

    return next;
}

export function clearRecentIcons(scope = 'global'): string[] {
    if (typeof window !== 'undefined') {
        try {
            window.localStorage.removeItem(storageKey(scope));
        } catch {
            // Ignore.
        }
    }

    return [];
}
