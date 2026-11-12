import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Carousel,
    type CarouselApi,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import type { StorefrontHeroSlide } from '@/types/storefront';
import { cn } from '@/lib/utils';

type HomeHeroSliderProps = {
    slides: StorefrontHeroSlide[];
    className?: string;
};

const AUTOPLAY_MS = 4000;

const navButtonClass =
    'border-ink-surface-foreground/25 bg-ink-surface/30 text-ink-surface-foreground hover:bg-ink-surface/50 hover:text-ink-surface-foreground size-8 rounded-full border backdrop-blur-sm transition disabled:opacity-40 sm:size-10';

export function HomeHeroSlider({ slides, className }: HomeHeroSliderProps) {
    const [api, setApi] = useState<CarouselApi>();

    useEffect(() => {
        if (!api || slides.length <= 1) {
            return;
        }

        const id = window.setInterval(() => {
            api.scrollNext();
        }, AUTOPLAY_MS);

        return () => {
            window.clearInterval(id);
        };
    }, [api, slides.length]);

    if (slides.length === 0) {
        return null;
    }

    const canNavigate = slides.length > 1;

    return (
        <Carousel
            opts={{ loop: true }}
            setApi={setApi}
            className={cn(
                'relative isolate min-h-112 w-full overflow-hidden sm:min-h-128 lg:min-h-144',
                className,
            )}
        >
            <CarouselContent className="ml-0">
                {slides.map((slide) => (
                    <CarouselItem
                        key={slide.id}
                        className="relative isolate min-h-112 basis-full pl-0 sm:min-h-128 lg:min-h-144"
                    >
                        <div
                            className="bg-ink-surface absolute inset-0 bg-cover bg-center"
                            style={{ backgroundImage: `url(${slide.image})` }}
                            aria-hidden
                        />
                        <div
                            className="from-ink-surface/85 via-ink-surface/55 to-ink-surface/20 absolute inset-0 bg-linear-to-r"
                            aria-hidden
                        />

                        <div className="relative container mx-auto flex h-full min-h-112 flex-col justify-center px-4 py-12 sm:min-h-128 sm:px-6 sm:py-16 lg:min-h-144">
                            <div className="text-ink-surface-foreground max-w-md sm:max-w-lg lg:max-w-xl">
                                <p className="font-inter mb-2 text-xs font-medium tracking-[0.12em] uppercase opacity-90 sm:mb-3 sm:text-sm md:text-base lg:tracking-[0.08em]">
                                    {slide.eyebrow}
                                </p>
                                <h1 className="mb-3 text-4xl leading-[1.05] font-semibold tracking-tight sm:mb-4 sm:text-5xl md:text-6xl lg:text-7xl">
                                    {slide.title}
                                </h1>
                                <p className="text-ink-surface-foreground/85 mb-6 max-w-md text-sm leading-relaxed font-normal sm:mb-8 sm:text-base md:text-lg lg:text-xl">
                                    {slide.description}
                                </p>
                                <div className="flex flex-wrap gap-2 sm:gap-3">
                                    <Button
                                        asChild
                                        size="lg"
                                        className="h-11 px-5 text-sm sm:h-12 sm:px-8 sm:text-base"
                                    >
                                        {slide.primaryCta.href.startsWith(
                                            '#',
                                        ) ? (
                                            <a href={slide.primaryCta.href}>
                                                {slide.primaryCta.label}
                                            </a>
                                        ) : (
                                            <Link
                                                href={slide.primaryCta.href}
                                                prefetch
                                            >
                                                {slide.primaryCta.label}
                                            </Link>
                                        )}
                                    </Button>
                                    <Button
                                        asChild
                                        size="lg"
                                        variant="outline"
                                        className="border-ink-surface-foreground/40 bg-ink-surface-foreground/10 text-ink-surface-foreground hover:bg-ink-surface-foreground/20 hover:text-ink-surface-foreground h-11 px-5 text-sm backdrop-blur-sm sm:h-12 sm:px-8 sm:text-base"
                                    >
                                        {slide.secondaryCta.href.startsWith(
                                            '#',
                                        ) ? (
                                            <a href={slide.secondaryCta.href}>
                                                {slide.secondaryCta.label}
                                            </a>
                                        ) : (
                                            <Link
                                                href={slide.secondaryCta.href}
                                                prefetch
                                            >
                                                {slide.secondaryCta.label}
                                            </Link>
                                        )}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CarouselItem>
                ))}
            </CarouselContent>

            {canNavigate ? (
                <>
                    <CarouselPrevious
                        className={cn(
                            navButtonClass,
                            'top-1/2 left-2 z-10 hidden -translate-y-1/2 sm:left-6 sm:flex',
                        )}
                    />
                    <CarouselNext
                        className={cn(
                            navButtonClass,
                            'top-1/2 right-2 z-10 hidden -translate-y-1/2 sm:right-6 sm:flex',
                        )}
                    />
                </>
            ) : null}
        </Carousel>
    );
}
