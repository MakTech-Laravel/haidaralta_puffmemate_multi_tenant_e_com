import { Link } from '@inertiajs/react';
import { Mail, Phone } from 'lucide-react';
import { useAppBrand } from '@/hooks/use-app-brand';
import { useContacts } from '@/hooks/use-contacts';
import {
    storefrontFooterBlurb,
    storefrontFooterBrand,
    storefrontFooterCustomer,
    storefrontFooterShop,
    type StorefrontChromeItem,
} from '@/lib/storefront-chrome';
import { cn } from '@/lib/utils';
import {
    about,
    contact,
    faqs,
    home,
    packsSaves,
    privacy,
    shop,
    wholesale,
} from '@/routes';
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
        case 'packs-saves':
            return packsSaves.url();
        case 'wholesale':
            return wholesale.url();
        case 'privacy':
            return privacy.url();
        case 'terms':
            return '/terms';
        default:
            return shop.url();
    }
}

function FooterLink({ item }: { item: StorefrontChromeItem }) {
    return (
        <Link
            href={resolveHref(item)}
            prefetch
            className="text-muted-foreground hover:text-foreground flex items-center py-1.5 text-sm leading-snug font-normal transition-colors"
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
        <div className="flex flex-col gap-2">
            <h3 className="text-foreground text-sm font-semibold">
                {title}
            </h3>
            <ul className="grid grid-cols-2 gap-x-3 sm:grid-cols-1">
                {items.map((item) => (
                    <li key={item.label}>
                        <FooterLink item={item} />
                    </li>
                ))}
            </ul>
        </div>
    );
}

export function StorefrontFooter({ className }: { className?: string }) {
    const brand = useAppBrand();
    const contacts = useContacts();

    return (
        <footer className={cn('border-border bg-muted border-t', className)}>
            <div className="container mx-auto flex flex-col gap-5 px-4 py-6 sm:px-6 sm:py-8 lg:grid lg:grid-cols-4 lg:gap-6 lg:py-10">
                <div className="flex flex-col gap-2">
                    <Link
                        href={home()}
                        prefetch
                        className="flex shrink-0 items-center"
                    >
                        <img
                            src={brand.logoUrl}
                            alt={brand.name}
                            className="h-8 w-auto max-w-full object-contain sm:h-11"
                        />
                    </Link>
                    <p className="text-muted-foreground max-w-sm text-sm leading-snug font-normal">
                        {brand.description || storefrontFooterBlurb}
                    </p>
                    <div className="text-muted-foreground mt-1 flex flex-col text-sm">
                        {contacts.phone ? (
                            <a
                                href={`tel:${contacts.phone.replace(/\s+/g, '')}`}
                                className="hover:text-foreground flex items-center gap-2 py-1.5 break-words transition-colors"
                            >
                                <Phone
                                    className="text-foreground size-4 shrink-0"
                                    aria-hidden
                                />
                                {contacts.phone}
                            </a>
                        ) : null}
                        {contacts.email ? (
                            <a
                                href={`mailto:${contacts.email}`}
                                className="hover:text-foreground flex items-center gap-2 py-1.5 break-all transition-colors"
                            >
                                <Mail
                                    className="text-foreground size-4 shrink-0"
                                    aria-hidden
                                />
                                {contacts.email}
                            </a>
                        ) : null}
                    </div>
                </div>

                <div className="border-border flex flex-col gap-4 border-t pt-4 sm:grid sm:grid-cols-3 sm:gap-6 sm:border-0 sm:pt-0 lg:contents">
                    <FooterColumn title="Shop" items={storefrontFooterShop} />
                    <FooterColumn
                        title="Customer"
                        items={storefrontFooterCustomer}
                    />
                    <FooterColumn
                        title={brand.shortName}
                        items={storefrontFooterBrand}
                    />
                </div>
            </div>
        </footer>
    );
}
