import { router } from '@inertiajs/react';
import { useSyncExternalStore } from 'react';
import { cn } from '@/lib/utils';

/** Same default delay as Inertia's progress bar: visits that settle sooner show nothing. */
const SHOW_DELAY_MS = 250;
const FAILSAFE_MS = 60000;
/** Fallback only if `transitionend` does not fire. Visual fade is CSS `duration-300`. */
const FADE_FALLBACK_MS = 350;

type OverlayPhase = 'hidden' | 'entering' | 'visible' | 'leaving';

const listeners = new Set<() => void>();

let phase: OverlayPhase = 'hidden';
let activeVisitId: string | null = null;
let showTimer: number | undefined;
let hideTimer: number | undefined;
let failsafeTimer: number | undefined;
let enterFrame: number | undefined;

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

function prefersReducedMotion(): boolean {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function cancelEnterFrame(): void {
    if (enterFrame !== undefined) {
        window.cancelAnimationFrame(enterFrame);
        enterFrame = undefined;
    }
}

function reveal(): void {
    window.clearTimeout(hideTimer);
    cancelEnterFrame();

    if (prefersReducedMotion()) {
        setPhase('visible');

        return;
    }

    if (phase === 'hidden') {
        setPhase('entering');
        enterFrame = window.requestAnimationFrame(() => {
            enterFrame = window.requestAnimationFrame(() => {
                enterFrame = undefined;

                if (phase === 'entering') {
                    setPhase('visible');
                }
            });
        });

        return;
    }

    setPhase('visible');
}

function conceal(): void {
    if (phase === 'hidden') {
        return;
    }

    cancelEnterFrame();
    window.clearTimeout(hideTimer);

    if (prefersReducedMotion() || phase === 'entering') {
        setPhase('hidden');

        return;
    }

    setPhase('leaving');
    hideTimer = window.setTimeout(() => {
        if (phase === 'leaving' && activeVisitId === null) {
            setPhase('hidden');
        }
    }, FADE_FALLBACK_MS);
}

function completeFade(propertyName: string): void {
    if (propertyName !== 'opacity') {
        return;
    }

    if (phase === 'leaving' && activeVisitId === null) {
        window.clearTimeout(hideTimer);
        setPhase('hidden');
    }
}

function startNavigation(visitId: string): void {
    activeVisitId = visitId;
    window.clearTimeout(hideTimer);
    window.clearTimeout(showTimer);

    if (phase === 'hidden') {
        showTimer = window.setTimeout(reveal, SHOW_DELAY_MS);
    } else {
        reveal();
    }

    window.clearTimeout(failsafeTimer);
    failsafeTimer = window.setTimeout(endNavigation, FAILSAFE_MS);
}

function endNavigation(): void {
    window.clearTimeout(failsafeTimer);
    window.clearTimeout(showTimer);

    if (activeVisitId === null) {
        return;
    }

    activeVisitId = null;

    if (phase === 'hidden') {
        return;
    }

    conceal();
}

function endNavigationFor(visitId: string | undefined): void {
    if (visitId !== undefined && visitId === activeVisitId) {
        endNavigation();
    }
}

type VisitFlags = {
    id: string;
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
    // Which terminal events fire depends on the path: a cached visit to the current URL
    // fires neither `navigate` (history is replaced) nor `finish` (no request is sent),
    // so the overlay ends on the first event that carries the active visit id.
    router.on('before', (event) => {
        const visit = event.detail.visit as VisitFlags;

        if (!isPageVisit(visit)) {
            return;
        }

        queueMicrotask(() => {
            if (!event.defaultPrevented) {
                startNavigation(visit.id);
            }
        });
    });

    router.on('navigate', (event) => endNavigationFor(event.detail.visitId));
    router.on('success', (event) => endNavigationFor(event.detail.visitId));
    router.on('error', (event) => endNavigationFor(event.detail.visitId));
    router.on('finish', (event) =>
        endNavigationFor((event.detail.visit as VisitFlags).id),
    );
    router.on('httpException', () => endNavigation());
    router.on('networkError', () => endNavigation());
}

function subscribe(listener: () => void): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

function getPhase(): OverlayPhase {
    return phase;
}

const BUILDINGS: Array<[x: number, width: number, height: number]> = [
    [0, 34, 58],
    [38, 24, 88],
    [66, 40, 48],
    [110, 28, 108],
    [142, 46, 68],
    [192, 22, 94],
    [218, 38, 54],
    [260, 30, 118],
    [294, 44, 64],
    [342, 26, 84],
    [372, 28, 50],
];

function buildingWindows(
    x: number,
    width: number,
    height: number,
): Array<[x: number, y: number]> {
    const windows: Array<[x: number, y: number]> = [];

    for (let windowY = 170 - height + 6; windowY < 160; windowY += 11) {
        for (let windowX = x + 5; windowX + 4 <= x + width - 4; windowX += 9) {
            windows.push([windowX, windowY]);
        }
    }

    return windows;
}

const LANE_DASHES = Array.from({ length: 9 }, (_, index) => index * 60 - 20);

const WHEEL_SPOKES = [0, 72, 144, 216, 288];

function Wheel({ cx }: { cx: number }) {
    return (
        <g className="animate-navigation-wheel">
            <circle cx={cx} cy={150} r={20} fill="#18181b" />
            <circle
                cx={cx}
                cy={150}
                r={16}
                fill="none"
                stroke="#3f3f46"
                strokeWidth={2}
            />
            <circle cx={cx} cy={150} r={11} fill="url(#nav-rim)" />
            {WHEEL_SPOKES.map((angle) => (
                <line
                    key={angle}
                    x1={cx}
                    y1={150}
                    x2={cx}
                    y2={140}
                    stroke="#71717a"
                    strokeWidth={2.5}
                    strokeLinecap="round"
                    transform={`rotate(${angle} ${cx} 150)`}
                />
            ))}
            <circle cx={cx} cy={150} r={4} fill="#3f3f46" />
        </g>
    );
}

function DeliveryTruckScene() {
    return (
        <svg viewBox="0 0 400 200" className="h-auto w-full" aria-hidden>
            <defs>
                <linearGradient id="nav-fade" x1="0" x2="1">
                    <stop offset="0" stopColor="#000" />
                    <stop offset="0.14" stopColor="#fff" />
                    <stop offset="0.86" stopColor="#fff" />
                    <stop offset="1" stopColor="#000" />
                </linearGradient>
                <mask id="nav-edges">
                    <rect width="400" height="200" fill="url(#nav-fade)" />
                </mask>
                <linearGradient id="nav-cargo" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stopColor="#ffffff" />
                    <stop offset="1" stopColor="#e4e4e7" />
                </linearGradient>
                <linearGradient id="nav-cab" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" style={{ stopColor: 'var(--primary)' }} />
                    <stop
                        offset="1"
                        style={{
                            stopColor:
                                'color-mix(in oklab, var(--primary) 72%, black)',
                        }}
                    />
                </linearGradient>
                <linearGradient id="nav-glass" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stopColor="#e0f2fe" />
                    <stop offset="1" stopColor="#7dd3fc" />
                </linearGradient>
                <radialGradient id="nav-rim">
                    <stop offset="0" stopColor="#f4f4f5" />
                    <stop offset="1" stopColor="#a1a1aa" />
                </radialGradient>
                <linearGradient id="nav-beam" x1="0" x2="1">
                    <stop offset="0" stopColor="#fde68a" stopOpacity="0.75" />
                    <stop offset="1" stopColor="#fde68a" stopOpacity="0" />
                </linearGradient>
            </defs>

            <g mask="url(#nav-edges)">
                <g className="animate-navigation-skyline" fill="#e4e4e7">
                    {[0, 400].map((offset) => (
                        <g key={offset} transform={`translate(${offset} 0)`}>
                            {BUILDINGS.map(([x, width, height]) => (
                                <g key={x}>
                                    <rect
                                        x={x}
                                        y={170 - height}
                                        width={width}
                                        height={height}
                                        rx={2}
                                    />
                                    {buildingWindows(x, width, height).map(
                                        ([windowX, windowY]) => (
                                            <rect
                                                key={`${windowX}-${windowY}`}
                                                x={windowX}
                                                y={windowY}
                                                width={4}
                                                height={5}
                                                fill="#fafafa"
                                            />
                                        ),
                                    )}
                                </g>
                            ))}
                        </g>
                    ))}
                </g>

                <rect x="0" y="168" width="400" height="3" fill="#a1a1aa" />
                <rect x="0" y="171" width="400" height="29" fill="#3f3f46" />
                <g className="animate-navigation-lane" fill="#fafafa">
                    {LANE_DASHES.map((x) => (
                        <rect
                            key={x}
                            x={x}
                            y="184"
                            width="30"
                            height="3"
                            rx="1.5"
                        />
                    ))}
                </g>

                <g
                    stroke="#a1a1aa"
                    strokeWidth="3"
                    strokeLinecap="round"
                    className="animate-navigation-speed"
                >
                    <line x1="4" y1="62" x2="26" y2="62" />
                    <line x1="0" y1="84" x2="30" y2="84" />
                    <line x1="8" y1="106" x2="26" y2="106" />
                </g>
            </g>

            <ellipse
                cx="180"
                cy="171"
                rx="150"
                ry="4"
                fill="#000"
                opacity="0.18"
            />

            <g fill="#d4d4d8">
                {[0, 0.3, 0.6].map((delay) => (
                    <circle
                        key={delay}
                        cx="30"
                        cy="142"
                        r="5"
                        className="animate-navigation-smoke"
                        style={{ animationDelay: `${delay}s` }}
                    />
                ))}
            </g>

            <g className="animate-navigation-ride">
                <rect
                    x="36"
                    y="40"
                    width="186"
                    height="98"
                    rx="8"
                    fill="url(#nav-cargo)"
                    stroke="#d4d4d8"
                    strokeWidth="1.5"
                />
                <rect
                    x="36"
                    y="120"
                    width="186"
                    height="9"
                    className="fill-primary"
                />
                <path d="M44 46 V132" stroke="#d4d4d8" strokeWidth="1.5" />
                <rect
                    x="31"
                    y="116"
                    width="6"
                    height="14"
                    rx="2"
                    fill="#ef4444"
                />

                <polygon points="56,68 68,58 102,58 90,68" fill="#e8bb82" />
                <polygon points="90,68 102,58 102,86 90,96" fill="#b9844d" />
                <rect x="56" y="68" width="34" height="28" fill="#d6a064" />
                <polygon points="70,68 82,58 88,58 76,68" fill="#f5deb3" />
                <rect x="70" y="68" width="6" height="28" fill="#f5deb3" />
                <rect
                    x="59"
                    y="85"
                    width="9"
                    height="7"
                    rx="1"
                    fill="#ffffff"
                />

                <text
                    x="112"
                    y="80"
                    fontSize="17"
                    fontWeight="800"
                    fontStyle="italic"
                    className="fill-primary"
                >
                    PuffMeMate
                </text>
                <text
                    x="113"
                    y="96"
                    fontSize="8"
                    fontWeight="700"
                    letterSpacing="1.6"
                    fill="#52525b"
                >
                    EXPRESS DELIVERY
                </text>

                <polygon
                    points="322,112 400,94 400,140 322,122"
                    fill="url(#nav-beam)"
                />
                <path
                    d="M226 64 H282 Q292 64 298 72 L318 102 Q324 109 324 118 V140 Q324 146 318 146 H226 Z"
                    fill="url(#nav-cab)"
                />
                <path
                    d="M238 74 H280 Q286 74 290 79 L306 104 H238 Z"
                    fill="url(#nav-glass)"
                />
                <path
                    d="M262 74 L250 104 H258 L270 74 Z"
                    fill="#ffffff"
                    opacity="0.55"
                />
                <path
                    d="M232 74 V140"
                    stroke="#000"
                    strokeOpacity="0.2"
                    strokeWidth="1.5"
                />
                <rect
                    x="262"
                    y="112"
                    width="12"
                    height="3"
                    rx="1.5"
                    fill="#000"
                    opacity="0.35"
                />
                <rect
                    x="296"
                    y="80"
                    width="5"
                    height="16"
                    rx="2"
                    fill="#27272a"
                />
                <rect
                    x="316"
                    y="110"
                    width="8"
                    height="9"
                    rx="2"
                    fill="#fde68a"
                />
                <rect
                    x="298"
                    y="136"
                    width="30"
                    height="10"
                    rx="3"
                    fill="#27272a"
                />
                <rect
                    x="36"
                    y="136"
                    width="264"
                    height="10"
                    rx="2"
                    fill="#27272a"
                />
                <path d="M62 146 A28 28 0 0 1 118 146 Z" fill="#18181b" />
                <path d="M252 146 A28 28 0 0 1 308 146 Z" fill="#18181b" />
            </g>

            <Wheel cx={90} />
            <Wheel cx={280} />
        </svg>
    );
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
            aria-busy={current !== 'leaving'}
            className={cn(
                'fixed inset-0 z-[200] flex items-center justify-center bg-white/72 backdrop-blur-md transition-opacity duration-300 ease-out motion-reduce:transition-none',
                current === 'visible' ? 'opacity-100' : 'opacity-0',
                current === 'leaving' ? 'pointer-events-none' : 'pointer-events-auto',
            )}
            onTransitionEnd={(event) => {
                if (event.target === event.currentTarget) {
                    completeFade(event.propertyName);
                }
            }}
        >
            <span className="sr-only">Delivering the next page</span>
            <div className="w-[26rem] max-w-[90vw]">
                <DeliveryTruckScene />
                <p className="text-muted-foreground mt-2 text-center text-xs font-semibold tracking-[0.3em] uppercase">
                    On the way
                </p>
            </div>
        </div>
    );
}
