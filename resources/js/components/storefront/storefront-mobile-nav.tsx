import { Link, usePage } from '@inertiajs/react';
import { House, ShoppingBag, Store, User, type LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { cart, dashboard, home, login, shop } from '@/routes';
import type { StorefrontCart } from '@/types/storefront';

export const storefrontMobileNavPadding =
    'pb-[calc(4.75rem+env(safe-area-inset-bottom))] lg:pb-0';

export function isStorefrontCheckout(url: string): boolean {
    return url === '/checkout' || url.startsWith('/checkout?');
}

function isShopTab(url: string): boolean {
    return (
        url === '/shop' || url.startsWith('/shop?') || url.startsWith('/shop/')
    );
}

function isCartTab(url: string): boolean {
    return url === '/cart' || url.startsWith('/cart?');
}

function isAccountTab(url: string): boolean {
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
        url === '/wholesale-requests' ||
        url.startsWith('/wholesale-requests?') ||
        url.startsWith('/wholesale-requests/')
    );
}

type Tab = {
    label: string;
    href: string;
    icon: LucideIcon;
    active: boolean;
    badge?: number;
};

export function StorefrontMobileNav() {
    const page = usePage();
    const url = page.url;
    const { auth, storefront } = page.props as {
        auth: { user: unknown };
        storefront?: { cart?: StorefrontCart };
    };
    const itemCount = storefront?.cart?.itemCount ?? 0;

    const tabs: Tab[] = [
        {
            label: 'Home',
            href: home.url(),
            icon: House,
            active: url === '/' || url.startsWith('/?'),
        },
        {
            label: 'Shop',
            href: shop.url(),
            icon: Store,
            active: isShopTab(url),
        },
        {
            label: 'Cart',
            href: cart.url(),
            icon: ShoppingBag,
            active: isCartTab(url),
            badge: itemCount > 0 ? itemCount : undefined,
        },
        {
            label: 'Account',
            href: auth.user ? dashboard.url() : login.url(),
            icon: User,
            active: isAccountTab(url),
        },
    ];

    return (
        <nav
            aria-label="Storefront"
            className="fixed inset-x-3 bottom-[calc(0.75rem+env(safe-area-inset-bottom))] z-40 lg:hidden"
        >
            <ul className="border-border bg-background/80 supports-[backdrop-filter]:bg-background/70 mx-auto flex h-14 max-w-md items-center rounded-full border px-1.5 shadow-xl backdrop-blur-xl">
                {tabs.map((tab) => {
                    const Icon = tab.icon;

                    return (
                        <li key={tab.label} className="flex h-full flex-1 items-center">
                            <Link
                                href={tab.href}
                                prefetch
                                aria-label={tab.label}
                                aria-current={tab.active ? 'page' : undefined}
                                className={cn(
                                    'mx-0.5 flex h-12 w-full flex-col items-center justify-center gap-0.5 rounded-full text-[11px] leading-none font-medium',
                                    tab.active
                                        ? 'bg-muted text-primary'
                                        : 'text-foreground',
                                )}
                            >
                                <span className="relative">
                                    <Icon
                                        className="size-5"
                                        strokeWidth={1.75}
                                        aria-hidden
                                    />
                                    {tab.badge ? (
                                        <span className="bg-primary text-primary-foreground absolute -top-1.5 -right-2.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full px-1 text-[10px] font-semibold">
                                            {tab.badge}
                                        </span>
                                    ) : null}
                                </span>
                                {tab.label}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
