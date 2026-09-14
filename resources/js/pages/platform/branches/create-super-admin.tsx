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
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

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

            <div className="mx-auto flex max-w-2xl flex-col gap-4 p-2">
                <div className="flex flex-col gap-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge>Step 2 of 2</Badge>
                        <Badge variant="outline">Super-admin</Badge>
                    </div>
                    <Heading
                        title="Add branch super-admin"
                        description={`Create the portal admin for ${branch.name}`}
                    />
                </div>

                <Alert>
                    <AlertTitle>
                        {branch.name}{' '}
                        <Badge variant="secondary" className="align-middle">
                            {branch.code}
                        </Badge>
                    </AlertTitle>
                    <AlertDescription>
                        This account gets the branch Super Admin role and can
                        manage the branch portal.
                    </AlertDescription>
                </Alert>

                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Account details</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-6">
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
                                <Label htmlFor="email">
                                    Email (optional)
                                </Label>
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
                        </CardContent>
                        <CardFooter className="flex items-center gap-3 border-t pt-6">
                            <Button type="submit" disabled={processing}>
                                {processing ? <Spinner /> : null}
                                Create super-admin
                            </Button>
                            <Button variant="secondary" asChild>
                                <Link
                                    href={BranchController.show.url(
                                        branch.slug,
                                    )}
                                >
                                    Skip for now
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>
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
