import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import PacksSavesController from '@/actions/App/Http/Controllers/Branch/PacksSavesController';
import { type PackProductOption } from '@/components/branch/branch-pack-form';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Branch = { id: number; name: string; slug: string };

type PackShow = {
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

function offerLabel(pack: PackShow): string {
    if (pack.offer_type === 'percent') {
        return `${Number(pack.offer_value)}% off`;
    }

    if (pack.offer_type === 'fixed') {
        return `${formatAud(pack.offer_value)} off`;
    }

    return `Pack price ${formatAud(pack.pack_price)}`;
}

export default function BranchPacksSavesShow({
    branch,
    pack,
}: {
    branch: Branch;
    pack: PackShow;
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Packs & Saves',
                href: PacksSavesController.index.url(branch.slug),
            },
            {
                title: pack.name,
                href: PacksSavesController.show.url({
                    branch: branch.slug,
                    pack: pack.slug,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={pack.name} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={pack.name}
                        description={
                            pack.description ??
                            `Bundle of ${pack.items.length} products at ${branch.name}`
                        }
                    />
                    <Button asChild>
                        <Link
                            href={PacksSavesController.edit.url({
                                branch: branch.slug,
                                pack: pack.slug,
                            })}
                        >
                            <Pencil className="size-4" />
                            Edit
                        </Link>
                    </Button>
                </div>

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)]">
                    <section className="border-border/80 bg-card space-y-4 rounded-2xl border p-5 shadow-sm">
                        <Avatar className="aspect-square size-full max-h-64 rounded-xl">
                            <AvatarImage
                                src={pack.image_url ?? undefined}
                                alt={pack.name}
                                className="object-contain"
                            />
                            <AvatarFallback className="rounded-xl text-2xl">
                                {initials(pack.name)}
                            </AvatarFallback>
                        </Avatar>
                        {pack.gallery.length > 0 ? (
                            <div className="grid grid-cols-4 gap-2">
                                {pack.gallery.map((image) => (
                                    <img
                                        key={image.id}
                                        src={image.url}
                                        alt=""
                                        className="bg-muted/20 aspect-square rounded-md object-contain"
                                    />
                                ))}
                            </div>
                        ) : null}
                    </section>

                    <section className="border-border/80 bg-card space-y-5 rounded-2xl border p-5 shadow-sm sm:p-6">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge
                                variant={
                                    pack.status ? 'default' : 'secondary'
                                }
                            >
                                {pack.status ? 'Active' : 'Inactive'}
                            </Badge>
                            <Badge variant="outline">{offerLabel(pack)}</Badge>
                            <Badge variant="outline">
                                {pack.quantity} to sell
                            </Badge>
                        </div>

                        <dl className="grid gap-3 sm:grid-cols-2">
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
                                <dt className="text-muted-foreground text-sm">
                                    Dates
                                </dt>
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
                                            src={
                                                item.product.image_url ??
                                                undefined
                                            }
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
            </div>
        </>
    );
}
