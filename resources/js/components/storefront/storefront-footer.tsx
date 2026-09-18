import { Link } from '@inertiajs/react';
import {
    storefrontFooterBlurb,
    storefrontFooterBrand,
    storefrontFooterCustomer,
    storefrontFooterShop,
    storefrontLogoSrc,
    type StorefrontChromeItem,
} from '@/lib/storefront-chrome';
import { about, contact, faqs, home, privacy, shop, wholesale } from '@/routes';
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
        case 'privacy':
            return privacy.url();
        default:
            return shop.url();
    }
}

function FooterLink({ item }: { item: StorefrontChromeItem }) {
    return (
        <Link
            href={resolveHref(item)}
            prefetch
            className="text-muted-foreground hover:text-foreground text-sm leading-relaxed font-normal transition-colors"
        >
            {item.label}
        </Link>
    );
}

function FooterColumn({
    title,
    items,
}: {
    title: string;
    items: StorefrontChromeItem[];
}) {
    return (
        <div className="flex flex-col gap-3">
            <h3 className="text-foreground text-base font-extrabold">
                {title}
            </h3>
            <ul className="flex flex-col gap-2">
                {items.map((item) => (
                    <li key={item.label}>
                        <FooterLink item={item} />
                    </li>
                ))}
            </ul>
        </div>
    );
}

export function StorefrontFooter() {
    return (
        <footer className="border-border bg-muted mt-auto border-t">
            <div className="container mx-auto grid gap-10 px-4 py-12 sm:px-6 sm:py-14 md:grid-cols-2 lg:grid-cols-4 lg:gap-8">
                <div className="flex flex-col gap-3 md:col-span-2 lg:col-span-1">
                    <Link
                        href={home()}
                        prefetch
                        className="flex shrink-0 items-center"
                    >
                        <img
                            src={storefrontLogoSrc}
                            alt="PuffMeMate"
                            className="h-9 w-auto object-contain sm:h-11"
                        />
                    </Link>
                    <p className="text-muted-foreground max-w-xs text-sm leading-relaxed font-normal">
                        {storefrontFooterBlurb}
                    </p>
                </div>

                <FooterColumn title="Shop" items={storefrontFooterShop} />
                <FooterColumn
                    title="Customer"
                    items={storefrontFooterCustomer}
                />
                <FooterColumn
                    title="PuffMeMate"
                    items={storefrontFooterBrand}
                />
            </div>
        </footer>
    );
}
