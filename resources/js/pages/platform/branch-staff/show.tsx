import { Head, Link } from '@inertiajs/react';
import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type StaffMember = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    branch: { id: number; name: string; slug: string } | null;
    roles: string[];
};

export default function PlatformBranchStaffShow({
    staffMember,
}: {
    staffMember: StaffMember;
}) {
    return (
        <>
            <Head title={staffMember.name} />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={staffMember.name}
                        description={staffMember.email}
                    />
                    <Button variant="secondary" asChild>
                        <Link href={BranchStaffController.index.url()}>
                            Back
                        </Link>
                    </Button>
                </div>

                <dl className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt className="text-muted-foreground text-sm">
                            Branch
                        </dt>
                        <dd className="font-medium">
                            {staffMember.branch?.name ?? '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground text-sm">
                            Status
                        </dt>
                        <dd className="font-medium">
                            {staffMember.is_active ? 'Active' : 'Inactive'}
                        </dd>
                    </div>
                    <div className="sm:col-span-2">
                        <dt className="text-muted-foreground mb-2 text-sm">
                            Roles
                        </dt>
                        <dd className="flex flex-wrap gap-2">
                            {staffMember.roles.length === 0 ? (
                                <span className="text-muted-foreground text-sm">
                                    No roles assigned
                                </span>
                            ) : (
                                staffMember.roles.map((role) => (
                                    <Badge key={role} variant="secondary">
                                        {role}
                                    </Badge>
                                ))
                            )}
                        </dd>
                    </div>
                </dl>
            </div>
        </>
    );
}

PlatformBranchStaffShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branch staff', href: '/platform/branch-staff' },
        { title: 'Show', href: '#' },
    ],
};
