import { useSyncExternalStore } from 'react';

export type DriverPosition = {
    lat: number;
    lng: number;
    ts: number;
};

let current: DriverPosition | null = null;
const listeners = new Set<() => void>();

/**
 * The tracker publishes each GPS fix here, so screens such as the route map can show "You"
 * without opening a second location watch on the device.
 */
export function publishDriverPosition(position: DriverPosition): void {
    current = position;
    listeners.forEach((listener) => listener());
}

function subscribe(listener: () => void): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

export function useDriverPosition(): DriverPosition | null {
    return useSyncExternalStore(
        subscribe,
        () => current,
        () => null,
    );
}
