import { useState } from 'react';
import { cn } from '@/lib/utils';

type ProductGalleryProps = {
    images: string[];
    alt: string;
    className?: string;
};

export function ProductGallery({ images, alt, className }: ProductGalleryProps) {
    const gallery = images.length > 0 ? images : ['/images/storefront/product-marlboro-gold.jpg'];
    const [activeIndex, setActiveIndex] = useState(0);
    const activeImage = gallery[activeIndex] ?? gallery[0];

    return (
        <div className={cn('flex flex-col gap-3', className)}>
            <div className="bg-muted relative aspect-square overflow-hidden rounded-xl">
                <div
                    className="absolute inset-0 bg-cover bg-center"
                    style={{ backgroundImage: `url(${activeImage})` }}
                    role="img"
                    aria-label={alt}
                />
            </div>
            <div className="grid grid-cols-3 gap-3">
                {gallery.slice(0, 3).map((image, index) => (
                    <button
                        key={`${image}-${index}`}
                        type="button"
                        onClick={() => setActiveIndex(index)}
                        className={cn(
                            'bg-muted relative aspect-square overflow-hidden border-2 transition',
                            index === activeIndex
                                ? 'border-primary'
                                : 'border-transparent hover:border-border',
                        )}
                        aria-label={`View image ${index + 1}`}
                    >
                        <div
                            className="absolute inset-0 bg-cover bg-center"
                            style={{ backgroundImage: `url(${image})` }}
                            aria-hidden
                        />
                    </button>
                ))}
            </div>
        </div>
    );
}
