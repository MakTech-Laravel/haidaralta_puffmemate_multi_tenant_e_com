import { useEffect, useState } from 'react';
import { ProductCard } from '@/components/storefront/product-card';
import {
    Carousel,
    type CarouselApi,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import type { StorefrontProduct } from '@/lib/storefront-demo';
import { cn } from '@/lib/utils';

type ProductCarouselProps = {
    products: StorefrontProduct[];
    className?: string;
};

const AUTOPLAY_MS = 4000;

const navButtonClass =
    'border-border bg-background text-foreground hover:bg-muted size-8 rounded-full border shadow-sm disabled:opacity-40 sm:size-9';

export function ProductCarousel({ products, className }: ProductCarouselProps) {
    const [api, setApi] = useState<CarouselApi>();

    useEffect(() => {
        if (!api || products.length <= 1) {
            return;
        }

        const id = window.setInterval(() => {
            api.scrollNext();
        }, AUTOPLAY_MS);

        return () => {
            window.clearInterval(id);
        };
    }, [api, products.length]);

    if (products.length === 0) {
        return null;
    }

    const canNavigate = products.length > 1;

    return (
        <Carousel
            opts={{ loop: true, align: 'start' }}
            setApi={setApi}
            className={cn('relative w-full', className)}
        >
            <CarouselContent className="-ml-4">
                {products.map((product) => (
                    <CarouselItem
                        key={product.id}
                        className="basis-1/2 pl-4 md:basis-1/3 xl:basis-1/4"
                    >
                        <ProductCard product={product} />
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
    );
}
