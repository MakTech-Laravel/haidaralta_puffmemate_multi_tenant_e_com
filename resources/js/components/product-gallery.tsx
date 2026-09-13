import { useMemo, useState } from 'react';
import Lightbox from 'yet-another-react-lightbox';
import Zoom from 'yet-another-react-lightbox/plugins/zoom';
import 'yet-another-react-lightbox/styles.css';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

export type ProductGalleryImage = {
    id: number;
    url: string;
    path?: string;
    sort_order?: number;
};

type ProductGalleryProps = {
    productName: string;
    images: ProductGalleryImage[];
    className?: string;
    /** When false, render only the grid (no Card chrome). Default true. */
    card?: boolean;
};

export function ProductGallery({
    productName,
    images,
    className,
    card = true,
}: ProductGalleryProps) {
    const [open, setOpen] = useState(false);
    const [index, setIndex] = useState(0);

    const slides = useMemo(
        () => images.map((image) => ({ src: image.url })),
        [images],
    );

    const grid = (
        <>
            {images.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    No gallery images yet.
                </p>
            ) : (
                <div
                    className={cn(
                        'grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10',
                        className,
                    )}
                >
                    {images.map((image, imageIndex) => (
                        <button
                            key={image.id}
                            type="button"
                            className="group overflow-hidden rounded-lg border border-border/70 bg-muted/20 outline-none transition hover:border-primary/40 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            onClick={() => {
                                setIndex(imageIndex);
                                setOpen(true);
                            }}
                            aria-label={`Preview gallery image ${imageIndex + 1}`}
                        >
                            <img
                                src={image.url}
                                alt={`${productName} gallery ${imageIndex + 1}`}
                                className="aspect-square size-full object-cover transition group-hover:scale-[1.03]"
                                loading="lazy"
                            />
                        </button>
                    ))}
                </div>
            )}
            {slides.length > 0 ? (
                <Lightbox
                    open={open}
                    close={() => setOpen(false)}
                    index={index}
                    slides={slides}
                    plugins={[Zoom]}
                    zoom={{ maxZoomPixelRatio: 4 }}
                />
            ) : null}
        </>
    );

    if (!card) {
        return grid;
    }

    return (
        <Card className="gap-4 py-5 shadow-none">
            <CardHeader className="px-5">
                <CardTitle className="text-base">Gallery</CardTitle>
                <CardDescription>
                    Extra images — click a thumbnail to preview
                </CardDescription>
            </CardHeader>
            <CardContent className="px-5">{grid}</CardContent>
        </Card>
    );
}
