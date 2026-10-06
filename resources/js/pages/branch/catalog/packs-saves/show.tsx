import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import PacksSavesController from '@/actions/App/Http/Controllers/Branch/PacksSavesController';
import {
    BranchPackDetails,
    type PackDetails,
} from '@/components/branch/branch-pack-details';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';

type Branch = { id: number; name: string; slug: string };

export default function BranchPacksSavesShow({
    branch,
    pack,
}: {
    branch: Branch;
    pack: PackDetails;
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Packs & Saves',
                href: PacksSavesController.index.url(branch.slug),
            },
            {
                title: pack.name,
                href: PacksSavesController.show.url({
                    branch: branch.slug,
                    pack: pack.slug,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={pack.name} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={pack.name}
                        description={
                            pack.description ??
                            `Bundle of ${pack.items.length} products at ${branch.name}`
                        }
                    />
                    <Button asChild>
                        <Link
                            href={PacksSavesController.edit.url({
                                branch: branch.slug,
                                pack: pack.slug,
                            })}
                        >
                            <Pencil className="size-4" />
                            Edit
                        </Link>
                    </Button>
                </div>

                <BranchPackDetails pack={pack} />
            </div>
        </>
    );
}
