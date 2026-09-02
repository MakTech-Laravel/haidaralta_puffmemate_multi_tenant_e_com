import { ChevronLeft, ChevronRight } from 'lucide-react';
import { AnimatePresence, motion } from 'motion/react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type IconPickerCategoryRailProps = {
    categories: readonly string[];
    activeCategory: string | null;
    onCategoryChange?: (category: string | null) => void;
    disabled?: boolean;
    className?: string;
    activeClassName?: string;
};

export function IconPickerCategoryRail({
    categories,
    activeCategory,
    onCategoryChange,
    disabled = false,
    className,
    activeClassName,
}: IconPickerCategoryRailProps) {
    const scrollerRef = useRef<HTMLDivElement>(null);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    const updateScrollState = useCallback(() => {
        const el = scrollerRef.current;

        if (!el) {
            return;
        }

        const maxScroll = el.scrollWidth - el.clientWidth;

        setCanScrollLeft(el.scrollLeft > 2);
        setCanScrollRight(maxScroll > 2 && el.scrollLeft < maxScroll - 2);
    }, []);

    useEffect(() => {
        const el = scrollerRef.current;

        if (!el) {
            return;
        }

        updateScrollState();

        const onScroll = () => updateScrollState();
        el.addEventListener('scroll', onScroll, { passive: true });

        const observer = new ResizeObserver(() => updateScrollState());
        observer.observe(el);

        const onWheel = (event: WheelEvent) => {
            if (el.scrollWidth <= el.clientWidth) {
                return;
            }

            const mostlyVertical =
                Math.abs(event.deltaY) >= Math.abs(event.deltaX);

            if (!mostlyVertical || event.deltaY === 0) {
                return;
            }

            event.preventDefault();
            el.scrollLeft += event.deltaY;
        };

        el.addEventListener('wheel', onWheel, { passive: false });

        return () => {
            el.removeEventListener('scroll', onScroll);
            el.removeEventListener('wheel', onWheel);
            observer.disconnect();
        };
    }, [categories, updateScrollState]);

    const scrollByAmount = (amount: number) => {
        scrollerRef.current?.scrollBy({ left: amount, behavior: 'smooth' });
    };

    const chipClass = (active: boolean) =>
        cn(
            'shrink-0 rounded-full border px-2.5 py-1 text-xs font-medium capitalize transition-colors',
            active
                ? cn(
                      'border-foreground/20 bg-foreground text-background',
                      activeClassName,
                  )
                : 'border-border/70 bg-muted text-muted-foreground hover:bg-muted/80',
        );

    return (
        <div className={cn('relative', className)}>
            <AnimatePresence>
                {canScrollLeft ? (
                    <motion.div
                        key="left-fade"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="pointer-events-none absolute inset-y-0 left-0 z-10 w-10 bg-gradient-to-r from-background to-transparent"
                    />
                ) : null}
                {canScrollRight ? (
                    <motion.div
                        key="right-fade"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="pointer-events-none absolute inset-y-0 right-0 z-10 w-10 bg-gradient-to-l from-background to-transparent"
                    />
                ) : null}
            </AnimatePresence>

            <AnimatePresence>
                {canScrollLeft ? (
                    <motion.div
                        key="left-btn"
                        initial={{ opacity: 0, x: -4 }}
                        animate={{ opacity: 1, x: 0 }}
                        exit={{ opacity: 0, x: -4 }}
                        className="absolute top-1/2 left-0 z-20 -translate-y-1/2"
                    >
                        <Button
                            type="button"
                            size="icon"
                            variant="outline"
                            disabled={disabled}
                            aria-label="Scroll categories left"
                            className="size-7 rounded-full bg-background/95 shadow-sm backdrop-blur-sm"
                            onClick={() => scrollByAmount(-180)}
                        >
                            <ChevronLeft className="size-3.5" />
                        </Button>
                    </motion.div>
                ) : null}
                {canScrollRight ? (
                    <motion.div
                        key="right-btn"
                        initial={{ opacity: 0, x: 4 }}
                        animate={{ opacity: 1, x: 0 }}
                        exit={{ opacity: 0, x: 4 }}
                        className="absolute top-1/2 right-0 z-20 -translate-y-1/2"
                    >
                        <Button
                            type="button"
                            size="icon"
                            variant="outline"
                            disabled={disabled}
                            aria-label="Scroll categories right"
                            className="size-7 rounded-full bg-background/95 shadow-sm backdrop-blur-sm"
                            onClick={() => scrollByAmount(180)}
                        >
                            <ChevronRight className="size-3.5" />
                        </Button>
                    </motion.div>
                ) : null}
            </AnimatePresence>

            <div
                ref={scrollerRef}
                className={cn(
                    'flex scrollbar-thin gap-1.5 overflow-x-auto overscroll-x-contain scroll-smooth py-0.5 transition-[padding]',
                    canScrollLeft ? 'pl-9' : 'pl-0.5',
                    canScrollRight ? 'pr-9' : 'pr-0.5',
                )}
                role="tablist"
                aria-label="Icon categories"
            >
                <button
                    type="button"
                    role="tab"
                    aria-selected={activeCategory === null}
                    disabled={disabled}
                    onClick={() => onCategoryChange?.(null)}
                    className={chipClass(activeCategory === null)}
                >
                    All
                </button>
                {categories.map((item) => (
                    <button
                        key={item}
                        type="button"
                        role="tab"
                        aria-selected={activeCategory === item}
                        disabled={disabled}
                        onClick={() => onCategoryChange?.(item)}
                        className={chipClass(activeCategory === item)}
                    >
                        {item}
                    </button>
                ))}
            </div>
        </div>
    );
}
