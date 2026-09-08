import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermission } from '@/hooks/use-permissions';

type SuperAdmin = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
};

type Branch = {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
    icon_url: string | null;
    is_active: boolean;
    users_count: number;
    address: string | null;
    latitude: string | number | null;
    longitude: string | number | null;
    super_admins: SuperAdmin[];
};

export default function PlatformBranchesShow({ branch }: { branch: Branch }) {
    const { can } = usePermission();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <Head title={branch.name} />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={branch.name}
                        description={`/${branch.slug}`}
                    />
                    <div className="flex gap-2">
                        <Button variant="secondary" asChild>
                            <Link href={BranchController.index.url()}>
                                Back
                            </Link>
                        </Button>
                        {can('edit-branches') ? (
                            <Button asChild>
                                <Link
                                    href={BranchController.edit.url(
                                        branch.slug,
                                    )}
                                >
                                    Edit
                                </Link>
                            </Button>
                        ) : null}
                        {can('delete-branches') ? (
                            <Button
                                variant="destructive"
                                onClick={() => setConfirmOpen(true)}
                            >
                                Delete
                            </Button>
                        ) : null}
                    </div>
                </div>

                <section className="space-y-2">
                    <h2 className="text-sm font-medium">Details</h2>
                    <dl className="grid gap-2 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted-foreground">Image</dt>
                            <dd className="mt-1">
                                {branch.icon_url ? (
                                    <img
                                        src={branch.icon_url}
                                        alt={branch.name}
                                        className="size-16 rounded-lg object-cover"
                                    />
                                ) : (
                                    '—'
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Status</dt>
                            <dd>
                                {branch.is_active ? 'Active' : 'Inactive'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Address</dt>
                            <dd>{branch.address ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Staff</dt>
                            <dd>{branch.users_count}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Latitude</dt>
                            <dd>{branch.latitude ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Longitude</dt>
                            <dd>{branch.longitude ?? '—'}</dd>
                        </div>
                    </dl>
                </section>

                <section className="space-y-2">
                    <h2 className="text-sm font-medium">Super-admins</h2>
                    {branch.super_admins.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No super-admins found for this branch.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {branch.super_admins.map((admin) => (
                                <Badge key={admin.id} variant="secondary">
                                    {admin.name} ({admin.email})
                                    {!admin.is_active ? ' · inactive' : ''}
                                </Badge>
                            ))}
                        </div>
                    )}
                </section>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                description={`Delete “${branch.name}” permanently? Branches with staff cannot be deleted.`}
                processing={processing}
                onConfirm={() => {
                    setProcessing(true);
                    router.delete(BranchController.destroy.url(branch.slug), {
                        preserveScroll: true,
                        onFinish: () => setProcessing(false),
                    });
                }}
            />
        </>
    );
}

PlatformBranchesShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
        { title: 'Show', href: '#' },
    ],
};
