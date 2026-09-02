import { Form, Head, Link } from '@inertiajs/react';
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
import { useState } from 'react';

type Branch = {
    id: number;
    name: string;
    slug: string;
};

type RoleOption = {
    value: string;
    label: string;
};

export default function BranchStaffCreate({
    branch,
    roles,
}: {
    branch: Branch;
    roles: RoleOption[];
}) {
    const [role, setRole] = useState(roles[0]?.value ?? '');
    const [isActive, setIsActive] = useState(true);

    return (
        <>
            <Head title="Add staff" />

            <div className="mx-auto max-w-2xl space-y-6 p-2">
                <Heading
                    title="Add staff"
                    description={`Create a staff member for ${branch.name}`}
                />

                <Form
                    {...StaffController.store.form(branch.slug)}
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
                                <Input id="name" name="name" required />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
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
                                    required
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
                                    Create staff
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

BranchStaffCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/branch/downtown/dashboard' },
        { title: 'Staff', href: '#' },
        { title: 'Create', href: '#' },
    ],
};
