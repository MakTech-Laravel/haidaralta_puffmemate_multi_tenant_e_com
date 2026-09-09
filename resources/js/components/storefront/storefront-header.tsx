import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { cn, toUrl } from '@/lib/utils';
import { about, contact, dashboard, home, login, register, shop } from '@/routes';

const nav = [
    { label: 'Home', href: home },
    { label: 'Shop', href: shop },
    { label: 'About', href: about },
    { label: 'Contact', href: contact },
] as const;

export function StorefrontHeader() {
    const page = usePage();
    const { auth, name } = page.props;
    const url = page.url;

    return (
        <header className="border-border bg-background/95 sticky top-0 z-40 border-b backdrop-blur">
            <div className="mx-auto flex h-14 max-w-5xl items-center gap-4 px-4 sm:px-6">
                <Link
                    href={home()}
                    className="flex shrink-0 items-center gap-2 font-semibold"
                >
                    <span className="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-md">
                        <AppLogoIcon className="size-4 fill-current" />
                    </span>
                    <span className="hidden sm:inline">{name}</span>
                </Link>

                <nav className="hidden items-center gap-1 md:flex">
                    {nav.map((item) => {
                        const href = toUrl(item.href());
                        const active =
                            href === '/'
                                ? url === '/'
                                : url.startsWith(href);

                        return (
                            <Link
                                key={item.label}
                                href={item.href()}
                                prefetch
                                className={cn(
                                    'rounded-md px-3 py-1.5 text-sm transition-colors',
                                    active
                                        ? 'bg-accent text-accent-foreground'
                                        : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                                )}
                            >
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>

                <div className="ml-auto flex items-center gap-2">
                    <span className="bg-muted text-muted-foreground hidden rounded-md px-2.5 py-1 text-xs sm:inline">
                        Pickup: Downtown
                    </span>

                    {auth.user ? (
                        <Button asChild size="sm">
                            <Link href={dashboard()}>Dashboard</Link>
                        </Button>
                    ) : (
                        <>
                            <Button asChild variant="ghost" size="sm">
                                <Link href={login()}>Log in</Link>
                            </Button>
                            <Button asChild size="sm">
                                <Link href={register()}>Register</Link>
                            </Button>
                        </>
                    )}
                </div>
            </div>
        </header>
    );
}
