import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { Pencil, Star, StarOff } from 'lucide-react';
import { useState } from 'react';
import PacksSavesController from '@/actions/App/Http/Controllers/Platform/PacksSavesController';
import {
    BranchPackDetails,
    type PackDetails,
} from '@/components/branch/branch-pack-details';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';

type Branch = { id: number; name: string; slug: string };

export default function PlatformPacksSavesShow({
    branch,
    pack,
}: {
    branch: Branch;
    pack: PackDetails & { id: number };
}) {
    const [processing, setProcessing] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: 'Packs & Saves',
                href: PacksSavesController.index.url(),
            },
            {
                title: pack.name,
                href: PacksSavesController.show.url(pack.id),
            },
        ],
    });

    const toggleHighlight = (): void => {
        setProcessing(true);
        router.put(
            PacksSavesController.highlight.url(pack.id),
            { highlighted: !pack.is_highlighted },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

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
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            disabled={processing}
                            onClick={toggleHighlight}
                        >
                            {pack.is_highlighted ? (
                                <StarOff className="size-4" />
                            ) : (
                                <Star className="size-4" />
                            )}
                            {pack.is_highlighted
                                ? 'Remove highlight'
                                : 'Highlight'}
                        </Button>
                        <Button asChild>
                            <Link href={PacksSavesController.edit.url(pack.id)}>
                                <Pencil className="size-4" />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </div>

                <BranchPackDetails pack={pack} branchName={branch.name} />
            </div>
        </>
    );
}
