export interface StoredError {
    id: string;
    message: string;
    stack: string;
    componentStack: string | null;
    timestamp: number;
    url: string;
    count: number;
}

const STORAGE_KEY = 'dev_error_boundary_history';
const MAX_ERRORS = 20;

function generateId(): string {
    return `err_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`;
}

export function getStoredErrors(): StoredError[] {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);

        return raw ? (JSON.parse(raw) as StoredError[]) : [];
    } catch {
        return [];
    }
}

export function pushError(
    error: unknown,
    componentStack: string | null,
): StoredError {
    const message = error instanceof Error ? error.message : String(error);
    const stack =
        error instanceof Error
            ? (error.stack ?? 'No stack trace available')
            : String(error);

    const existing = getStoredErrors();

    // De-duplicate by message+stack fingerprint
    const fingerprint = `${message}|${stack.slice(0, 200)}`;
    const dupIdx = existing.findIndex(
        (e) => `${e.message}|${e.stack.slice(0, 200)}` === fingerprint,
    );

    if (dupIdx !== -1) {
        existing[dupIdx].count += 1;
        existing[dupIdx].timestamp = Date.now();
        saveErrors(existing);

        return existing[dupIdx];
    }

    const entry: StoredError = {
        id: generateId(),
        message,
        stack,
        componentStack,
        timestamp: Date.now(),
        url: window.location.href,
        count: 1,
    };

    const next = [entry, ...existing].slice(0, MAX_ERRORS);
    saveErrors(next);

    return entry;
}

export function clearErrors(): void {
    sessionStorage.removeItem(STORAGE_KEY);
}

export function removeError(id: string): void {
    const existing = getStoredErrors().filter((e) => e.id !== id);
    saveErrors(existing);
}

function saveErrors(errors: StoredError[]): void {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(errors));
    } catch {
        // quota exceeded — drop oldest
    }
}
