import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const settingsTabItems: NavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl, isCurrentUrl } = useCurrentUrl();
    const { portal } = usePage().props;
    const profileHref = toUrl(edit());
    const items = settingsTabItems.filter((item) => {
        if (portal !== 'driver') {
            return true;
        }

        return toUrl(item.href) !== profileHref;
    });

    const isSecuritySection =
        isCurrentOrParentUrl(editSecurity()) ||
        isCurrentUrl('/user/confirm-password');

    return (
        <div className="flex w-full flex-col gap-4 p-2 md:p-4">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="space-y-6">
                <nav
                    className="border-border flex gap-1 border-b"
                    aria-label="Settings"
                >
                    {items.map((item, index) => {
                        const href = toUrl(item.href);
                        const active =
                            href === profileHref
                                ? isCurrentOrParentUrl(item.href)
                                : isSecuritySection;

                        return (
                            <Link
                                key={`${href}-${index}`}
                                href={item.href}
                                prefetch
                                className={cn(
                                    'text-muted-foreground hover:text-foreground -mb-px border-b-2 border-transparent px-3 py-2 text-sm font-medium transition-colors',
                                    active &&
                                        'border-foreground text-foreground',
                                )}
                            >
                                {item.title}
                            </Link>
                        );
                    })}
                </nav>

                <div className="min-w-0 space-y-6">{children}</div>
            </div>
        </div>
    );
}
