import { router } from '@inertiajs/react';
import { useSyncExternalStore } from 'react';

const MIN_VISIBLE_MS = 1100;
const EXIT_MS = 280;
const FAILSAFE_MS = 15000;

type OverlayPhase = 'hidden' | 'visible' | 'leaving';

const listeners = new Set<() => void>();

let phase: OverlayPhase = 'hidden';
let isNavigating = false;
let hideTimer: number | undefined;
let failsafeTimer: number | undefined;
let shownAt = 0;

function emit(): void {
    listeners.forEach((listener) => listener());
}

function setPhase(next: OverlayPhase): void {
    if (phase === next) {
        return;
    }

    phase = next;
    emit();
}

function reveal(): void {
    window.clearTimeout(hideTimer);
    shownAt = Date.now();
    setPhase('visible');
}

function conceal(): void {
    if (phase === 'hidden') {
        return;
    }

    if (phase === 'visible') {
        setPhase('leaving');
    }

    window.clearTimeout(hideTimer);
    hideTimer = window.setTimeout(() => {
        if (!isNavigating) {
            setPhase('hidden');
        }
    }, EXIT_MS);
}

function startNavigation(): void {
    isNavigating = true;
    window.clearTimeout(hideTimer);

    if (phase !== 'visible') {
        reveal();
    }

    window.clearTimeout(failsafeTimer);
    failsafeTimer = window.setTimeout(endNavigation, FAILSAFE_MS);
}

function endNavigation(): void {
    window.clearTimeout(failsafeTimer);

    if (!isNavigating) {
        return;
    }

    isNavigating = false;

    const remaining = Math.max(0, MIN_VISIBLE_MS - (Date.now() - shownAt));

    window.clearTimeout(hideTimer);
    hideTimer = window.setTimeout(conceal, remaining);
}

type VisitFlags = {
    async?: boolean;
    prefetch?: boolean;
    only?: string[];
    except?: string[];
};

function isPageVisit(visit: VisitFlags): boolean {
    return (
        visit.async !== true &&
        visit.prefetch !== true &&
        (visit.only?.length ?? 0) === 0 &&
        (visit.except?.length ?? 0) === 0
    );
}

if (typeof window !== 'undefined') {
    // Visits served from the prefetch cache only fire `before` and `navigate`, never `start`/`finish`.
    router.on('before', (event) => {
        if (!isPageVisit(event.detail.visit)) {
            return;
        }

        queueMicrotask(() => {
            if (!event.defaultPrevented) {
                startNavigation();
            }
        });
    });

    router.on('navigate', () => {
        if (isNavigating) {
            endNavigation();
        }
    });

    router.on('finish', (event) => {
        if (isPageVisit(event.detail.visit)) {
            endNavigation();
        }
    });
}

function subscribe(listener: () => void): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

function getPhase(): OverlayPhase {
    return phase;
}

export function NavigationOverlay() {
    const current = useSyncExternalStore(subscribe, getPhase, () => 'hidden');

    if (current === 'hidden') {
        return null;
    }

    return (
        <div
            role="status"
            aria-live="polite"
            aria-busy="true"
            className={
                current === 'leaving'
                    ? 'pointer-events-none fixed inset-0 z-[200] flex items-center justify-center bg-white/72 opacity-0 backdrop-blur-md transition-opacity duration-300'
                    : 'animate-in fade-in fixed inset-0 z-[200] flex items-center justify-center bg-white/72 opacity-100 backdrop-blur-md transition-opacity duration-300'
            }
        >
            <span className="sr-only">Delivering the next page</span>
            <div className="w-[22rem] max-w-[86vw]">
                <svg
                    viewBox="0 0 320 160"
                    className="animate-navigation-ride h-auto w-full overflow-visible"
                    aria-hidden
                >
                    <g className="animate-navigation-parcel">
                        <rect
                            x="86"
                            y="58"
                            width="36"
                            height="28"
                            rx="3"
                            className="fill-background stroke-foreground"
                            strokeWidth="2.5"
                        />
                        <path
                            d="M86 68 h36 M104 58 v28"
                            className="stroke-primary"
                            strokeWidth="2.5"
                        />
                    </g>
                    <path
                        d="M118 92 h18 l8-16 h28 l10 16 h22"
                        fill="none"
                        className="stroke-foreground"
                        strokeWidth="3"
                        strokeLinejoin="round"
                        strokeLinecap="round"
                    />
                    <path
                        d="M146 76 h26 l8 16 H150 Z"
                        className="fill-primary"
                    />
                    <path
                        d="M154 76 v-14 h10 l6 14"
                        className="fill-foreground"
                    />
                    <circle
                        cx="162"
                        cy="70"
                        r="7"
                        className="fill-foreground"
                    />
                    <path
                        d="M156 63 q6-10 12 0"
                        fill="none"
                        className="stroke-foreground"
                        strokeWidth="2"
                    />
                    <g className="animate-navigation-wheel">
                        <circle
                            cx="128"
                            cy="112"
                            r="16"
                            className="fill-background stroke-foreground"
                            strokeWidth="3"
                        />
                        <path
                            d="M128 98 v28 M114 112 h28"
                            className="stroke-foreground/70"
                            strokeWidth="1.5"
                        />
                        <circle
                            cx="128"
                            cy="112"
                            r="3"
                            className="fill-primary"
                        />
                    </g>
                    <g className="animate-navigation-wheel">
                        <circle
                            cx="206"
                            cy="112"
                            r="16"
                            className="fill-background stroke-foreground"
                            strokeWidth="3"
                        />
                        <path
                            d="M206 98 v28 M192 112 h28"
                            className="stroke-foreground/70"
                            strokeWidth="1.5"
                        />
                        <circle
                            cx="206"
                            cy="112"
                            r="3"
                            className="fill-primary"
                        />
                    </g>
                </svg>
                <div
                    className="animate-navigation-road border-foreground/80 mt-1 h-3 border-t-2 bg-[length:2.5rem_100%] bg-repeat-x"
                    style={{
                        backgroundImage:
                            'linear-gradient(90deg, transparent 0 38%, var(--primary) 38% 62%, transparent 62%)',
                    }}
                />
            </div>
        </div>
    );
}
