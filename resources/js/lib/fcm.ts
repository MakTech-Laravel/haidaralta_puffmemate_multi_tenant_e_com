import { getToken, onMessage, type MessagePayload } from 'firebase/messaging';
import {
    getFirebaseMessaging,
    getFirebaseVapidKey,
} from '@/lib/firebase';

const SERVICE_WORKER_URL = '/firebase-messaging-sw.js';

export async function registerFcmServiceWorker(): Promise<ServiceWorkerRegistration | null> {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    return navigator.serviceWorker.register(SERVICE_WORKER_URL, {
        scope: '/',
    });
}

export async function requestFcmToken(): Promise<string | null> {
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

    await navigator.serviceWorker.ready;

    return getToken(messaging, {
        vapidKey,
        serviceWorkerRegistration: registration,
    });
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
