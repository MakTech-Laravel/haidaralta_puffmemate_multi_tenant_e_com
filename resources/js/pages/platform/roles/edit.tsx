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

type Role = {
    id: number;
    name: string;
    is_super_admin: boolean;
    permissions: string[];
};

export default function PlatformRolesEdit({
    role,
    permissionGroups,
}: {
    role: Role;
    permissionGroups: PermissionGroup[];
}) {
    const [selected, setSelected] = useState<string[]>(role.permissions);

    return (
        <>
            <Head title={`Edit ${role.name}`} />

            <div className="mx-auto max-w-3xl space-y-6 p-2">
                <Heading
                    title={`Edit ${role.name}`}
                    description={
                        role.is_super_admin
                            ? 'Super-admin cannot be renamed. Permissions are granted via gate bypass.'
                            : 'Update the role name and assigned permissions'
                    }
                />

                <Form
                    {...RoleController.update.form(role.id)}
                    className="space-y-6"
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={role.name}
                                    required
                                    disabled={role.is_super_admin}
                                    readOnly={role.is_super_admin}
                                />
                                {role.is_super_admin && (
                                    <input
                                        type="hidden"
                                        name="name"
                                        value={role.name}
                                    />
                                )}
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label>Permissions</Label>
                                <PermissionSelector
                                    groups={permissionGroups}
                                    selected={selected}
                                    onChange={setSelected}
                                    disabled={role.is_super_admin}
                                />
                                <InputError message={errors.permissions} />
                            </div>

                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    disabled={processing || role.is_super_admin}
                                >
                                    Save changes
                                </Button>
                                <Button variant="secondary" asChild>
                                    <Link
                                        href={RoleController.show.url(role.id)}
                                    >
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

PlatformRolesEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Roles', href: '/platform/roles' },
        { title: 'Edit', href: '#' },
    ],
};
