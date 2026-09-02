import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Platform/RoleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    PermissionSelector,
    type PermissionGroup,
} from '@/components/permission-selector';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function PlatformRolesCreate({
    permissionGroups,
}: {
    permissionGroups: PermissionGroup[];
}) {
    const [selected, setSelected] = useState<string[]>([]);

    return (
        <>
            <Head title="Create role" />

            <div className="mx-auto max-w-3xl space-y-6 p-2">
                <Heading
                    title="Create role"
                    description="Define a platform role and assign permissions"
                />

                <Form
                    {...RoleController.store.form()}
                    className="space-y-6"
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" name="name" required />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label>Permissions</Label>
                                <PermissionSelector
                                    groups={permissionGroups}
                                    selected={selected}
                                    onChange={setSelected}
                                />
                                <InputError message={errors.permissions} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Create role
                                </Button>
                                <Button variant="secondary" asChild>
                                    <Link href={RoleController.index.url()}>
                                        Cancel
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

PlatformRolesCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Roles', href: '/platform/roles' },
        { title: 'Create', href: '/platform/roles/create' },
    ],
};
