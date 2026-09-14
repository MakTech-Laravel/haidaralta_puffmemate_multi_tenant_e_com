import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import BranchScopedStaffController from '@/actions/App/Http/Controllers/Platform/BranchScopedStaffController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Branch = {
    id: number;
    name: string;
    slug: string;
    code: string;
};

type StaffMember = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    roles: string[];
    is_super_admin: boolean;
    effective_permissions: string[];
};

export default function PlatformBranchScopedStaffShow({
    branch,
    staffMember,
}: {
    branch: Branch;
    staffMember: StaffMember;
}) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <Head title={staffMember.name} />

            <div className="flex flex-col gap-4 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={staffMember.name}
                        description={`${staffMember.phone} · ${branch.name}`}
                    />
                    <div className="flex gap-2">
                        <Button variant="secondary" asChild>
                            <Link
                                href={BranchScopedStaffController.index.url(
                                    branch.slug,
                                )}
                            >
                                Back
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={BranchScopedStaffController.edit.url([
                                    branch.slug,
                                    staffMember.id,
                                ])}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => setConfirmOpen(true)}
                        >
                            Delete
                        </Button>
                    </div>
                </div>

                <section className="flex flex-col gap-2">
                    <h2 className="text-sm font-medium">Status</h2>
                    <p className="text-sm">
                        {staffMember.is_active ? 'Active' : 'Inactive'}
                    </p>
                </section>

                <section className="flex flex-col gap-2">
                    <h2 className="text-sm font-medium">Roles</h2>
                    <div className="flex flex-wrap gap-2">
                        {staffMember.roles.map((role) => (
                            <Badge key={role} variant="secondary">
                                {role}
                            </Badge>
                        ))}
                    </div>
                </section>

                <section className="flex flex-col gap-2">
                    <h2 className="text-sm font-medium">
                        Effective permissions
                    </h2>
                    {staffMember.is_super_admin ? (
                        <p className="text-muted-foreground text-sm">
                            Branch super-admin bypasses permission checks via
                            Gate within this branch team.
                        </p>
                    ) : null}
                    <div className="flex flex-wrap gap-2">
                        {staffMember.effective_permissions.map((permission) => (
                            <Badge key={permission} variant="outline">
                                {permission}
                            </Badge>
                        ))}
                    </div>
                </section>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                description={`Delete “${staffMember.name}” permanently?`}
                processing={processing}
                onConfirm={() => {
                    setProcessing(true);
                    router.delete(
                        BranchScopedStaffController.destroy.url([
                            branch.slug,
                            staffMember.id,
                        ]),
                        {
                            preserveScroll: true,
                            onFinish: () => setProcessing(false),
                        },
                    );
                }}
            />
        </>
    );
}

PlatformBranchScopedStaffShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
        { title: 'Staff', href: '#' },
        { title: 'Show', href: '#' },
    ],
};
