import { router } from '@inertiajs/react';

export type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

export const PWA_BANNER_DISMISSED_KEY = 'pwa-install-banner-dismissed';

const PWA_SERVICE_WORKER_URL = '/firebase-messaging-sw.js';

let deferredInstallPrompt: BeforeInstallPromptEvent | null = null;
let installPromptCaptured = false;
const installPromptListeners = new Set<
    (event: BeforeInstallPromptEvent | null) => void
>();

export function getDeferredInstallPrompt(): BeforeInstallPromptEvent | null {
    return deferredInstallPrompt;
}

export function clearDeferredInstallPrompt(): void {
    deferredInstallPrompt = null;
    installPromptListeners.forEach((listener) => listener(null));
}

export function subscribeInstallPrompt(
    listener: (event: BeforeInstallPromptEvent | null) => void,
): () => void {
    installPromptListeners.add(listener);

    return () => {
        installPromptListeners.delete(listener);
    };
}

/**
 * Chrome only emits beforeinstallprompt after the service worker is active.
 * Listen before React mounts so a prompt that arrives during boot is not lost.
 */
export function captureInstallPrompt(): void {
    if (typeof window === 'undefined' || installPromptCaptured) {
        return;
    }

    installPromptCaptured = true;

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstallPrompt = event as BeforeInstallPromptEvent;
        installPromptListeners.forEach((listener) =>
            listener(deferredInstallPrompt),
        );
    });

    window.addEventListener('appinstalled', () => {
        clearDeferredInstallPrompt();
    });
}

function registerPwaServiceWorker(): Promise<ServiceWorkerRegistration> {
    return navigator.serviceWorker.register(PWA_SERVICE_WORKER_URL, {
        scope: '/',
        updateViaCache: 'none',
    });
}

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

    const registration = registerPwaServiceWorker();
    await unregisterForeignServiceWorkers();

    return registration;
}

export function startPwaServiceWorkerLifecycle(): void {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }

    captureInstallPrompt();

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
