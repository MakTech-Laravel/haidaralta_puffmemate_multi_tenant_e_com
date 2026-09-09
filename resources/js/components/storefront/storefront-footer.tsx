import { Link, usePage } from '@inertiajs/react';
import { about, contact, home, shop } from '@/routes';

const links = [
    { label: 'Home', href: home },
    { label: 'Shop', href: shop },
    { label: 'About', href: about },
    { label: 'Contact', href: contact },
] as const;

export function StorefrontFooter() {
    const { name } = usePage().props;

    return (
        <footer className="border-border bg-muted/40 mt-auto border-t">
            <div className="mx-auto flex max-w-5xl flex-col gap-4 px-4 py-8 sm:px-6">
                <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                    <span className="font-semibold">{name}</span>
                    {links.map((item) => (
                        <Link
                            key={item.label}
                            href={item.href()}
                            className="text-muted-foreground hover:text-foreground"
                        >
                            {item.label}
                        </Link>
                    ))}
                </div>
                <p className="text-muted-foreground text-xs">
                    18+ only. Tobacco products. Must be of legal smoking age.
                </p>
                <p className="text-muted-foreground text-xs">
                    © {new Date().getFullYear()} {name}. Placeholder storefront.
                </p>
            </div>
        </footer>
    );
}
