import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type Branch = {
    id: number;
    name: string;
    slug: string;
    code: string;
};

export default function PlatformBranchesCreateSuperAdmin({
    branch,
}: {
    branch: Branch;
}) {
    const {
        data,
        setData,
        submit,
        processing,
        errors,
        validate,
        setValidationTimeout,
    } = useForm(BranchController.storeSuperAdmin(branch.slug), {
        name: '',
        phone: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();
        submit({ preserveScroll: true });
    };

    return (
        <>
            <Head title={`Super-admin · ${branch.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add branch super-admin"
                    description={`Create the portal admin for ${branch.name}`}
                />

                <Alert>
                    <AlertTitle>
                        {branch.name}{' '}
                        <Badge variant="secondary" className="align-middle">
                            {branch.code}
                        </Badge>
                    </AlertTitle>
                    <AlertDescription>
                        This branch is missing a super-admin. Create one so
                        staff can manage the branch portal. Additional admins
                        can be promoted later from staff management.
                    </AlertDescription>
                </Alert>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <section
                        className={cn(
                            'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
                            'sm:p-6',
                        )}
                    >
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Account details
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                This account gets the branch Super Admin role.
                            </p>
                        </div>

                        <div className="flex flex-col gap-5">
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(event) =>
                                            setData('name', event.target.value)
                                        }
                                        required
                                        placeholder="Full name"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="phone">Phone number</Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={data.phone}
                                        onChange={(event) =>
                                            setData('phone', event.target.value)
                                        }
                                        onBlur={() => validate('phone')}
                                        required
                                        placeholder="04XX XXX XXX"
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="email">Email (optional)</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) =>
                                        setData('email', event.target.value)
                                    }
                                    onBlur={() => {
                                        if (data.email.trim() !== '') {
                                            validate('email');
                                        }
                                    }}
                                    placeholder="admin@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="password">Password</Label>
                                    <PasswordInput
                                        id="password"
                                        value={data.password}
                                        onChange={(event) =>
                                            setData(
                                                'password',
                                                event.target.value,
                                            )
                                        }
                                        required
                                        autoComplete="new-password"
                                        placeholder="Enter password"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirm password
                                    </Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        value={data.password_confirmation}
                                        onChange={(event) =>
                                            setData(
                                                'password_confirmation',
                                                event.target.value,
                                            )
                                        }
                                        required
                                        autoComplete="new-password"
                                        placeholder="Confirm password"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <div className="bg-background/95 supports-[backdrop-filter]:bg-background/80 sticky bottom-0 z-10 -mx-2 flex flex-wrap items-center justify-end gap-3 border-t px-2 py-4 backdrop-blur">
                        <Button variant="secondary" asChild>
                            <Link href={BranchController.show.url(branch.slug)}>
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <Spinner /> : null}
                            Create super-admin
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PlatformBranchesCreateSuperAdmin.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
        { title: 'Super-admin', href: '#' },
    ],
};
