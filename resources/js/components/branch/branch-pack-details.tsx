import { useState } from 'react';
import { type PackProductOption } from '@/components/branch/branch-pack-form';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import { cn } from '@/lib/utils';

export type PackDetails = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image_url: string | null;
    gallery: { id: number; url: string }[];
    offer_type: string;
    offer_value: string;
    subtotal: string;
    pack_price: string;
    quantity: number;
    status: boolean;
    is_highlighted?: boolean;
    starts_at: string | null;
    ends_at: string | null;
    items: { quantity: number; product: PackProductOption }[];
};

function initials(name: string): string {
    return name.slice(0, 2).toUpperCase();
}

function formatAud(value: string): string {
    const amount = Number(value);

    return Number.isFinite(amount) ? `$${amount.toFixed(2)}` : value;
}

function offerLabel(pack: PackDetails): string {
    if (pack.offer_type === 'percent') {
        return `${Number(pack.offer_value)}% off`;
    }

    if (pack.offer_type === 'fixed') {
        return `${formatAud(pack.offer_value)} off`;
    }

    return `Pack price ${formatAud(pack.pack_price)}`;
}

export function BranchPackDetails({
    pack,
    branchName,
}: {
    pack: PackDetails;
    branchName?: string;
}) {
    const gallery =
        pack.gallery.length > 0
            ? pack.gallery
            : [{ id: 1, url: pack.image_url ?? '' }];
    const [activeIndex, setActiveIndex] = useState(0);
    const activeImage = gallery[activeIndex] ?? gallery[0];
    const showThumbNav = gallery.length > 3;

    return (
        <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)]">
            <section className="border-border/80 bg-card space-y-4 rounded-2xl border p-5 shadow-sm">
                <div className="bg-muted relative aspect-square overflow-hidden">
                    {activeImage?.url ? (
                        <div
                            className="absolute inset-0 bg-cover bg-center"
                            style={{
                                backgroundImage: `url(${activeImage.url})`,
                            }}
                            role="img"
                            aria-label={pack.name}
                        />
                    ) : (
                        <div className="text-muted-foreground flex h-full items-center justify-center">
                            No image
                        </div>
                    )}
                </div>

                <Carousel
                    opts={{ align: 'start', dragFree: true }}
                    className={cn('w-full', showThumbNav && 'px-8')}
                >
                    <CarouselContent className="-ml-2">
                        {gallery.map((image, index) => (
                            <CarouselItem
                                key={`${image.url}-${index}`}
                                className="basis-1/3 pl-2"
                            >
                                <button
                                    type="button"
                                    onClick={() => setActiveIndex(index)}
                                    className={cn(
                                        'bg-muted relative mx-auto aspect-square w-full max-w-16 overflow-hidden border-2 transition sm:max-w-20',
                                        index === activeIndex
                                            ? 'border-primary'
                                            : 'hover:border-border border-transparent',
                                    )}
                                    aria-label={`View image ${index + 1}`}
                                    aria-current={
                                        index === activeIndex
                                            ? 'true'
                                            : undefined
                                    }
                                >
                                    <div
                                        className="absolute inset-0 bg-cover bg-center"
                                        style={{
                                            backgroundImage: `url(${image.url})`,
                                        }}
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
            </section>

            <section className="border-border/80 bg-card space-y-5 rounded-2xl border p-5 shadow-sm sm:p-6">
                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant={pack.status ? 'default' : 'secondary'}>
                        {pack.status ? 'Active' : 'Inactive'}
                    </Badge>
                    {pack.is_highlighted ? (
                        <Badge variant="outline">Highlighted</Badge>
                    ) : null}
                    <Badge variant="outline">{offerLabel(pack)}</Badge>
                    <Badge variant="outline">{pack.quantity} to sell</Badge>
                </div>

                <dl className="grid gap-3 sm:grid-cols-2">
                    {branchName ? (
                        <div>
                            <dt className="text-muted-foreground text-sm">
                                Branch
                            </dt>
                            <dd>{branchName}</dd>
                        </div>
                    ) : null}
                    <div>
                        <dt className="text-muted-foreground text-sm">
                            Subtotal
                        </dt>
                        <dd className="tabular-nums">
                            <s className="text-muted-foreground">
                                {formatAud(pack.subtotal)}
                            </s>
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground text-sm">
                            Pack price
                        </dt>
                        <dd className="font-medium tabular-nums">
                            {formatAud(pack.pack_price)}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground text-sm">Dates</dt>
                        <dd>
                            {pack.starts_at || pack.ends_at
                                ? `${pack.starts_at ?? '—'} – ${pack.ends_at ?? '—'}`
                                : 'Always on'}
                        </dd>
                    </div>
                </dl>

                <ul className="divide-border divide-y rounded-xl border">
                    {pack.items.map((item) => (
                        <li
                            key={item.product.id}
                            className="flex items-center gap-3 p-3"
                        >
                            <Avatar className="size-10 rounded-md">
                                <AvatarImage
                                    src={item.product.image_url ?? undefined}
                                    alt={item.product.name}
                                />
                                <AvatarFallback className="rounded-md">
                                    {initials(item.product.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-medium">
                                    {item.product.name}
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    {item.product.sku ?? 'No SKU'}
                                </p>
                            </div>
                            <p className="text-sm tabular-nums">
                                ×{item.quantity}
                            </p>
                        </li>
                    ))}
                </ul>
            </section>
        </div>
    );
}
