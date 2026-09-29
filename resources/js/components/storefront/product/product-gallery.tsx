import { useState } from 'react';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import { cn } from '@/lib/utils';

type ProductGalleryProps = {
    images: string[];
    alt: string;
    fallbackImage?: string;
    mainClassName?: string;
    className?: string;
};

export function ProductGallery({
    images,
    alt,
    fallbackImage = '/images/storefront/product-marlboro-gold.jpg',
    mainClassName,
    className,
}: ProductGalleryProps) {
    const gallery = images.length > 0 ? images : [fallbackImage];
    const [activeIndex, setActiveIndex] = useState(0);
    const activeImage = gallery[activeIndex] ?? gallery[0];
    const showThumbNav = gallery.length > 5;

    return (
        <div className={cn('flex flex-col gap-3', className)}>
            <div
                className={cn(
                    'bg-muted relative aspect-square overflow-hidden',
                    mainClassName,
                )}
            >
                <div
                    className="absolute inset-0 bg-cover bg-center"
                    style={{ backgroundImage: `url(${activeImage})` }}
                    role="img"
                    aria-label={alt}
                />
            </div>

            <Carousel
                opts={{ align: 'start', dragFree: true }}
                className={cn('w-full', showThumbNav && 'px-8')}
            >
                <CarouselContent className="-ml-1.5">
                    {gallery.map((image, index) => (
                        <CarouselItem
                            key={`${image}-${index}`}
                            className="basis-auto pl-1.5"
                        >
                            <button
                                type="button"
                                onClick={() => setActiveIndex(index)}
                                className={cn(
                                    'bg-muted relative size-16 overflow-hidden rounded-md border-2 transition sm:size-20',
                                    index === activeIndex
                                        ? 'border-primary'
                                        : 'hover:border-border border-transparent',
                                )}
                                aria-label={`View image ${index + 1}`}
                                aria-current={
                                    index === activeIndex ? 'true' : undefined
                                }
                            >
                                <div
                                    className="absolute inset-0 bg-cover bg-center"
                                    style={{ backgroundImage: `url(${image})` }}
                                    aria-hidden
                                />
                            </button>
                        </CarouselItem>
                    ))}
                </CarouselContent>
                {showThumbNav ? (
                    <>
                        <CarouselPrevious className="left-0 size-7" />
                        <CarouselNext className="right-0 size-7" />
                    </>
                ) : null}
            </Carousel>
        </div>
    );
}
