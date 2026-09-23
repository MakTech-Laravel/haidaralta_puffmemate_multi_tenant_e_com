import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import Heading from '@/components/heading';
import { AppDateTime } from '@/components/app-datetime';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';

type BannerRow = {
    id: number;
    sort_order: number;
    is_active: boolean;
    title: string;
    eyebrow: string;
    image_url: string | null;
    updated_at: string | null;
};

export default function PlatformHomeBannersIndex({
    banners,
}: {
    banners: BannerRow[];
}) {
    return (
        <>
            <Head title="Home banners" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Home banners"
                        description="These slides appear on the homepage banner. Keep titles short and use a clear photo."
                    />
                    <Button asChild>
                        <Link href="/platform/home-banners/create">
                            <Plus className="size-4" />
                            Add banner
                        </Link>
                    </Button>
                </div>

                {banners.length === 0 ? (
                    <div className="border-border/80 bg-card rounded-2xl border p-8 text-center shadow-sm">
                        <p className="text-muted-foreground text-sm">
                            No banners yet. Add one to show a homepage slider.
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href="/platform/home-banners/create">
                                <Plus className="size-4" />
                                Add banner
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {banners.map((banner, index) => (
                            <div
                                key={banner.id}
                                className={cn(
                                    'border-border/80 bg-card flex flex-col gap-4 rounded-2xl border p-4 shadow-sm sm:flex-row sm:items-center',
                                )}
                            >
                                <div className="bg-muted relative aspect-video w-full overflow-hidden rounded-xl sm:w-40 sm:shrink-0">
                                    {banner.image_url ? (
                                        <img
                                            src={banner.image_url}
                                            alt=""
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <div className="text-muted-foreground flex size-full items-center justify-center text-xs">
                                            No image
                                        </div>
                                    )}
                                </div>

                                <div className="min-w-0 flex-1 space-y-1">
                                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                        {banner.eyebrow || '—'}
                                    </p>
                                    <p className="truncate text-base font-semibold tracking-tight">
                                        {banner.title}
                                    </p>
                                    <div className="flex flex-wrap items-center gap-2 pt-1">
                                        <Badge
                                            variant={
                                                banner.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {banner.is_active
                                                ? 'Visible'
                                                : 'Hidden'}
                                        </Badge>
                                        {banner.updated_at ? (
                                            <span className="text-muted-foreground text-xs">
                                                Updated{' '}
                                                <AppDateTime
                                                    value={banner.updated_at}
                                                />
                                            </span>
                                        ) : null}
                                    </div>
                                </div>

                                <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <div className="mr-2 flex items-center gap-2">
                                        <span className="text-muted-foreground text-xs">
                                            Show
                                        </span>
                                        <Switch
                                            checked={banner.is_active}
                                            onCheckedChange={(checked) =>
                                                router.patch(
                                                    `/platform/home-banners/${banner.id}/toggle`,
                                                    { is_active: checked },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                    </div>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        aria-label="Move up"
                                        disabled={index === 0}
                                        onClick={() =>
                                            router.post(
                                                `/platform/home-banners/${banner.id}/move`,
                                                { direction: 'up' },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <ArrowUp className="size-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        aria-label="Move down"
                                        disabled={index === banners.length - 1}
                                        onClick={() =>
                                            router.post(
                                                `/platform/home-banners/${banner.id}/move`,
                                                { direction: 'down' },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <ArrowDown className="size-4" />
                                    </Button>

                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={`/platform/home-banners/${banner.id}/edit`}
                                        >
                                            <Pencil className="size-3.5" />
                                            Edit
                                        </Link>
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Delete banner"
                                        onClick={() => {
                                            if (
                                                !window.confirm(
                                                    'Remove this banner from the homepage?',
                                                )
                                            ) {
                                                return;
                                            }

                                            router.delete(
                                                `/platform/home-banners/${banner.id}`,
                                                { preserveScroll: true },
                                            );
                                        }}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

PlatformHomeBannersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Home banners', href: '/platform/home-banners' },
    ],
};
