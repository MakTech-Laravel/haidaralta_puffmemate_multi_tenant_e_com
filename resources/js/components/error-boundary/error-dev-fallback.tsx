import { useState } from 'react';
import type { FallbackProps } from 'react-error-boundary';
import { ErrorBadge } from '@/components/error-boundary/error-badge';
import { ErrorOverlay } from '@/components/error-boundary/error-overlay';

/**
 * Dev-mode fallback.
 *
 * Flow:
 *   1. Error thrown → full-screen overlay opens.
 *   2. User clicks "Try again" → overlay closes, floating badge appears.
 *   3. User clicks badge → overlay re-opens at the selected error index.
 */
export function ErrorDevFallback(props: FallbackProps) {
    const [overlayOpen, setOverlayOpen] = useState(true);
    const [openAtIdx, setOpenAtIdx] = useState(0);

    return (
        <>
            {overlayOpen && (
                <ErrorOverlay
                    {...props}
                    resetErrorBoundary={() => {
                        setOverlayOpen(false);
                        props.resetErrorBoundary();
                    }}
                />
            )}

            {!overlayOpen && (
                <ErrorBadge
                    onOpen={(idx) => {
                        setOpenAtIdx(idx);
                        setOverlayOpen(true);
                    }}
                />
            )}

            {/* Keep openAtIdx in scope (used when re-opening overlay) */}
            {openAtIdx < 0 && null}
        </>
    );
}
