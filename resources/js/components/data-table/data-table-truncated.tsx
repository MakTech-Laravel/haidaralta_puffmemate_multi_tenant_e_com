import {
    useCallback,
    useLayoutEffect,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

export function DataTableTruncated({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const [truncated, setTruncated] = useState(false);
    const [label, setLabel] = useState('');

    const update = useCallback(() => {
        const node = ref.current;

        if (!node) {
            return;
        }

        const isTruncated = node.scrollWidth > node.clientWidth;
        setTruncated(isTruncated);
        setLabel(isTruncated ? node.innerText.trim() : '');
    }, []);

    useLayoutEffect(() => {
        update();

        const node = ref.current;

        if (!node || typeof ResizeObserver === 'undefined') {
            return;
        }

        const observer = new ResizeObserver(() => update());
        observer.observe(node);

        return () => observer.disconnect();
    }, [children, update]);

    const showTooltip = truncated && label !== '';

    return (
        <Tooltip open={showTooltip ? undefined : false}>
            <TooltipTrigger asChild>
                <div
                    ref={ref}
                    className={cn('max-w-full min-w-0 truncate', className)}
                >
                    {children}
                </div>
            </TooltipTrigger>
            <TooltipContent className="max-w-sm wrap-break-word whitespace-normal">
                {label}
            </TooltipContent>
        </Tooltip>
    );
}
