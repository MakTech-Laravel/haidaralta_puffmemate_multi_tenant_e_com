import { Link, usePage } from '@inertiajs/react';
import { Menu, ShoppingCart, User } from 'lucide-react';
import { useState } from 'react';
import { NotificationsPopover } from '@/components/notifications/notifications-popover';
import type { NotificationItem } from '@/components/notifications/types';
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
import { about, cart, contact, dashboard, faqs, home, login, shop, wholesale } from '@/routes';
import {
    index as notificationsIndex,
    show as notificationsShow,
} from '@/routes/customer/notifications';
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
        case 'faqs':
            return faqs.url();
        case 'contact':
            return contact.url();
        case 'wholesale':
            return wholesale.url();
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

    if (item.route === 'shop') {
        return url === '/shop' || url.startsWith('/shop?');
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

function isAccountArea(url: string): boolean {
    return (
        url === '/dashboard' ||
        url.startsWith('/dashboard?') ||
        url === '/orders' ||
        url.startsWith('/orders?') ||
        url.startsWith('/orders/') ||
        url === '/notifications' ||
        url.startsWith('/notifications?') ||
        url.startsWith('/notifications/') ||
        url === '/account' ||
        url.startsWith('/account?') ||
        url === '/product-to-review' ||
        url.startsWith('/product-to-review?') ||
        url.startsWith('/product-to-review/') ||
        url === '/activity' ||
        url.startsWith('/activity?') ||
        url.startsWith('/activity/') ||
        url.startsWith('/settings')
    );
}

export function StorefrontHeader() {
    const page = usePage();
    const { auth, name, customerNotifications = [] } = page.props as {
        auth: { user: { avatar?: string } | null };
        name: string;
        customerNotifications?: NotificationItem[];
    };
    const url = page.url;
    const [mobileOpen, setMobileOpen] = useState(false);
    const accountHref = auth.user ? dashboard() : login();
    const accountActive = Boolean(auth.user) && isAccountArea(url);
    const avatarUrl = auth.user?.avatar;
    const cartActive =
        url === '/cart' ||
        url.startsWith('/cart?') ||
        url === '/checkout' ||
        url.startsWith('/checkout?');
    const items = storefrontNavItems;

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
                        {items.map((item) => (
                            <NavLabel key={item.label} item={item} url={url} />
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-1 sm:gap-2">
                        <Link
                            href={cart()}
                            prefetch
                            className={cn(
                                'relative inline-flex size-10 items-center justify-center rounded-md transition-colors',
                                cartActive
                                    ? 'text-primary after:bg-primary after:absolute after:inset-x-2 after:bottom-0.5 after:h-0.5'
                                    : 'text-foreground',
                            )}
                            aria-label="Cart"
                            aria-current={cartActive ? 'page' : undefined}
                        >
                            <ShoppingCart className="size-6 md:size-8" strokeWidth={1.75} />
                        </Link>

                        <NotificationsPopover
                            items={customerNotifications}
                            viewAllHref={notificationsIndex.url()}
                            getItemHref={(item) => notificationsShow.url(item.id)}
                            isGuest={!auth.user}
                            guestHref={login.url()}
                        />

                        <Link
                            href={accountHref}
                            prefetch
                            className={cn(
                                'relative inline-flex size-10 items-center justify-center rounded-md transition-colors',
                                accountActive
                                    ? 'text-primary after:bg-primary after:absolute after:inset-x-2 after:bottom-0.5 after:h-0.5'
                                    : 'text-foreground',
                            )}
                            aria-label="Account"
                            aria-current={accountActive ? 'page' : undefined}
                        >
                            {avatarUrl ? (
                                <img
                                    src={avatarUrl}
                                    alt=""
                                    className="size-8 rounded-full object-cover"
                                />
                            ) : (
                                <User className="size-6 md:size-8" strokeWidth={1.75} />
                            )}
                        </Link>

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
                                <nav className="mt-6 flex flex-col gap-4 px-5">
                                    {items.map((item) => (
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
