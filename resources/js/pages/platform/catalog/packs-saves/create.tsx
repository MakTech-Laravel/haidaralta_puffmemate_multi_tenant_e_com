import { Head, router, setLayoutProps, usePage } from '@inertiajs/react';
import PacksSavesController from '@/actions/App/Http/Controllers/Platform/PacksSavesController';
import {
    BranchPackForm,
    type PackProductOption,
} from '@/components/branch/branch-pack-form';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Label } from '@/components/ui/label';

type Branch = { id: number; name: string; slug: string };

export default function PlatformPacksSavesCreate({
    selectedBranch,
    availableProducts,
}: {
    selectedBranch: Branch | null;
    availableProducts: PackProductOption[];
}) {
    const { errors } = usePage<{ errors: Record<string, string> }>().props;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: 'Packs & Saves',
                href: PacksSavesController.index.url(),
            },
            {
                title: 'Create pack',
                href: PacksSavesController.create.url(),
            },
        ],
    });

    const selectBranch = (value: string | null): void => {
        router.get(
            PacksSavesController.create.url(
                value === null ? undefined : { query: { branch: value } },
            ),
            {},
            {
                only: ['availableProducts', 'selectedBranch'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <>
            <Head title="Create pack" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Create pack"
                    description="Choose a branch, pick its stock products, then set amount off, percent, or a pack price"
                />

                <div className="grid max-w-sm gap-2">
                    <Label htmlFor="pack-branch">Branch</Label>
                    <InfiniteScrollCombobox
                        id="pack-branch"
                        optionsUrl={PacksSavesController.branchOptions.url()}
                        value={
                            selectedBranch ? String(selectedBranch.id) : null
                        }
                        onValueChange={selectBranch}
                        selectedLabel={selectedBranch?.name ?? null}
                        placeholder="Select a branch"
                        searchPlaceholder="Search branches…"
                        emptyText="No branch found."
                        descriptionKey={null}
                    />
                    <InputError message={errors.branch_id} />
                </div>

                <BranchPackForm
                    key={selectedBranch?.id ?? 'none'}
                    action={PacksSavesController.store.form()}
                    availableProducts={availableProducts}
                    cancelHref={PacksSavesController.index.url()}
                    extraData={{ branch_id: selectedBranch?.id ?? null }}
                    canHighlight
                    emptyProductsMessage={
                        selectedBranch
                            ? undefined
                            : {
                                  title: 'Select a branch',
                                  description:
                                      'Select a branch to pick its stock products.',
                              }
                    }
                />
            </div>
        </>
    );
}
