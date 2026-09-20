import {
    useCallback,
    useEffect,
    useLayoutEffect,
    useRef,
    useState,
    type RefObject,
} from 'react';

/** How far from the bottom still counts as "watching the live edge". */
const LIVE_EDGE_PX = 120;

type Options = {
    /** Newest message id; a change means something arrived or was sent. */
    newestId: number;
    /** Oldest message id; a change means a history page was prepended. */
    oldestId: number;
    /** Whether another page of history exists above. */
    hasMore: boolean;
    onLoadOlder: () => void;
};

type Result = {
    viewportRef: RefObject<HTMLDivElement | null>;
    topSentinelRef: RefObject<HTMLDivElement | null>;
    /** True when the reader has scrolled away from the newest message. */
    showJumpToLatest: boolean;
    scrollToBottom: (behavior?: ScrollBehavior) => void;
};

/**
 * WhatsApp transcript mechanics.
 *
 * Three behaviours the registry scroller got wrong for us:
 *
 *  1. The newest message is pinned to the bottom, and a short thread rests on
 *     the composer instead of leaving a gap beneath it (that part is CSS:
 *     `justify-end` inside a `min-h-full` track).
 *  2. Arrivals only auto-scroll when the reader is already at the live edge.
 *     Reading history is never interrupted.
 *  3. Prepending older messages restores the scroll offset by the height the
 *     page added, so the reader does not get thrown to the top.
 */
export function useChatScroll({
    newestId,
    oldestId,
    hasMore,
    onLoadOlder,
}: Options): Result {
    const viewportRef = useRef<HTMLDivElement>(null);
    const topSentinelRef = useRef<HTMLDivElement>(null);
    const [showJumpToLatest, setShowJumpToLatest] = useState(false);

    const atLiveEdge = useRef(true);
    const pendingPrepend = useRef<number | null>(null);
    const lastOldestId = useRef(oldestId);
    const hasMounted = useRef(false);

    const scrollToBottom = useCallback((behavior: ScrollBehavior = 'auto') => {
        const viewport = viewportRef.current;

        if (!viewport) {
            return;
        }

        viewport.scrollTo({ top: viewport.scrollHeight, behavior });
        atLiveEdge.current = true;
        setShowJumpToLatest(false);
    }, []);

    useEffect(() => {
        const viewport = viewportRef.current;

        if (!viewport) {
            return;
        }

        const onScroll = () => {
            const distance =
                viewport.scrollHeight -
                viewport.scrollTop -
                viewport.clientHeight;

            atLiveEdge.current = distance <= LIVE_EDGE_PX;
            setShowJumpToLatest(!atLiveEdge.current);
        };

        viewport.addEventListener('scroll', onScroll, { passive: true });

        return () => viewport.removeEventListener('scroll', onScroll);
    }, []);

    // Record the pre-prepend height so the offset can be restored below.
    if (oldestId !== lastOldestId.current && oldestId < lastOldestId.current) {
        pendingPrepend.current = viewportRef.current?.scrollHeight ?? null;
        lastOldestId.current = oldestId;
    } else if (oldestId !== lastOldestId.current) {
        lastOldestId.current = oldestId;
    }

    // Layout effect so the correction lands before the browser paints.
    useLayoutEffect(() => {
        const viewport = viewportRef.current;

        if (!viewport) {
            return;
        }

        if (!hasMounted.current) {
            hasMounted.current = true;
            viewport.scrollTop = viewport.scrollHeight;

            return;
        }

        const before = pendingPrepend.current;

        if (before !== null) {
            viewport.scrollTop += viewport.scrollHeight - before;
            pendingPrepend.current = null;
        }
    }, [oldestId]);

    useLayoutEffect(() => {
        if (newestId === 0 || !atLiveEdge.current) {
            return;
        }

        const viewport = viewportRef.current;

        if (viewport) {
            viewport.scrollTop = viewport.scrollHeight;
        }
    }, [newestId]);

    useEffect(() => {
        const sentinel = topSentinelRef.current;

        if (!sentinel || !hasMore) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0]?.isIntersecting) {
                    onLoadOlder();
                }
            },
            { root: viewportRef.current, rootMargin: '150px' },
        );

        observer.observe(sentinel);

        return () => observer.disconnect();
    }, [hasMore, onLoadOlder]);

    return { viewportRef, topSentinelRef, showJumpToLatest, scrollToBottom };
}
