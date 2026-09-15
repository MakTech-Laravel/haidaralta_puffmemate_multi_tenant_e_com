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
    className?: string;
};

export function ProductGallery({ images, alt, className }: ProductGalleryProps) {
    const gallery =
        images.length > 0
            ? images
            : ['/images/storefront/product-marlboro-gold.jpg'];
    const [activeIndex, setActiveIndex] = useState(0);
    const activeImage = gallery[activeIndex] ?? gallery[0];
    const showThumbNav = gallery.length > 5;

    return (
        <div className={cn('flex flex-col gap-3', className)}>
            <div className="bg-muted relative aspect-square overflow-hidden">
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
                <CarouselContent className="-ml-2">
                    {gallery.map((image, index) => (
                        <CarouselItem
                            key={`${image}-${index}`}
                            className="basis-1/5 pl-2"
                        >
                            <button
                                type="button"
                                onClick={() => setActiveIndex(index)}
                                className={cn(
                                    'bg-muted relative mx-auto aspect-square w-full max-w-16 overflow-hidden border-2 transition sm:max-w-20',
                                    index === activeIndex
                                        ? 'border-primary'
                                        : 'border-transparent hover:border-border',
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
