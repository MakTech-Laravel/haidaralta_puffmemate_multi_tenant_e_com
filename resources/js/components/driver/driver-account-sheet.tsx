import { Link, router, usePage } from '@inertiajs/react';
import {
    CircleHelp,
    CircleUser,
    LogOut,
    Phone,
    Settings,
    Shield,
    UserRound,
} from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useInitials } from '@/hooks/use-initials';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { index as accountIndex } from '@/routes/driver/account';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { User } from '@/types';

function demoOnly(message: string): void {
    toast.message(message, {
        description: 'Placeholder — this control is not live yet.',
    });
}

function AccountSheetItem({
    href,
    icon: Icon,
    children,
    external = false,
    onNavigate,
}: {
    href: string;
    icon: typeof UserRound;
    children: ReactNode;
    external?: boolean;
    onNavigate?: () => void;
}) {
    const className = cn(
        'hover:bg-accent flex min-h-12 items-center gap-3 rounded-xl px-3 text-base font-medium transition-colors',
    );

    if (external) {
        return (
            <a href={href} className={className} onClick={onNavigate}>
                <Icon className="size-5 shrink-0" aria-hidden />
                {children}
            </a>
        );
    }

    return (
        <Link href={href} prefetch className={className}>
            <Icon className="size-5 shrink-0" aria-hidden />
            {children}
        </Link>
    );
}

type DriverAccountSheetProps = {
    user: User | null;
};

export function DriverAccountSheet({ user }: DriverAccountSheetProps) {
    const [open, setOpen] = useState(false);
    const getInitials = useInitials();
    const cleanup = useMobileNavigation();
    const { url, props } = usePage();
    const supportPhone = props.driverSupport?.supportPhone ?? null;
    const branchPhone = props.driverSupport?.branchPhone ?? null;

    useEffect(() => {
        setOpen(false);
    }, [url]);

    const closeSheet = (): void => {
        setOpen(false);
    };

    const handleLogout = (): void => {
        cleanup();
        closeSheet();
        router.flushAll();
    };

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-11 shrink-0 rounded-full"
                    aria-label="Account menu"
                    data-test="driver-header-account"
                >
                    {user?.avatar ? (
                        <Avatar className="size-8">
                            <AvatarImage src={user.avatar} alt={user.name} />
                            <AvatarFallback className="text-xs">
                                {getInitials(user.name)}
                            </AvatarFallback>
                        </Avatar>
                    ) : (
                        <UserRound className="size-6" strokeWidth={1.75} />
                    )}
                </Button>
            </SheetTrigger>
            <SheetContent
                side="right"
                className="gap-0 p-0 sm:max-w-sm"
                data-test="driver-account-sheet"
            >
                <SheetHeader className="border-border gap-3 border-b p-4 pr-12 text-left">
                    <div className="flex items-center gap-3">
                        <Avatar className="size-12">
                            {user?.avatar ? (
                                <AvatarImage
                                    src={user.avatar}
                                    alt={user.name}
                                />
                            ) : null}
                            <AvatarFallback>
                                {getInitials(user?.name ?? 'Driver')}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0 space-y-0.5">
                            <SheetTitle className="truncate text-lg">
                                {user?.name ?? 'Driver'}
                            </SheetTitle>
                            <SheetDescription className="truncate">
                                {user?.phone ?? 'Signed in'}
                            </SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <div className="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto px-3 py-4">
                    <section className="space-y-1">
                        <p className="text-muted-foreground px-3 text-xs font-medium tracking-wide uppercase">
                            Account
                        </p>
                        <AccountSheetItem
                            href={accountIndex.url()}
                            icon={CircleUser}
                        >
                            Account
                        </AccountSheetItem>
                        <AccountSheetItem
                            href={editProfile.url()}
                            icon={UserRound}
                        >
                            Profile
                        </AccountSheetItem>
                        <AccountSheetItem
                            href={editSecurity.url()}
                            icon={Settings}
                        >
                            Settings
                        </AccountSheetItem>
                    </section>

                    <Separator />

                    <section className="space-y-1">
                        <p className="text-muted-foreground px-3 text-xs font-medium tracking-wide uppercase">
                            Quick actions
                        </p>
                        {supportPhone ? (
                            <AccountSheetItem
                                href={`tel:${supportPhone}`}
                                icon={CircleHelp}
                                external
                                onNavigate={closeSheet}
                            >
                                Call support
                            </AccountSheetItem>
                        ) : null}
                        {branchPhone ? (
                            <AccountSheetItem
                                href={`tel:${branchPhone}`}
                                icon={Phone}
                                external
                                onNavigate={closeSheet}
                            >
                                Call branch
                            </AccountSheetItem>
                        ) : null}
                        <button
                            type="button"
                            className="hover:bg-accent flex min-h-12 w-full items-center gap-3 rounded-xl px-3 text-left text-base font-medium transition-colors"
                            onClick={() => {
                                closeSheet();
                                demoOnly(
                                    'Safety check-in recorded for this demo',
                                );
                            }}
                        >
                            <Shield className="size-5 shrink-0" aria-hidden />
                            Safety check-in
                        </button>
                    </section>
                </div>

                <SheetFooter className="border-border border-t p-4">
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        className="h-12 w-full"
                        asChild
                    >
                        <Link
                            href={logout()}
                            as="button"
                            onClick={handleLogout}
                            data-test="driver-header-logout"
                        >
                            <LogOut data-icon="inline-start" />
                            Log out
                        </Link>
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
