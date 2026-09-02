import type { FallbackProps } from 'react-error-boundary';
import { ErrorDevFallback } from '@/components/error-boundary/error-dev-fallback';
import { ErrorProductionFallback } from '@/components/error-boundary/error-production-fallback';
import { useDevErrorFallback } from '@/hooks/useDevErrorFallback';

/**
 * Root FallbackComponent passed to <ErrorBoundary>.
 *
 * - DEV  → full overlay with stack trace, tabs, error history, floating badge
 * - PROD → clean user-facing card with reload / go-back / home actions
 */
export function ErrorBoundaryFallback(props: FallbackProps) {
    if (useDevErrorFallback()) {
        return <ErrorDevFallback {...props} />;
    }

    return <ErrorProductionFallback />;
}
