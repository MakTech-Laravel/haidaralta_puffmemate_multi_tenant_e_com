import { AlertTriangle, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import {
    clearErrors,
    getStoredErrors,
} from '@/components/error-boundary/error-store';
import type { StoredError } from '@/components/error-boundary/error-store';
import { cn } from '@/lib/utils';

interface ErrorBadgeProps {
    onOpen: (idx: number) => void;
}

/**
 * Floating bottom-left badge that mimics the Next.js dev toolbar.
 * Shows when there are errors stored but the overlay is closed.
 */
export function ErrorBadge({ onOpen }: ErrorBadgeProps) {
    const [errors, setErrors] = useState<StoredError[]>(() =>
        getStoredErrors(),
    );
    const [idx, setIdx] = useState(0);
    const [dismissAll, setDismissAll] = useState(false);
    const [visible, setVisible] = useState(false);

    // Animate in
    useEffect(() => {
        const id = setTimeout(() => setVisible(true), 50);

        return () => clearTimeout(id);
    }, []);

    // Poll for new errors pushed from other tabs / windows (cross-tab via storage event)
    useEffect(() => {
        const onStorage = (e: StorageEvent) => {
            if (e.key?.includes('dev_error_boundary_history')) {
                setErrors(getStoredErrors());
                setDismissAll(false);
            }
        };
        window.addEventListener('storage', onStorage);

        return () => window.removeEventListener('storage', onStorage);
    }, []);

    const total = errors.length;
    const current = errors[idx];

    const handleClear = useCallback(() => {
        clearErrors();
        setDismissAll(true);
    }, []);

    if (!total || dismissAll || !current) {
        return null;
    }

    return (
        <div
            aria-label="Error history"
            className={cn(
                'fixed bottom-4 left-4 z-[9998] flex items-center gap-0 overflow-hidden',
                'rounded-full shadow-[0_8px_32px_rgba(0,0,0,0.6)]',
                'transition-all duration-300 ease-out',
                visible
                    ? 'translate-y-0 opacity-100'
                    : 'translate-y-4 opacity-0',
            )}
            style={{ border: '1px solid rgba(239,68,68,0.3)' }}
        >
            {/* Open button */}
            <button
                type="button"
                onClick={() => onOpen(idx)}
                className="flex items-center gap-2 bg-zinc-900 px-3 py-2 transition hover:bg-zinc-800"
            >
                <span
                    className="flex size-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white"
                    aria-hidden
                >
                    {total > 9 ? '9+' : total}
                </span>
                <AlertTriangle className="size-3.5 text-red-400" aria-hidden />
                <span className="max-w-[180px] truncate font-mono text-[11px] text-zinc-300">
                    {current.message}
                </span>
            </button>

            {/* Navigation (only if >1 error) */}
            {total > 1 && (
                <div className="flex items-center border-l border-zinc-800 bg-zinc-900">
                    <button
                        type="button"
                        disabled={idx === 0}
                        onClick={() => setIdx((i) => i - 1)}
                        className="flex size-8 items-center justify-center text-zinc-500 transition hover:bg-zinc-800 hover:text-zinc-200 disabled:pointer-events-none disabled:opacity-30"
                        aria-label="Previous error"
                    >
                        <ChevronLeft className="size-3.5" />
                    </button>
                    <span className="px-0.5 text-[11px] text-zinc-500">
                        {idx + 1}/{total}
                    </span>
                    <button
                        type="button"
                        disabled={idx === total - 1}
                        onClick={() => setIdx((i) => i + 1)}
                        className="flex size-8 items-center justify-center text-zinc-500 transition hover:bg-zinc-800 hover:text-zinc-200 disabled:pointer-events-none disabled:opacity-30"
                        aria-label="Next error"
                    >
                        <ChevronRight className="size-3.5" />
                    </button>
                </div>
            )}

            {/* Dismiss all */}
            <button
                type="button"
                onClick={handleClear}
                className="flex size-8 items-center justify-center border-l border-zinc-800 bg-zinc-900 text-zinc-500 transition hover:bg-zinc-800 hover:text-zinc-200"
                aria-label="Clear all errors"
            >
                <X className="size-3.5" />
            </button>
        </div>
    );
}
