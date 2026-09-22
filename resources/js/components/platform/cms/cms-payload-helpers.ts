export function newId(prefix = 'item'): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `${prefix}-${Date.now()}-${Math.floor(Math.random() * 10000)}`;
}

export function padNumber(index: number): string {
    return String(index + 1).padStart(2, '0');
}
