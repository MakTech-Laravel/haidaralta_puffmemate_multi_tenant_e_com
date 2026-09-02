import { Head, Link, router } from '@inertiajs/react';
import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type StaffRow = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    branch: { id: number; name: string; slug: string } | null;
    roles: string[];
};

type PaginatedStaff = {
    data: StaffRow[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

export default function PlatformBranchStaffIndex({
    staff,
    branches,
    filters,
}: {
    staff: PaginatedStaff;
    branches: { id: number; name: string; slug: string }[];
    filters: { branch_id: number | null };
}) {
    return (
        <>
            <Head title="Branch staff" />

            <div className="space-y-6 p-2">
                <Heading
                    title="Branch staff"
                    description="Platform oversight of staff across all branches"
                />

                <div className="max-w-xs">
                    <Select
                        value={
                            filters.branch_id
                                ? String(filters.branch_id)
                                : 'all'
                        }
                        onValueChange={(value) => {
                            router.get(
                                BranchStaffController.index.url({
                                    query:
                                        value === 'all'
                                            ? {}
                                            : { branch_id: value },
                                }),
                                {},
                                { preserveState: true },
                            );
                        }}
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Filter by branch" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All branches</SelectItem>
                            {branches.map((branch) => (
                                <SelectItem
                                    key={branch.id}
                                    value={String(branch.id)}
                                >
                                    {branch.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Branch</TableHead>
                                <TableHead>Roles</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {staff.data.map((member) => (
                                <TableRow key={member.id}>
                                    <TableCell className="font-medium">
                                        {member.name}
                                    </TableCell>
                                    <TableCell>{member.email}</TableCell>
                                    <TableCell>
                                        {member.branch?.name ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {member.roles.map((role) => (
                                                <Badge
                                                    key={role}
                                                    variant="secondary"
                                                >
                                                    {role}
                                                </Badge>
                                            ))}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {member.is_active
                                            ? 'Active'
                                            : 'Inactive'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link
                                                href={BranchStaffController.show.url(
                                                    member.id,
                                                )}
                                            >
                                                View
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <SimplePagination links={staff.links} />
            </div>
        </>
    );
}

PlatformBranchStaffIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branch staff', href: '/platform/branch-staff' },
    ],
};
