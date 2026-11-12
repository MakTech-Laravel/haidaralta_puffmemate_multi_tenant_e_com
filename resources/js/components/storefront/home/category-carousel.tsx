import { useEffect, useState } from 'react';
import { CategoryCard } from '@/components/storefront/category-card';
import {
    Carousel,
    type CarouselApi,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import type { StorefrontCategoryItem } from '@/types/storefront';
import { cn } from '@/lib/utils';

type CategoryCarouselProps = {
    categories: StorefrontCategoryItem[];
    className?: string;
};

const AUTOPLAY_MS = 4000;

/** Matches Tailwind `lg` — carousel only below this width. */
const LG_BREAKPOINT = 1024;

const navButtonClass =
    'border-border bg-background text-foreground hover:bg-muted size-8 rounded-full border shadow-sm disabled:opacity-40 sm:size-9';

export function CategoryCarousel({
    categories,
    className,
}: CategoryCarouselProps) {
    const [api, setApi] = useState<CarouselApi>();

    useEffect(() => {
        if (!api || categories.length <= 1) {
            return;
        }

        const mql = window.matchMedia(
            `(max-width: ${LG_BREAKPOINT - 1}px)`,
        );

        const tick = () => {
            if (mql.matches) {
                api.scrollNext();
            }
        };

        const id = window.setInterval(tick, AUTOPLAY_MS);

        return () => {
            window.clearInterval(id);
        };
    }, [api, categories.length]);

    if (categories.length === 0) {
        return null;
    }

    const canNavigate = categories.length > 1;

    return (
        <div className={cn('w-full', className)}>
            <div className="lg:hidden">
                <Carousel
                    opts={{ loop: true, align: 'start' }}
                    setApi={setApi}
                    className="relative w-full"
                >
                    <CarouselContent className="-ml-4">
                        {categories.map((category) => (
                            <CarouselItem
                                key={category.id}
                                className="basis-1/2 pl-4 md:basis-1/3"
                            >
                                <CategoryCard category={category} />
                            </CarouselItem>
                        ))}
                    </CarouselContent>

                    {canNavigate ? (
                        <>
                            <CarouselPrevious
                                className={cn(
                                    navButtonClass,
                                    'top-1/2 left-0 z-10 hidden -translate-y-1/2 sm:flex',
                                )}
                            />
                            <CarouselNext
                                className={cn(
                                    navButtonClass,
                                    'top-1/2 right-0 z-10 hidden -translate-y-1/2 sm:flex',
                                )}
                            />
                        </>
                    ) : null}
                </Carousel>
            </div>

            <div className="hidden gap-3.5 lg:grid lg:grid-cols-6">
                {categories.map((category) => (
                    <CategoryCard key={category.id} category={category} />
                ))}
            </div>
        </div>
    );
}
