import { Form, Head, setLayoutProps, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import PasskeyVerify from '@/components/passkey-verify';
import { Button } from '@/components/ui/button';
import Heading from '@/components/heading';
import BranchAccountPage from '@/layouts/settings/branch-account-page';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import { store } from '@/routes/password/confirm';
import { edit as editProfile } from '@/routes/profile';

function confirmBreadcrumbs(
    portal: string | null,
    branchSlug?: string,
) {
    if (portal === 'platform') {
        return [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Profile', href: editProfile() },
            { title: 'Confirm password', href: '#' },
        ];
    }

    if (portal === 'branch' && branchSlug) {
        return [
            { title: 'Dashboard', href: `/branch/${branchSlug}/dashboard` },
            { title: 'Profile', href: editProfile() },
            { title: 'Confirm password', href: '#' },
        ];
    }

    return [{ title: 'Confirm password', href: '#' }];
}

export default function ConfirmPassword() {
    const { portal, branch } = usePage().props;

    setLayoutProps({
        breadcrumbs: confirmBreadcrumbs(
            portal,
            branch && typeof branch === 'object' && 'slug' in branch
                ? String(branch.slug)
                : undefined,
        ),
    });

    const isStaffPortal = portal === 'branch' || portal === 'platform';
    const page = (
        <>
            <Head title="Confirm password" />

            {!isStaffPortal && <h1 className="sr-only">Confirm password</h1>}

            <section className="border-border/80 bg-card w-full max-w-lg space-y-6 rounded-2xl border p-5 shadow-sm sm:p-6">
                {!isStaffPortal && (
                    <Heading
                        variant="small"
                        title="Confirm password"
                        description="This is a secure area. Confirm your password before continuing."
                    />
                )}

                <PasskeyVerify
                    routes={{
                        options: confirmOptions(),
                        submit: confirmStore(),
                    }}
                    label="Confirm with passkey"
                    loadingLabel="Confirming..."
                    separator="Or confirm with password"
                />

                <Form
                    {...store.form()}
                    resetOnSuccess={['password']}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    className="mt-1 block w-full"
                                    placeholder="Password"
                                    autoComplete="current-password"
                                    autoFocus
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="confirm-password-button"
                                >
                                    {processing && <Spinner />}
                                    Confirm password
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </section>
        </>
    );

    if (isStaffPortal) {
        return (
            <BranchAccountPage
                title="Confirm password"
                description="This is a secure area. Confirm your password before continuing."
            >
                {page}
            </BranchAccountPage>
        );
    }

    return page;
}
