import * as SheetPrimitive from '@radix-ui/react-dialog';
import { XIcon } from 'lucide-react';
import {
    useEffect,
    useRef,
    useState,
    type ComponentProps,
} from 'react';
import { cn } from '@/lib/utils';

type StorefrontSheetSide = 'left' | 'right';

/**
 * Floating storefront panel. The default sheet stays full-bleed and square;
 * this one sits inset with the same corner radius as the rest of the shop.
 */
export function StorefrontSheetContent({
    className,
    children,
    side = 'right',
    ...props
}: ComponentProps<typeof SheetPrimitive.Content> & {
    side?: StorefrontSheetSide;
}) {
    return (
        <SheetPrimitive.Portal>
            <SheetPrimitive.Overlay className="data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed inset-0 z-50 bg-scrim backdrop-blur-[2px]" />
            <SheetPrimitive.Content
                data-slot="storefront-sheet-content"
                className={cn(
                    'bg-background data-[state=open]:animate-in data-[state=closed]:animate-out fixed z-50 flex flex-col gap-4 overflow-hidden rounded-3xl border shadow-xl transition ease-in-out data-[state=closed]:duration-300 data-[state=open]:duration-500',
                    side === 'right' &&
                        'data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right top-3 right-3 bottom-3 w-[min(calc(100vw-1.5rem),20rem)]',
                    side === 'left' &&
                        'data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left top-3 bottom-3 left-3 w-[min(calc(100vw-1.5rem),20rem)]',
                    className,
                )}
                {...props}
            >
                {children}
                <SheetPrimitive.Close className="ring-offset-background focus:ring-ring data-[state=open]:bg-secondary absolute top-4 right-4 flex size-8 items-center justify-center rounded-full opacity-70 transition-[opacity,background-color] hover:bg-accent hover:opacity-100 focus:ring-2 focus:ring-offset-2 focus:outline-hidden disabled:pointer-events-none">
                    <XIcon className="size-4" />
                    <span className="sr-only">Close</span>
                </SheetPrimitive.Close>
            </SheetPrimitive.Content>
        </SheetPrimitive.Portal>
    );
}

/**
 * Scrolls sheet content without a track along the rounded edge.
 * A short fade shows when more content sits below the fold.
 */
export function StorefrontSheetBody({
    className,
    children,
    ...props
}: ComponentProps<'div'>) {
    const scrollerRef = useRef<HTMLDivElement>(null);
    const [moreBelow, setMoreBelow] = useState(false);

    useEffect(() => {
        const scroller = scrollerRef.current;

        if (!scroller) {
            return;
        }

        const update = (): void => {
            const remaining =
                scroller.scrollHeight -
                scroller.scrollTop -
                scroller.clientHeight;

            setMoreBelow(remaining > 8);
        };

        update();
        scroller.addEventListener('scroll', update, { passive: true });
        const observer = new ResizeObserver(update);
        observer.observe(scroller);

        return () => {
            scroller.removeEventListener('scroll', update);
            observer.disconnect();
        };
    }, []);

    return (
        <div className="relative min-h-0 flex-1">
            <div
                ref={scrollerRef}
                className={cn(
                    'scrollbar-none h-full overflow-y-auto overscroll-contain',
                    className,
                )}
                {...props}
            >
                {children}
            </div>
            {moreBelow ? (
                <div
                    aria-hidden
                    className="from-background pointer-events-none absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t to-transparent"
                />
            ) : null}
        </div>
    );
}
