import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import type { StorefrontHeroSlide } from '@/lib/storefront-demo';
import { cn } from '@/lib/utils';

type HeroSliderProps = {
    slides: StorefrontHeroSlide[];
    className?: string;
};

export function HeroSlider({ slides, className }: HeroSliderProps) {
    const [index, setIndex] = useState(0);

    if (slides.length === 0) {
        return null;
    }

    const slide = slides[index] ?? slides[0];
    const canNavigate = slides.length > 1;

    const goPrev = () => {
        setIndex((current) => (current === 0 ? slides.length - 1 : current - 1));
    };

    const goNext = () => {
        setIndex((current) => (current === slides.length - 1 ? 0 : current + 1));
    };

    return (
        <section
            className={cn(
                'relative isolate min-h-112 overflow-hidden sm:min-h-128 lg:min-h-144',
                className,
            )}
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

            <div className="relative mx-auto flex h-full min-h-112 container flex-col justify-center px-4 py-16 sm:min-h-128 sm:px-6 lg:min-h-144">
                <div className="text-primary-foreground max-w-xl">
                    <p className="font-inter mb-3 text-xl font-normal uppercase opacity-90">
                        {slide.eyebrow}
                    </p>
                    <h1 className="mb-4 text-8xl font-medium tracking-tight">
                        {slide.title}
                    </h1>
                    <p className="text-primary-foreground/85 mb-8 max-w-md text-base leading-relaxed font-normal sm:text-xl">
                        {slide.description}
                    </p>
                    <div className="flex flex-wrap gap-3">
                        <Button asChild size="lg">
                            <Link href={slide.primaryCta.href} prefetch>
                                {slide.primaryCta.label}
                            </Link>
                        </Button>
                        <Button
                            asChild
                            size="lg"
                            variant="outline"
                            className="border-primary-foreground/40 bg-foreground/30 text-primary-foreground hover:bg-foreground/50 hover:text-primary-foreground"
                        >
                            <Link href={slide.secondaryCta.href} prefetch>
                                {slide.secondaryCta.label}
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>

            {canNavigate ? (
                <>
                    <button
                        type="button"
                        onClick={goPrev}
                        aria-label="Previous slide"
                        className="border-primary-foreground/25 bg-foreground/30 text-primary-foreground hover:bg-foreground/50 absolute top-1/2 left-3 z-10 flex size-10 -translate-y-1/2 items-center justify-center rounded-full border backdrop-blur-sm transition sm:left-6"
                    >
                        <ChevronLeft className="size-5" />
                    </button>
                    <button
                        type="button"
                        onClick={goNext}
                        aria-label="Next slide"
                        className="border-primary-foreground/25 bg-foreground/30 text-primary-foreground hover:bg-foreground/50 absolute top-1/2 right-3 z-10 flex size-10 -translate-y-1/2 items-center justify-center rounded-full border backdrop-blur-sm transition sm:right-6"
                    >
                        <ChevronRight className="size-5" />
                    </button>
                </>
            ) : null}
        </section>
    );
}
