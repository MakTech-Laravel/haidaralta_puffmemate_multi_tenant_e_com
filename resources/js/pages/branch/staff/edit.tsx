import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import StaffController from '@/actions/App/Http/Controllers/Branch/StaffController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Branch = {
    id: number;
    name: string;
    slug: string;
};

type RoleOption = {
    value: string;
    label: string;
};

type StaffMember = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    role: string | null;
};

export default function BranchStaffEdit({
    branch,
    roles,
    staffMember,
}: {
    branch: Branch;
    roles: RoleOption[];
    staffMember: StaffMember;
}) {
    const [role, setRole] = useState(staffMember.role ?? roles[0]?.value ?? '');
    const [isActive, setIsActive] = useState(staffMember.is_active);

    return (
        <>
            <Head title={`Edit ${staffMember.name}`} />

            <div className="mx-auto max-w-2xl space-y-6 p-2">
                <Heading
                    title={`Edit ${staffMember.name}`}
                    description={`Update staff for ${branch.name}`}
                />

                <Form
                    {...StaffController.update.form({
                        branch: branch.slug,
                        user: staffMember.id,
                    })}
                    className="space-y-6"
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="role" value={role} />
                            <input
                                type="hidden"
                                name="is_active"
                                value={isActive ? '1' : '0'}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={staffMember.name}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={staffMember.email}
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    Password (optional)
                                </Label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label>Role</Label>
                                <Select value={role} onValueChange={setRole}>
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Select role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.role} />
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="is_active"
                                    checked={isActive}
                                    onCheckedChange={(value) =>
                                        setIsActive(value === true)
                                    }
                                />
                                <Label htmlFor="is_active" className="font-normal">
                                    Active
                                </Label>
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Save changes
                                </Button>
                                <Button variant="secondary" asChild>
                                    <Link
                                        href={StaffController.index.url(
                                            branch.slug,
                                        )}
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

BranchStaffEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/branch/downtown/dashboard' },
        { title: 'Staff', href: '#' },
        { title: 'Edit', href: '#' },
    ],
};
