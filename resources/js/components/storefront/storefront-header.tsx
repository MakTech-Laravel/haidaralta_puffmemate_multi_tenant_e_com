import { Link, usePage } from '@inertiajs/react';
import { Menu, ShoppingCart, User } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    storefrontAnnouncement,
    storefrontLogoSrc,
    storefrontNavItems,
    type StorefrontChromeItem,
} from '@/lib/storefront-chrome';
import { cn } from '@/lib/utils';
import { about, contact, dashboard, home, login, shop } from '@/routes';
import { category as shopCategory } from '@/routes/shop';

function resolveHref(item: StorefrontChromeItem): string {
    if (item.categorySlug) {
        return shopCategory.url(item.categorySlug);
    }

    switch (item.route) {
        case 'home':
            return home.url();
        case 'shop':
            return shop.url();
        case 'about':
            return about.url();
        case 'contact':
            return contact.url();
        default:
            return shop.url();
    }
}

function isActive(url: string, item: StorefrontChromeItem): boolean {
    const href = resolveHref(item);

    if (item.categorySlug) {
        return url === href || url.startsWith(`${href}?`);
    }

    if (!item.route) {
        return false;
    }

    return href === '/' ? url === '/' : url.startsWith(href);
}

function NavLabel({
    item,
    url,
    className,
    onNavigate,
}: {
    item: StorefrontChromeItem;
    url: string;
    className?: string;
    onNavigate?: () => void;
}) {
    const href = resolveHref(item);
    const active = isActive(url, item);

    return (
        <Link
            href={href}
            prefetch
            onClick={onNavigate}
            className={cn(
                'relative inline-flex py-1 text-base font-medium transition-colors',
                active
                    ? 'text-primary after:bg-primary after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5'
                    : 'text-foreground/80',
                className,
            )}
        >
            {item.label}
        </Link>
    );
}

export function StorefrontHeader() {
    const page = usePage();
    const { auth, name } = page.props;
    const url = page.url;
    const [mobileOpen, setMobileOpen] = useState(false);
    const accountHref = auth.user ? dashboard() : login();

    return (
        <div className="sticky top-0 z-40">
            <div className="bg-foreground text-primary-foreground">
                <p className="px-4 py-2 text-center text-sm font-normal uppercase">
                    {storefrontAnnouncement}
                </p>
            </div>

            <header className="border-border bg-background border-b">
                <div className="mx-auto flex h-16 container items-center gap-4 px-4 sm:h-[4.5rem] sm:px-6">
                    <Link
                        href={home()}
                        prefetch
                        className="flex shrink-0 items-center"
                    >
                        <img
                            src={storefrontLogoSrc}
                            alt={name}
                            className="h-9 w-auto object-contain sm:h-11"
                        />
                    </Link>

                    <nav className="ml-4 hidden flex-1 items-center justify-center gap-5 lg:flex xl:gap-7">
                        {storefrontNavItems.map((item) => (
                            <NavLabel key={item.label} item={item} url={url} />
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-1 sm:gap-2">
                        <Link
                            href={shop()}
                            prefetch
                            className="text-foreground inline-flex size-10 items-center justify-center rounded-md"
                            aria-label="Cart"
                        >
                            <ShoppingCart className="size-8" strokeWidth={1.75} />
                        </Link>

                        <Button
                            asChild
                            variant="ghost"
                            size="icon"
                            className="text-foreground size-10"
                        >
                            <Link href={accountHref} aria-label="Account">
                                <User className="size-8" strokeWidth={1.75} />
                            </Link>
                        </Button>

                        <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="text-foreground size-10 lg:hidden"
                                    aria-label="Open menu"
                                >
                                    <Menu className="size-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="right" className="w-[min(100%,20rem)]">
                                <SheetHeader>
                                    <SheetTitle>Menu</SheetTitle>
                                </SheetHeader>
                                <nav className="mt-6 flex flex-col gap-4 px-1">
                                    {storefrontNavItems.map((item) => (
                                        <NavLabel
                                            key={item.label}
                                            item={item}
                                            url={url}
                                            className="text-base"
                                            onNavigate={() => setMobileOpen(false)}
                                        />
                                    ))}
                                </nav>
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>
            </header>
        </div>
    );
}
