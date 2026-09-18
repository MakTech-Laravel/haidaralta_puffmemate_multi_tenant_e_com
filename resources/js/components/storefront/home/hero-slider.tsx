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
import type { StorefrontHeroSlide } from '@/lib/storefront-demo';
import { cn } from '@/lib/utils';

type HomeHeroSliderProps = {
    slides: StorefrontHeroSlide[];
    className?: string;
};

const AUTOPLAY_MS = 4000;

const navButtonClass =
    'border-primary-foreground/25 bg-foreground/30 text-primary-foreground hover:bg-foreground/50 hover:text-primary-foreground size-8 rounded-full border backdrop-blur-sm transition disabled:opacity-40 sm:size-10';

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
                'relative isolate w-full min-h-112 overflow-hidden sm:min-h-128 lg:min-h-144',
                className,
            )}
        >
            <CarouselContent className="ml-0">
                {slides.map((slide) => (
                    <CarouselItem
                        key={slide.id}
                        className="relative isolate min-h-112 pl-0 basis-full sm:min-h-128 lg:min-h-144"
                    >
                        <div
                            className="absolute inset-0 bg-foreground bg-cover bg-center"
                            style={{ backgroundImage: `url(${slide.image})` }}
                            aria-hidden
                        />
                        <div
                            className="absolute inset-0 bg-linear-to-r from-foreground/72 via-foreground/45 to-foreground/25"
                            aria-hidden
                        />

                        <div className="relative mx-auto flex h-full min-h-112 container flex-col justify-center px-4 py-12 sm:min-h-128 sm:px-6 sm:py-16 lg:min-h-144">
                            <div className="text-primary-foreground max-w-md sm:max-w-lg lg:max-w-xl">
                                <p className="font-inter mb-2 text-xs font-normal tracking-[0.12em] uppercase opacity-90 sm:mb-3 sm:text-sm md:text-base lg:text-xl lg:tracking-normal">
                                    {slide.eyebrow}
                                </p>
                                <h1 className="mb-3 text-4xl leading-[1.05] font-medium tracking-tight sm:mb-4 sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl">
                                    {slide.title}
                                </h1>
                                <p className="text-primary-foreground/85 mb-6 max-w-md text-sm leading-relaxed font-normal sm:mb-8 sm:text-base md:text-lg lg:text-xl">
                                    {slide.description}
                                </p>
                                <div className="flex flex-wrap gap-2 sm:gap-3">
                                    <Button
                                        asChild
                                        size="lg"
                                        className="h-10 px-4 text-sm sm:h-11 sm:px-8 sm:text-base"
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
                                        className="border-primary-foreground/40 bg-foreground/30 text-primary-foreground hover:bg-foreground/50 hover:text-primary-foreground h-10 px-4 text-sm sm:h-11 sm:px-8 sm:text-base"
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
