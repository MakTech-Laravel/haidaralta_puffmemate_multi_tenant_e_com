import { Link, usePage } from '@inertiajs/react';
import { Menu, User } from 'lucide-react';
import { useState } from 'react';
import { CartSheet } from '@/components/storefront/cart/cart-sheet';
import { NotificationsPopover } from '@/components/notifications/notifications-popover';
import type { NotificationItem } from '@/components/notifications/types';
import { PwaInstallMenuItem } from '@/components/pwa/pwa-install-menu-item';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useAppBrand } from '@/hooks/use-app-brand';
import {
    buildStorefrontNavItems,
    storefrontAnnouncement,
    type StorefrontChromeItem,
    type StorefrontNavCategory,
} from '@/lib/storefront-chrome';
import { cn } from '@/lib/utils';
import {
    about,
    contact,
    dashboard,
    faqs,
    home,
    login,
    packsSaves,
    shop,
    wholesale,
} from '@/routes';
import {
    index as notificationsIndex,
    show as notificationsShow,
} from '@/routes/customer/notifications';
import { category as shopCategory } from '@/routes/shop';
import type { StorefrontCart } from '@/types/storefront';

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
        case 'packs-saves':
            return packsSaves.url();
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
                'relative inline-flex items-center rounded-full px-3.5 py-1.5 text-sm font-medium transition-colors',
                active
                    ? 'bg-muted text-foreground'
                    : 'text-foreground/70 hover:bg-muted/70 hover:text-foreground',
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
        url.startsWith('/platform/dashboard') ||
        url.startsWith('/driver/dashboard') ||
        /^\/branch\/[^/]+\/dashboard/.test(url) ||
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
    const brand = useAppBrand();
    const {
        auth,
        storefront,
        customerNotifications = [],
    } = page.props as {
        auth: {
            user: { avatar?: string } | null;
            dashboardUrl?: string | null;
        };
        storefront?: {
            navCategories?: StorefrontNavCategory[];
            cart?: StorefrontCart;
        };
        customerNotifications?: NotificationItem[];
    };
    const name = brand.name;
    const url = page.url;
    const [mobileOpen, setMobileOpen] = useState(false);
    const accountHref = auth.user
        ? (auth.dashboardUrl ?? dashboard())
        : login();
    const accountActive = Boolean(auth.user) && isAccountArea(url);
    const avatarUrl = auth.user?.avatar;
    const cartActive =
        url === '/cart' ||
        url.startsWith('/cart?') ||
        url === '/checkout' ||
        url.startsWith('/checkout?');
    const items = buildStorefrontNavItems(storefront?.navCategories ?? []);

    return (
        <div className="sticky top-0 z-40">
            <div className="bg-ink-surface text-ink-surface-foreground">
                <p className="px-4 py-2 text-center text-[11px] leading-snug font-medium tracking-wide uppercase sm:text-xs">
                    {storefrontAnnouncement}
                </p>
            </div>

            <header className="border-border/60 bg-background/85 supports-[backdrop-filter]:bg-background/75 border-b backdrop-blur-xl">
                <div className="container mx-auto flex h-14 items-center gap-4 px-4 sm:h-16 sm:px-6">
                    <Link
                        href={home()}
                        prefetch
                        className="flex shrink-0 items-center"
                    >
                        <img
                            src={brand.logoUrl}
                            alt={name}
                            className="h-8 w-auto object-contain sm:h-10"
                        />
                    </Link>

                    <nav className="ml-4 hidden flex-1 items-center justify-center gap-1 lg:flex xl:gap-2">
                        {items.map((item) => (
                            <NavLabel
                                key={`${item.label}-${item.categorySlug ?? item.route}`}
                                item={item}
                                url={url}
                            />
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-1 sm:gap-2">
                        <CartSheet
                            cart={
                                storefront?.cart ?? {
                                    itemCount: 0,
                                    items: [],
                                    subtotal: 0,
                                    formattedSubtotal: '$0.00',
                                }
                            }
                            active={cartActive}
                        />

                        {auth.user ? (
                            <NotificationsPopover
                                items={customerNotifications}
                                viewAllHref={notificationsIndex.url()}
                                getItemHref={(item) =>
                                    notificationsShow.url(item.id)
                                }
                            />
                        ) : null}

                        <Link
                            href={accountHref}
                            prefetch
                            className={cn(
                                'relative inline-flex size-10 items-center justify-center rounded-full transition-colors',
                                accountActive
                                    ? 'bg-muted text-foreground'
                                    : 'text-foreground hover:bg-muted/70',
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
                                <User
                                    className="size-5 md:size-[22px]"
                                    strokeWidth={1.75}
                                />
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
                            <SheetContent
                                side="right"
                                className="w-[min(100%,20rem)]"
                            >
                                <SheetHeader>
                                    <SheetTitle>Menu</SheetTitle>
                                </SheetHeader>
                                <nav className="mt-4 flex flex-col gap-1 px-3">
                                    {items.map((item) => (
                                        <NavLabel
                                            key={`${item.label}-${item.categorySlug ?? item.route}`}
                                            item={item}
                                            url={url}
                                            className="w-full rounded-xl px-3 py-2.5 text-base"
                                            onNavigate={() =>
                                                setMobileOpen(false)
                                            }
                                        />
                                    ))}
                                    <PwaInstallMenuItem
                                        className="text-foreground/70 rounded-xl px-3 py-2.5 text-base font-medium"
                                        onInstalledNavigate={() =>
                                            setMobileOpen(false)
                                        }
                                    />
                                </nav>
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>
            </header>
        </div>
    );
}
