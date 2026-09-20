import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, LogOut, Settings, UserRound } from 'lucide-react';
import Heading from '@/components/heading';
import { UserInfo } from '@/components/user-info';
import { Separator } from '@/components/ui/separator';
import { logout } from '@/routes';
import { index as activityIndex } from '@/routes/driver/activity';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { Auth } from '@/types';

export default function DriverAccountIndex() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;

    return (
        <>
            <Head title="Account" />

            <div className="flex flex-col gap-6">
                <Heading
                    title="Account"
                    description="Profile, security, and sign out"
                />

                {user ? (
                    <div className="border-border flex items-center gap-3 rounded-xl border p-4">
                        <UserInfo user={user} showEmail />
                    </div>
                ) : null}

                <div className="flex flex-col">
                    <Link
                        href={editProfile.url()}
                        prefetch
                        className="border-border hover:text-primary flex items-center gap-3 border-b py-3.5 text-base font-medium first:border-t"
                    >
                        <UserRound className="size-5 shrink-0" aria-hidden />
                        Profile
                    </Link>
                    <Link
                        href={editSecurity.url()}
                        prefetch
                        className="border-border hover:text-primary flex items-center gap-3 border-b py-3.5 text-base font-medium"
                    >
                        <Settings className="size-5 shrink-0" aria-hidden />
                        Security
                    </Link>
                    <Link
                        href={activityIndex.url()}
                        prefetch
                        className="border-border hover:text-primary flex items-center gap-3 border-b py-3.5 text-base font-medium"
                    >
                        <Activity className="size-5 shrink-0" aria-hidden />
                        My activity
                    </Link>
                    <Separator className="my-1" />
                    <Link
                        href={logout()}
                        as="button"
                        className="border-border text-foreground hover:text-primary flex cursor-pointer items-center gap-3 py-3.5 text-left text-base font-medium"
                        data-test="driver-account-logout"
                    >
                        <LogOut className="size-5 shrink-0" aria-hidden />
                        Log out
                    </Link>
                </div>
            </div>
        </>
    );
}

DriverAccountIndex.layout = {
    breadcrumbs: [{ title: 'Account', href: '/driver/account' }],
};
