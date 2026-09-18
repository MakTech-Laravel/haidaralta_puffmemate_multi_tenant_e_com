import { ImageIcon } from 'lucide-react';
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
import {
    colorPickerStyle,
    isTransparentColorPickerValue,
    normalizeCatalogColorPickerValue,
    type ColorPickerValue,
} from '@/lib/color-picker';
import { cn } from '@/lib/utils';

export type ProductGalleryImage = {
    id: number;
    url: string;
    path?: string;
    sort_order?: number;
};

type MediaItem = {
    key: string;
    url: string;
    label?: string;
};

type ProductGalleryProps = {
    productName: string;
    images: ProductGalleryImage[];
    colorCode?: ColorPickerValue | null;
    className?: string;
    /** When false, render only the grid (no Card chrome). Default true. */
    card?: boolean;
    /** Large featured stage with primary + gallery thumbs. `tile` is a compact square preview. */
    layout?: 'grid' | 'hero' | 'tile';
    primaryUrl?: string | null;
    /** Applied to the wrapping Card when `card` is true. */
    cardClassName?: string;
};

function useGalleryPresentation({
    images,
    primaryUrl,
    colorCode,
}: {
    images: ProductGalleryImage[];
    primaryUrl?: string | null;
    colorCode?: ColorPickerValue | null;
}) {
    const normalizedColor = normalizeCatalogColorPickerValue(colorCode);
    const tileStyle = isTransparentColorPickerValue(normalizedColor)
        ? undefined
        : colorPickerStyle(normalizedColor);

    const items = useMemo<MediaItem[]>(() => {
        const media: MediaItem[] = [];

        if (primaryUrl) {
            media.push({
                key: 'primary',
                url: primaryUrl,
                label: 'Primary',
            });
        }

        images.forEach((image, index) => {
            media.push({
                key: `gallery-${image.id}`,
                url: image.url,
                label: `Gallery ${index + 1}`,
            });
        });

        return media;
    }, [images, primaryUrl]);

    const slides = useMemo(
        () => items.map((item) => ({ src: item.url })),
        [items],
    );

    return { items, slides, tileStyle };
}

export function ProductGallery({
    productName,
    images,
    colorCode,
    className,
    card = true,
    layout = 'grid',
    primaryUrl = null,
    cardClassName,
}: ProductGalleryProps) {
    const [open, setOpen] = useState(false);
    const [index, setIndex] = useState(0);
    const { items, slides, tileStyle } = useGalleryPresentation({
        images,
        primaryUrl,
        colorCode,
    });

    const activeIndex =
        items.length === 0 ? 0 : Math.min(index, items.length - 1);
    const active = items[activeIndex];

    const lightbox =
        slides.length > 0 ? (
            <Lightbox
                open={open}
                close={() => setOpen(false)}
                index={activeIndex}
                slides={slides}
                plugins={[Zoom]}
                zoom={{ maxZoomPixelRatio: 4 }}
                render={{
                    slide: ({ slide }) => (
                        <div
                            className="relative flex h-full w-full items-center justify-center p-6"
                            style={tileStyle}
                        >
                            <img
                                src={slide.src}
                                alt=""
                                className="max-h-full max-w-full object-contain"
                                draggable={false}
                            />
                        </div>
                    ),
                }}
            />
        ) : null;

    const openAt = (nextIndex: number): void => {
        setIndex(nextIndex);
        setOpen(true);
    };

    const empty = (
        <div
            className="border-border/70 bg-muted/20 flex aspect-square w-full flex-col items-center justify-center rounded-xl border border-dashed px-6 text-center"
            style={tileStyle}
        >
            <ImageIcon className="text-muted-foreground mb-3 size-8" />
            <p className="text-sm font-medium">No product images</p>
            <p className="text-muted-foreground mt-1 max-w-xs text-xs">
                Add a primary photo and gallery from Edit so this listing has a
                storefront preview.
            </p>
        </div>
    );

    const hero = (
        <div className={cn('flex flex-col gap-3', className)}>
            {items.length === 0 ? (
                empty
            ) : (
                <>
                    <button
                        type="button"
                        className="group border-border/70 hover:border-primary/40 focus-visible:border-ring focus-visible:ring-ring/50 relative flex aspect-square w-full items-center justify-center overflow-hidden rounded-2xl border transition outline-none focus-visible:ring-[3px]"
                        style={tileStyle}
                        onClick={() => openAt(activeIndex)}
                        aria-label={`Preview ${active?.label ?? productName}`}
                    >
                        {active ? (
                            <img
                                src={active.url}
                                alt={productName}
                                className="size-full object-contain p-5 transition group-hover:scale-[1.02]"
                            />
                        ) : null}
                        {active?.label === 'Primary' ? (
                            <span className="bg-background/90 text-foreground absolute top-3 left-3 rounded-full border px-2.5 py-1 text-[11px] font-medium tracking-wide uppercase shadow-sm">
                                Primary
                            </span>
                        ) : null}
                        {items.length > 1 ? (
                            <span className="bg-background/90 text-foreground absolute right-3 bottom-3 rounded-md border px-2 py-0.5 text-[11px] font-medium tabular-nums shadow-sm">
                                {activeIndex + 1}/{items.length}
                            </span>
                        ) : null}
                    </button>
                    {items.length > 1 ? (
                        <div className="flex gap-2 overflow-x-auto pb-1">
                            {items.map((item, imageIndex) => (
                                <button
                                    key={item.key}
                                    type="button"
                                    className={cn(
                                        'size-14 shrink-0 overflow-hidden rounded-lg border transition outline-none sm:size-16',
                                        imageIndex === activeIndex
                                            ? 'border-primary ring-primary/30 ring-1'
                                            : 'border-border/70 hover:border-primary/40',
                                    )}
                                    style={tileStyle}
                                    onClick={() => setIndex(imageIndex)}
                                    onDoubleClick={() => openAt(imageIndex)}
                                    aria-label={`Show ${item.label ?? `image ${imageIndex + 1}`}`}
                                    aria-pressed={imageIndex === activeIndex}
                                >
                                    <img
                                        src={item.url}
                                        alt=""
                                        className="size-full object-contain p-1"
                                    />
                                </button>
                            ))}
                        </div>
                    ) : null}
                </>
            )}
            {lightbox}
        </div>
    );

    const tileEmpty = (
        <div
            className="border-border/70 bg-muted/20 flex aspect-square w-full flex-col items-center justify-center rounded-xl border border-dashed px-3 text-center"
            style={tileStyle}
        >
            <ImageIcon className="text-muted-foreground mb-2 size-6" />
            <p className="text-muted-foreground text-xs">No listing photo</p>
        </div>
    );

    const tile = (
        <div className={cn('flex w-full flex-col gap-2', className)}>
            {items.length === 0 ? (
                tileEmpty
            ) : (
                <>
                    <button
                        type="button"
                        className="group border-border/70 hover:border-primary/40 focus-visible:border-ring focus-visible:ring-ring/50 relative aspect-square w-full overflow-hidden rounded-xl border transition outline-none focus-visible:ring-[3px]"
                        style={tileStyle}
                        onClick={() => openAt(activeIndex)}
                        aria-label={`Preview ${active?.label ?? productName}`}
                    >
                        {active ? (
                            <img
                                src={active.url}
                                alt={productName}
                                className="size-full object-contain p-2.5 transition group-hover:scale-[1.02]"
                            />
                        ) : null}
                    </button>
                    {items.length > 1 ? (
                        <div className="flex gap-1.5 overflow-x-auto pb-0.5">
                            {items.map((item, imageIndex) => (
                                <button
                                    key={item.key}
                                    type="button"
                                    className={cn(
                                        'size-10 shrink-0 overflow-hidden rounded-md border transition outline-none',
                                        imageIndex === activeIndex
                                            ? 'border-primary ring-primary/30 ring-1'
                                            : 'border-border/70 hover:border-primary/40',
                                    )}
                                    style={tileStyle}
                                    onClick={() => setIndex(imageIndex)}
                                    onDoubleClick={() => openAt(imageIndex)}
                                    aria-label={`Show ${item.label ?? `image ${imageIndex + 1}`}`}
                                    aria-pressed={imageIndex === activeIndex}
                                >
                                    <img
                                        src={item.url}
                                        alt=""
                                        className="size-full object-contain p-0.5"
                                        loading="lazy"
                                    />
                                </button>
                            ))}
                        </div>
                    ) : null}
                </>
            )}
            {lightbox}
        </div>
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
                            className="group border-border/70 hover:border-primary/40 focus-visible:border-ring focus-visible:ring-ring/50 overflow-hidden rounded-lg border transition outline-none focus-visible:ring-[3px]"
                            style={tileStyle}
                            onClick={() => openAt(imageIndex)}
                            aria-label={`Preview gallery image ${imageIndex + 1}`}
                        >
                            <img
                                src={image.url}
                                alt={`${productName} gallery ${imageIndex + 1}`}
                                className="aspect-square size-full object-contain p-1 transition group-hover:scale-[1.03]"
                                loading="lazy"
                            />
                        </button>
                    ))}
                </div>
            )}
            {lightbox}
        </>
    );

    const body = layout === 'hero' ? hero : layout === 'tile' ? tile : grid;

    if (!card) {
        return body;
    }

    const mediaCount = items.length;

    return (
        <Card className={cn('h-full gap-4 py-5 shadow-sm', cardClassName)}>
            <CardHeader className="px-5">
                <CardTitle className="text-base">
                    {layout === 'hero' ? 'Media' : 'Gallery'}
                </CardTitle>
                <CardDescription>
                    {layout === 'hero'
                        ? mediaCount > 0
                            ? `${mediaCount} image${mediaCount === 1 ? '' : 's'} · click to preview`
                            : 'Primary listing photo and extra gallery images'
                        : 'Extra images — click a thumbnail to preview'}
                </CardDescription>
            </CardHeader>
            <CardContent className="flex min-h-0 flex-1 flex-col px-5">
                {body}
            </CardContent>
        </Card>
    );
}
