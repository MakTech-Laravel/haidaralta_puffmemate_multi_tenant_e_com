import { usePage } from '@inertiajs/react';
import type { BranchContext } from '@/types/tenant';

export function useBranch(): BranchContext {
    const { portal, branch } = usePage().props;

    if (portal !== 'branch' || branch === null) {
        throw new Error(
            'useBranch() can only be used within the branch portal when branch context is available.',
        );
    }

    return branch;
}
