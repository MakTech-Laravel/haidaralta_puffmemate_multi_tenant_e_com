import { getToken, onMessage, type MessagePayload } from 'firebase/messaging';
import {
    getFirebaseMessaging,
    getFirebaseVapidKey,
} from '@/lib/firebase';

const SERVICE_WORKER_URL = '/firebase-messaging-sw.js';

const FIREBASE_IDB_NAMES = [
    'firebase-heartbeat-database',
    'firebase-installations-database',
    'firebase-messaging-database',
] as const;

async function clearFirebaseIndexedDatabases(): Promise<void> {
    if (!('indexedDB' in window)) {
        return;
    }

    await Promise.all(
        FIREBASE_IDB_NAMES.map(
            (name) =>
                new Promise<void>((resolve) => {
                    const request = indexedDB.deleteDatabase(name);
                    request.onsuccess = () => resolve();
                    request.onerror = () => resolve();
                    request.onblocked = () => resolve();
                }),
        ),
    );
}

function waitForServiceWorkerActivation(
    worker: ServiceWorker | null,
): Promise<void> {
    if (!worker || worker.state === 'activated') {
        return Promise.resolve();
    }

    return new Promise((resolve) => {
        worker.addEventListener('statechange', () => {
            if (worker.state === 'activated') {
                resolve();
            }
        });
    });
}

export async function registerFcmServiceWorker(): Promise<ServiceWorkerRegistration | null> {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    const registration = await navigator.serviceWorker.register(
        SERVICE_WORKER_URL,
        {
            scope: '/',
            updateViaCache: 'none',
        },
    );

    await waitForServiceWorkerActivation(
        registration.installing ?? registration.waiting ?? registration.active,
    );
    await navigator.serviceWorker.ready;

    return registration;
}

async function getTokenWithRegistration(): Promise<string | null> {
    const messaging = await getFirebaseMessaging();
    const vapidKey = getFirebaseVapidKey();

    if (!messaging || !vapidKey) {
        return null;
    }

    if (Notification.permission === 'default') {
        await Notification.requestPermission();
    }

    if (Notification.permission !== 'granted') {
        return null;
    }

    const registration = await registerFcmServiceWorker();

    if (!registration) {
        return null;
    }

    return getToken(messaging, {
        vapidKey,
        serviceWorkerRegistration: registration,
    });
}

export async function requestFcmToken(): Promise<string | null> {
    try {
        return await getTokenWithRegistration();
    } catch (error) {
        const message = error instanceof Error ? error.message : String(error);

        if (!message.includes('token-subscribe-failed')) {
            throw error;
        }

        // Stale Firebase Installations auth in IndexedDB often surfaces as a
        // misleading "missing OAuth credential" 401 on fcmregistrations.
        await clearFirebaseIndexedDatabases();

        const registrations = await navigator.serviceWorker.getRegistrations();

        await Promise.all(
            registrations
                .filter((registration) =>
                    registration.active?.scriptURL.includes(
                        'firebase-messaging-sw.js',
                    ),
                )
                .map((registration) => registration.unregister()),
        );

        return getTokenWithRegistration();
    }
}

export async function listenForegroundMessages(
    onPayload: (payload: MessagePayload) => void,
): Promise<() => void> {
    const messaging = await getFirebaseMessaging();

    if (!messaging) {
        return () => {};
    }

    return onMessage(messaging, onPayload);
}
