import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import InputError from '@/components/input-error';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(BranchController.storeSuperAdmin.url(branch.slug), {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`Super-admin · ${branch.name}`} />

            <div className="mx-auto max-w-2xl space-y-6 p-2">
                <Heading
                    title="Add branch super-admin"
                    description={`Step 2 of 2 — create the portal admin for ${branch.name} (${branch.code})`}
                />

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.email} />
                        </div>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(event) =>
                                    setData('password', event.target.value)
                                }
                                required
                                autoComplete="new-password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirm password
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(event) =>
                                    setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                                required
                                autoComplete="new-password"
                            />
                        </div>
                    </div>

                    <div className="flex items-center gap-3 border-t pt-5">
                        <Button type="submit" disabled={processing}>
                            Create super-admin
                        </Button>
                        <Button variant="secondary" asChild>
                            <Link
                                href={BranchController.show.url(branch.slug)}
                            >
                                Skip for now
                            </Link>
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
