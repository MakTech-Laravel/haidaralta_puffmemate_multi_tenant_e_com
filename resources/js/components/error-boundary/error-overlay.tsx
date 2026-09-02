import {
    AlertTriangle,
    ArrowLeft,
    ChevronLeft,
    ChevronRight,
    Clipboard,
    ClipboardCheck,
    Home,
    RotateCcw,
    Trash2,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { FallbackProps } from 'react-error-boundary';
import {
    clearErrors,
    getStoredErrors,
    removeError,
} from '@/components/error-boundary/error-store';
import type { StoredError } from '@/components/error-boundary/error-store';
import { cn } from '@/lib/utils';

// ─── Types ──────────────────────────────────────────────────────────────────

type Tab = 'overview' | 'trace' | 'component';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function formatTime(ts: number) {
    return new Intl.DateTimeFormat('en', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date(ts));
}

function abbreviateUrl(url: string) {
    try {
        const u = new URL(url);

        return u.pathname + u.search;
    } catch {
        return url;
    }
}

// ─── Copy button ─────────────────────────────────────────────────────────────

function CopyButton({ text, className }: { text: string; className?: string }) {
    const [copied, setCopied] = useState(false);

    const copy = useCallback(async () => {
        await navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }, [text]);

    return (
        <button
            type="button"
            onClick={copy}
            className={cn(
                'flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition-all duration-150',
                copied
                    ? 'bg-emerald-500/20 text-emerald-400'
                    : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-zinc-200',
                className,
            )}
            aria-label="Copy to clipboard"
        >
            {copied ? (
                <>
                    <ClipboardCheck className="size-3.5" />
                    Copied!
                </>
            ) : (
                <>
                    <Clipboard className="size-3.5" />
                    Copy
                </>
            )}
        </button>
    );
}

// ─── Stack frame parser ───────────────────────────────────────────────────────

interface StackFrame {
    fn: string;
    loc: string;
    isInternal: boolean;
}

function parseStack(stack: string): StackFrame[] {
    return stack
        .split('\n')
        .filter((l) => l.trim().startsWith('at '))
        .map((line) => {
            const trimmed = line.trim().slice(3); // remove "at "
            const parenIdx = trimmed.indexOf('(');
            const fn =
                parenIdx > -1 ? trimmed.slice(0, parenIdx).trim() : 'anonymous';
            const loc =
                parenIdx > -1 ? trimmed.slice(parenIdx + 1, -1) : trimmed;

            const isInternal =
                loc.includes('node_modules') ||
                loc.includes('@inertiajs') ||
                loc.includes('react.development') ||
                loc.includes('react-dom');

            return { fn, loc, isInternal };
        });
}

// ─── Tabs ────────────────────────────────────────────────────────────────────

const TABS: { id: Tab; label: string }[] = [
    { id: 'overview', label: 'Overview' },
    { id: 'trace', label: 'Stack Trace' },
    { id: 'component', label: 'Component Stack' },
];

// ─── Main component ───────────────────────────────────────────────────────────

export interface ErrorOverlayProps extends FallbackProps {
    /** Called after errors are cleared */
    onClearAll?: () => void;
}

export function ErrorOverlay({ error, resetErrorBoundary }: ErrorOverlayProps) {
    const [errors, setErrors] = useState<StoredError[]>(() =>
        getStoredErrors(),
    );
    const [activeIdx, setActiveIdx] = useState(0);
    const [tab, setTab] = useState<Tab>('overview');
    const [showAll, setShowAll] = useState(false);
    const scrollRef = useRef<HTMLPreElement>(null);

    // Refresh list whenever we navigate
    const refresh = useCallback(() => setErrors(getStoredErrors()), []);

    useEffect(() => {
        // Re-sync from the sessionStorage error store on navigation; this reads
        // an external store rather than deriving state, so the setState is intentional.
        // eslint-disable-next-line react-hooks/set-state-in-effect
        refresh();
    }, [error, refresh]);

    const active = errors[activeIdx] ?? errors[0];

    const frames = useMemo(
        () => (active ? parseStack(active.stack) : []),
        [active],
    );

    const visibleFrames = showAll
        ? frames
        : frames.filter((f) => !f.isInternal);

    const handleClearAll = useCallback(() => {
        clearErrors();
        refresh();
        resetErrorBoundary();
    }, [refresh, resetErrorBoundary]);

    const handleRemoveCurrent = useCallback(() => {
        if (!active) {
            return;
        }

        removeError(active.id);
        const next = getStoredErrors();
        setErrors(next);
        setActiveIdx((i) => Math.max(0, Math.min(i, next.length - 1)));

        if (next.length === 0) {
            resetErrorBoundary();
        }
    }, [active, resetErrorBoundary]);

    const goBack = useCallback(() => {
        window.history.back();
    }, []);

    const goHome = useCallback(() => {
        window.location.href = '/';
    }, []);

    if (!active) {
        return null;
    }

    const total = errors.length;
    const canPrev = activeIdx > 0;
    const canNext = activeIdx < total - 1;

    return (
        <div
            className="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-6"
            style={{
                background: 'rgba(0,0,0,0.72)',
                backdropFilter: 'blur(6px)',
            }}
            role="alertdialog"
            aria-modal="true"
            aria-label="Error boundary"
        >
            <div
                className={cn(
                    'flex w-full flex-col overflow-hidden',
                    'max-h-[min(92vh,780px)] max-w-[780px]',
                    'rounded-2xl shadow-[0_32px_80px_rgba(0,0,0,0.8)]',
                )}
                style={{
                    background:
                        'linear-gradient(160deg,#18181b 0%,#111113 100%)',
                    border: '1px solid rgba(255,255,255,0.08)',
                }}
            >
                {/* ── Header ── */}
                <header className="flex shrink-0 items-center justify-between border-b border-white/[0.06] px-5 py-3.5">
                    <div className="flex items-center gap-3">
                        {/* Error badge */}
                        <div
                            className="flex size-8 items-center justify-center rounded-lg"
                            style={{ background: 'rgba(239,68,68,0.15)' }}
                        >
                            <AlertTriangle
                                className="size-4 text-red-400"
                                aria-hidden
                            />
                        </div>

                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-sm font-semibold tracking-tight text-zinc-100">
                                    Unhandled Error
                                </span>
                                {active.count > 1 && (
                                    <span className="rounded-full bg-red-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-red-400">
                                        ×{active.count}
                                    </span>
                                )}
                            </div>
                            <p className="text-[11px] text-zinc-500">
                                Development overlay ·{' '}
                                {formatTime(active.timestamp)}
                                {active.url && (
                                    <>
                                        {' '}
                                        ·{' '}
                                        <span className="text-zinc-400">
                                            {abbreviateUrl(active.url)}
                                        </span>
                                    </>
                                )}
                            </p>
                        </div>
                    </div>

                    {/* Error navigation */}
                    {total > 1 && (
                        <div className="flex items-center gap-1">
                            <button
                                type="button"
                                onClick={() => setActiveIdx((i) => i - 1)}
                                disabled={!canPrev}
                                className="flex size-7 items-center justify-center rounded-md text-zinc-400 transition hover:bg-zinc-800 hover:text-zinc-200 disabled:pointer-events-none disabled:opacity-30"
                                aria-label="Previous error"
                            >
                                <ChevronLeft className="size-4" />
                            </button>
                            <span className="min-w-[3rem] text-center text-xs text-zinc-500">
                                {activeIdx + 1} / {total}
                            </span>
                            <button
                                type="button"
                                onClick={() => setActiveIdx((i) => i + 1)}
                                disabled={!canNext}
                                className="flex size-7 items-center justify-center rounded-md text-zinc-400 transition hover:bg-zinc-800 hover:text-zinc-200 disabled:pointer-events-none disabled:opacity-30"
                                aria-label="Next error"
                            >
                                <ChevronRight className="size-4" />
                            </button>
                        </div>
                    )}

                    <button
                        type="button"
                        onClick={handleRemoveCurrent}
                        className="flex size-7 items-center justify-center rounded-md text-zinc-500 transition hover:bg-zinc-800 hover:text-zinc-300"
                        aria-label="Dismiss this error"
                    >
                        <X className="size-4" />
                    </button>
                </header>

                {/* ── Error message banner ── */}
                <div
                    className="shrink-0 px-5 py-3"
                    style={{
                        background: 'rgba(239,68,68,0.06)',
                        borderBottom: '1px solid rgba(239,68,68,0.12)',
                    }}
                >
                    <p className="font-mono text-sm leading-snug font-medium text-red-300">
                        {active.message}
                    </p>
                </div>

                {/* ── Tabs ── */}
                <div className="flex shrink-0 gap-1 border-b border-white/[0.06] px-5 pt-3">
                    {TABS.map((t) => {
                        const isDisabled =
                            t.id === 'component' && !active.componentStack;

                        return (
                            <button
                                key={t.id}
                                type="button"
                                disabled={isDisabled}
                                onClick={() => setTab(t.id)}
                                className={cn(
                                    'relative mb-[-1px] rounded-t-md px-3.5 pt-1.5 pb-2.5 text-xs font-medium transition-colors duration-150',
                                    tab === t.id
                                        ? 'text-zinc-100'
                                        : 'text-zinc-500 hover:text-zinc-300 disabled:pointer-events-none disabled:opacity-30',
                                )}
                            >
                                {t.label}
                                {tab === t.id && (
                                    <span className="absolute inset-x-2 bottom-0 h-[2px] rounded-full bg-red-400" />
                                )}
                            </button>
                        );
                    })}
                </div>

                {/* ── Tab content ── */}
                <div className="min-h-0 flex-1 overflow-auto">
                    {/* Overview */}
                    {tab === 'overview' && (
                        <div className="space-y-4 p-5">
                            {/* All stored errors list */}
                            {errors.length > 1 && (
                                <div>
                                    <div className="mb-2 flex items-center justify-between">
                                        <span className="text-[11px] font-semibold tracking-wider text-zinc-500 uppercase">
                                            Error History ({total})
                                        </span>
                                        <button
                                            type="button"
                                            onClick={handleClearAll}
                                            className="flex items-center gap-1 text-[11px] text-zinc-500 transition hover:text-red-400"
                                        >
                                            <Trash2 className="size-3" />
                                            Clear all
                                        </button>
                                    </div>
                                    <div className="space-y-1.5 rounded-xl border border-white/[0.06] bg-zinc-900/60 p-2">
                                        {errors.map((e, i) => (
                                            <button
                                                key={e.id}
                                                type="button"
                                                onClick={() => {
                                                    setActiveIdx(i);
                                                    setTab('overview');
                                                }}
                                                className={cn(
                                                    'flex w-full items-start gap-2.5 rounded-lg px-3 py-2 text-left transition',
                                                    i === activeIdx
                                                        ? 'bg-red-500/10 ring-1 ring-red-500/20'
                                                        : 'hover:bg-zinc-800/60',
                                                )}
                                            >
                                                <span
                                                    className={cn(
                                                        'mt-0.5 size-1.5 shrink-0 rounded-full',
                                                        i === activeIdx
                                                            ? 'bg-red-400'
                                                            : 'bg-zinc-600',
                                                    )}
                                                />
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-xs font-medium text-zinc-300">
                                                        {e.message}
                                                    </p>
                                                    <p className="mt-0.5 text-[11px] text-zinc-600">
                                                        {formatTime(
                                                            e.timestamp,
                                                        )}{' '}
                                                        · {abbreviateUrl(e.url)}
                                                        {e.count > 1 && (
                                                            <span className="ml-1.5 rounded-full bg-red-500/20 px-1 py-px text-[10px] text-red-400">
                                                                ×{e.count}
                                                            </span>
                                                        )}
                                                    </p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={(ev) => {
                                                        ev.stopPropagation();
                                                        removeError(e.id);
                                                        const next =
                                                            getStoredErrors();
                                                        setErrors(next);

                                                        if (next.length === 0) {
                                                            resetErrorBoundary();
                                                        } else if (
                                                            activeIdx >=
                                                            next.length
                                                        ) {
                                                            setActiveIdx(
                                                                next.length - 1,
                                                            );
                                                        }
                                                    }}
                                                    className="shrink-0 rounded p-0.5 text-zinc-600 hover:text-zinc-400"
                                                    aria-label="Remove error"
                                                >
                                                    <X className="size-3" />
                                                </button>
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Quick info */}
                            <div className="grid grid-cols-2 gap-2">
                                {[
                                    {
                                        label: 'Error Type',
                                        value:
                                            error instanceof Error
                                                ? error.constructor.name
                                                : 'Unknown',
                                    },
                                    {
                                        label: 'Occurred',
                                        value: formatTime(active.timestamp),
                                    },
                                    {
                                        label: 'Page',
                                        value: abbreviateUrl(active.url),
                                    },
                                    {
                                        label: 'Total Occurrences',
                                        value: String(active.count),
                                    },
                                ].map(({ label, value }) => (
                                    <div
                                        key={label}
                                        className="rounded-xl border border-white/[0.06] bg-zinc-900/60 px-4 py-3"
                                    >
                                        <p className="mb-1 text-[11px] font-medium tracking-wider text-zinc-600 uppercase">
                                            {label}
                                        </p>
                                        <p className="truncate font-mono text-xs text-zinc-300">
                                            {value}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Stack Trace */}
                    {tab === 'trace' && (
                        <div className="p-5">
                            <div className="mb-3 flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <span className="text-[11px] font-semibold tracking-wider text-zinc-500 uppercase">
                                        {visibleFrames.length} frames
                                        {!showAll &&
                                            frames.length >
                                                visibleFrames.length && (
                                                <span className="ml-1 text-zinc-600">
                                                    (
                                                    {frames.length -
                                                        visibleFrames.length}{' '}
                                                    internal hidden)
                                                </span>
                                            )}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => setShowAll((v) => !v)}
                                        className="text-[11px] text-zinc-500 underline decoration-dotted underline-offset-2 hover:text-zinc-300"
                                    >
                                        {showAll ? 'Hide internal' : 'Show all'}
                                    </button>
                                </div>
                                <CopyButton text={active.stack} />
                            </div>

                            <div className="space-y-1">
                                {visibleFrames.map((frame, i) => (
                                    <div
                                        key={i}
                                        className={cn(
                                            'rounded-lg px-3 py-2.5 transition',
                                            frame.isInternal
                                                ? 'border border-transparent bg-zinc-900/40'
                                                : 'border border-white/[0.06] bg-zinc-900/80',
                                        )}
                                    >
                                        <p
                                            className={cn(
                                                'font-mono text-xs font-medium',
                                                frame.isInternal
                                                    ? 'text-zinc-600'
                                                    : 'text-zinc-200',
                                            )}
                                        >
                                            {frame.fn}
                                        </p>
                                        <p
                                            className={cn(
                                                'mt-0.5 font-mono text-[11px] break-all',
                                                frame.isInternal
                                                    ? 'text-zinc-700'
                                                    : 'text-zinc-500',
                                            )}
                                        >
                                            {frame.loc}
                                        </p>
                                    </div>
                                ))}
                            </div>

                            {/* Raw toggle */}
                            <details className="mt-4">
                                <summary className="cursor-pointer text-[11px] text-zinc-500 hover:text-zinc-300">
                                    Raw stack trace
                                </summary>
                                <pre
                                    ref={scrollRef}
                                    className="mt-2 overflow-auto rounded-xl border border-white/[0.06] bg-zinc-900/80 p-4 font-mono text-[11px] leading-relaxed text-zinc-400"
                                >
                                    {active.stack}
                                </pre>
                            </details>
                        </div>
                    )}

                    {/* Component Stack */}
                    {tab === 'component' && active.componentStack && (
                        <div className="p-5">
                            <div className="mb-3 flex items-center justify-between">
                                <span className="text-[11px] font-semibold tracking-wider text-zinc-500 uppercase">
                                    Component Tree
                                </span>
                                <CopyButton text={active.componentStack} />
                            </div>
                            <pre className="overflow-auto rounded-xl border border-white/[0.06] bg-zinc-900/80 p-4 font-mono text-[11px] leading-relaxed text-zinc-400">
                                {active.componentStack}
                            </pre>
                        </div>
                    )}
                </div>

                {/* ── Footer actions ── */}
                <footer className="flex shrink-0 flex-wrap items-center justify-between gap-2 border-t border-white/[0.06] px-5 py-3.5">
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={goBack}
                            className="flex items-center gap-1.5 rounded-lg bg-zinc-800 px-3 py-1.5 text-xs font-medium text-zinc-300 transition hover:bg-zinc-700 hover:text-zinc-100"
                        >
                            <ArrowLeft className="size-3.5" />
                            Go back
                        </button>
                        <button
                            type="button"
                            onClick={goHome}
                            className="flex items-center gap-1.5 rounded-lg bg-zinc-800 px-3 py-1.5 text-xs font-medium text-zinc-300 transition hover:bg-zinc-700 hover:text-zinc-100"
                        >
                            <Home className="size-3.5" />
                            Home
                        </button>
                    </div>

                    <div className="flex items-center gap-2">
                        {total > 1 && (
                            <button
                                type="button"
                                onClick={handleClearAll}
                                className="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-red-400 transition hover:bg-red-500/10"
                            >
                                <Trash2 className="size-3.5" />
                                Clear all ({total})
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={resetErrorBoundary}
                            className="flex items-center gap-1.5 rounded-lg bg-red-500 px-4 py-1.5 text-xs font-semibold text-white shadow-lg shadow-red-500/25 transition hover:bg-red-400 active:scale-[0.98]"
                        >
                            <RotateCcw className="size-3.5" />
                            Try again
                        </button>
                    </div>
                </footer>
            </div>
        </div>
    );
}
