import { router } from '@inertiajs/react';

export type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

export const PWA_BANNER_DISMISSED_KEY = 'pwa-install-banner-dismissed';

const OUR_SW_SCRIPT = 'firebase-messaging-sw.js';

export function isStandaloneDisplay(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    if (window.matchMedia('(display-mode: standalone)').matches) {
        return true;
    }

    const navigatorWithStandalone = window.navigator as Navigator & {
        standalone?: boolean;
    };

    return navigatorWithStandalone.standalone === true;
}

export function isIosDevice(): boolean {
    if (typeof navigator === 'undefined') {
        return false;
    }

    const ua = navigator.userAgent;

    if (/iPhone|iPad|iPod/i.test(ua)) {
        return true;
    }

    return navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
}

export function isIosSafari(): boolean {
    if (!isIosDevice()) {
        return false;
    }

    const ua = navigator.userAgent;

    return (
        /Safari/i.test(ua) &&
        !/CriOS|FxiOS|EdgiOS|OPiOS|DuckDuckGo|YaBrowser/i.test(ua)
    );
}

export function isInAppBrowser(): boolean {
    if (typeof navigator === 'undefined') {
        return false;
    }

    return /FBAN|FBAV|Instagram|Line\/|Twitter|Snapchat|GSA\//i.test(
        navigator.userAgent,
    );
}

function scriptUrl(worker: ServiceWorker | null): string {
    return worker?.scriptURL ?? '';
}

function isOurServiceWorker(
    registration: ServiceWorkerRegistration,
): boolean {
    return [registration.active, registration.waiting, registration.installing]
        .map(scriptUrl)
        .some((url) => url.includes(OUR_SW_SCRIPT));
}

export async function unregisterForeignServiceWorkers(): Promise<void> {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    const registrations = await navigator.serviceWorker.getRegistrations();

    await Promise.all(
        registrations
            .filter((registration) => !isOurServiceWorker(registration))
            .map((registration) => registration.unregister()),
    );
}

export async function ensurePwaServiceWorker(): Promise<ServiceWorkerRegistration | null> {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    await unregisterForeignServiceWorkers();

    const { registerFcmServiceWorker } = await import('@/lib/fcm');

    return registerFcmServiceWorker();
}

export function startPwaServiceWorkerLifecycle(): void {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }

    const hadController = navigator.serviceWorker.controller !== null;
    let pendingReload = false;

    const isCheckout = (): boolean =>
        window.location.pathname.startsWith('/checkout');

    const applyReload = (): void => {
        if (!pendingReload || isCheckout()) {
            return;
        }

        pendingReload = false;
        window.location.reload();
    };

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!hadController) {
            return;
        }

        pendingReload = true;

        if (document.visibilityState === 'visible' && !isCheckout()) {
            applyReload();
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            applyReload();
        }
    });

    void ensurePwaServiceWorker().then((registration) => {
        if (!registration) {
            return;
        }

        const update = (): void => {
            void registration.update();
        };

        update();
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                update();
            }
        });
        window.addEventListener('pageshow', update);
    });

    router.on('success', () => {
        applyReload();
    });
}
