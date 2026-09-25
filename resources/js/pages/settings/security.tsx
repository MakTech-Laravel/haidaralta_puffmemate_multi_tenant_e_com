import { Head, setLayoutProps, usePage } from '@inertiajs/react';
import type { Props as ManagePasskeysProps } from '@/components/manage-passkeys';
import ManagePasskeys from '@/components/manage-passkeys';
import type { Props as ManageTwoFactorProps } from '@/components/manage-two-factor';
import ManageTwoFactor from '@/components/manage-two-factor';
import ChangePasswordCard from '@/components/settings/change-password-card';
import { edit } from '@/routes/security';

// oxfmt-ignore
type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

function securityBreadcrumbs(
    portal: string | null,
    branchSlug?: string,
) {
    if (portal === 'platform') {
        return [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Security', href: edit() },
        ];
    }

    if (portal === 'branch' && branchSlug) {
        return [
            { title: 'Dashboard', href: `/branch/${branchSlug}/dashboard` },
            { title: 'Security', href: edit() },
        ];
    }

    return [{ title: 'Security', href: edit() }];
}

export default function Security(props: Props) {
    const { portal, branch } = usePage().props;

    setLayoutProps({
        breadcrumbs: securityBreadcrumbs(
            portal,
            branch && typeof branch === 'object' && 'slug' in branch
                ? String(branch.slug)
                : undefined,
        ),
    });

    return (
        <>
            <Head title="Security settings" />

            <h1 className="sr-only">Security settings</h1>

            <ChangePasswordCard passwordRules={props.passwordRules} />

            <ManageTwoFactor
                canManageTwoFactor={props.canManageTwoFactor}
                requiresConfirmation={props.requiresConfirmation}
                twoFactorEnabled={props.twoFactorEnabled}
            />

            <ManagePasskeys
                canManagePasskeys={props.canManagePasskeys}
                passkeys={props.passkeys}
            />
        </>
    );
}
