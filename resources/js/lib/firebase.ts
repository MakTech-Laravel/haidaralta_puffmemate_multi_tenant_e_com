import { type FirebaseApp, getApp, getApps, initializeApp } from 'firebase/app';
import { type Messaging, getMessaging, isSupported } from 'firebase/messaging';

export type FirebaseWebConfig = {
    apiKey: string;
    authDomain: string;
    projectId: string;
    storageBucket: string;
    messagingSenderId: string;
    appId: string;
    measurementId?: string;
};

export function getFirebaseWebConfig(): FirebaseWebConfig | null {
    const apiKey = import.meta.env.VITE_FIREBASE_API_KEY;
    const authDomain = import.meta.env.VITE_FIREBASE_AUTH_DOMAIN;
    const projectId = import.meta.env.VITE_FIREBASE_PROJECT_ID;
    const storageBucket = import.meta.env.VITE_FIREBASE_STORAGE_BUCKET;
    const messagingSenderId = import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID;
    const appId = import.meta.env.VITE_FIREBASE_APP_ID;
    const measurementId = import.meta.env.VITE_FIREBASE_MEASUREMENT_ID;

    if (
        !apiKey ||
        !authDomain ||
        !projectId ||
        !storageBucket ||
        !messagingSenderId ||
        !appId
    ) {
        return null;
    }

    return {
        apiKey,
        authDomain,
        projectId,
        storageBucket,
        messagingSenderId,
        appId,
        ...(measurementId ? { measurementId } : {}),
    };
}

export function getFirebaseVapidKey(): string | null {
    const key = import.meta.env.VITE_FIREBASE_VAPID_KEY;

    return key && key.length > 0 ? key : null;
}

export function getFirebaseApp(): FirebaseApp | null {
    const config = getFirebaseWebConfig();

    if (!config) {
        return null;
    }

    return getApps().length > 0 ? getApp() : initializeApp(config);
}

export async function getFirebaseMessaging(): Promise<Messaging | null> {
    if (!(await isSupported())) {
        return null;
    }

    const app = getFirebaseApp();

    if (!app) {
        return null;
    }

    return getMessaging(app);
}
