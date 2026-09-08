import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'reverb'> | undefined;
    }
}

if (typeof window !== 'undefined') {
    window.Pusher = Pusher;
}

export type AppEcho = Echo<'reverb'>;

export type ReverbClientConfig = {
    enabled: boolean;
    appKey: string;
    host: string;
    port: number;
    scheme: string;
    useTls: boolean;
    enabledTransports: Array<'ws' | 'wss'>;
    authEndpoint: string;
};

function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export function getReverbClientConfig(): ReverbClientConfig {
    const appKey = String(import.meta.env.VITE_REVERB_APP_KEY ?? '').trim();
    const host = String(import.meta.env.VITE_REVERB_HOST ?? '').trim();
    const scheme = String(import.meta.env.VITE_REVERB_SCHEME ?? 'https').trim();
    const port = Number(
        import.meta.env.VITE_REVERB_PORT ?? (scheme === 'https' ? 443 : 80),
    );
    const useTls = scheme === 'https' || scheme === 'wss';

    return {
        enabled: appKey !== '' && host !== '',
        appKey,
        host,
        port: Number.isFinite(port) ? port : useTls ? 443 : 80,
        scheme,
        useTls,
        // pusher-js names the primary WebSocket transport "ws" even when forceTLS
        // is true (it still uses wss:// via hostTLS). Enabling only "wss" disables
        // that path and leaves XHR/SockJS fallbacks — which Reverb does not speak.
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
    };
}

function isBrowser(): boolean {
    return typeof window !== 'undefined';
}

/**
 * Build a new Echo instance for the standalone Reverb server.
 * Uses Fortify session cookies + CSRF for private channel auth.
 * Browser-only — never instantiate during Inertia/Vite SSR.
 */
export function createEcho(): AppEcho | null {
    if (!isBrowser()) {
        return null;
    }

    const config = getReverbClientConfig();

    if (!config.enabled) {
        return null;
    }

    window.Pusher = Pusher;

    return new Echo({
        broadcaster: 'reverb',
        key: config.appKey,
        wsHost: config.host,
        wsPort: config.port,
        wssPort: config.port,
        forceTLS: config.useTls,
        enabledTransports: config.enabledTransports,
        disableStats: true,
        authEndpoint: config.authEndpoint,
        // Avoid "Pusher client not found" when window.Pusher is unset (SSR/HMR).
        client: Pusher,
        auth: {
            headers: (() => {
                const headers: Record<string, string> = {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                };

                Object.defineProperty(headers, 'X-XSRF-TOKEN', {
                    get(): string {
                        return getXsrfToken();
                    },
                    enumerable: true,
                    configurable: true,
                });

                return headers;
            })(),
        },
        withCredentials: true,
    });
}

/** Bumped whenever the singleton is torn down / recreated. */
let echoGeneration = 0;

let echoInstance: AppEcho | null | undefined;

export function getEchoGeneration(): number {
    return echoGeneration;
}

export function getEcho(): AppEcho | null {
    if (echoInstance === undefined) {
        echoInstance = createEcho();
        if (typeof window !== 'undefined') {
            window.Echo = echoInstance ?? undefined;
        }
    }

    return echoInstance;
}

export function disconnectEcho(): void {
    if (echoInstance) {
        echoInstance.disconnect();
    }

    echoInstance = undefined;
    echoGeneration += 1;

    if (typeof window !== 'undefined') {
        window.Echo = undefined;
    }
}

/**
 * Tear down a stuck/failed Pusher client and open a new WebSocket.
 */
export function reconnectEcho(): AppEcho | null {
    disconnectEcho();
    const echo = getEcho();

    if (typeof window !== 'undefined') {
        window.dispatchEvent(
            new CustomEvent('echo:reconnected', {
                detail: { generation: echoGeneration },
            }),
        );
    }

    return echo;
}

export function logEchoConfig(): void {
    if (!import.meta.env.DEV) {
        return;
    }

    const config = getReverbClientConfig();
    console.info('[echo] config', {
        enabled: config.enabled,
        appKey: config.appKey ? `${config.appKey.slice(0, 8)}...` : 'MISSING',
        host: config.host || 'MISSING',
        port: config.port,
        scheme: config.scheme,
        forceTLS: config.useTls,
        enabledTransports: config.enabledTransports,
        authEndpoint: config.authEndpoint,
    });
}
