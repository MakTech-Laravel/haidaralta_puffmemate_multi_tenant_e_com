import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import StaffController from '@/actions/App/Http/Controllers/Platform/StaffController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

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

export default function PlatformStaffShow({
    staffMember,
}: {
    staffMember: StaffMember;
}) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <Head title={staffMember.name} />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={staffMember.name}
                        description={staffMember.phone}
                    />
                    <div className="flex gap-2">
                        <Button variant="secondary" asChild>
                            <Link href={StaffController.index.url()}>Back</Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={StaffController.edit.url(staffMember.id)}
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

                <section className="space-y-2">
                    <h2 className="text-sm font-medium">Status</h2>
                    <p className="text-sm">
                        {staffMember.is_active ? 'Active' : 'Inactive'}
                    </p>
                </section>

                <section className="space-y-2">
                    <h2 className="text-sm font-medium">Roles</h2>
                    <div className="flex flex-wrap gap-2">
                        {staffMember.roles.map((role) => (
                            <Badge key={role} variant="secondary">
                                {role}
                            </Badge>
                        ))}
                    </div>
                </section>

                <section className="space-y-2">
                    <h2 className="text-sm font-medium">
                        Effective permissions
                    </h2>
                    {staffMember.is_super_admin ? (
                        <p className="text-muted-foreground text-sm">
                            Super-admin bypasses permission checks via Gate.
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
                    router.delete(StaffController.destroy.url(staffMember.id), {
                        preserveScroll: true,
                        onFinish: () => setProcessing(false),
                    });
                }}
            />
        </>
    );
}

PlatformStaffShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Staff', href: '/platform/staff' },
        { title: 'Show', href: '#' },
    ],
};
