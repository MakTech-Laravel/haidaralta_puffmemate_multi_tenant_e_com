import { router } from '@inertiajs/react';
import { buy as buyPack } from '@/actions/App/Http/Controllers/Storefront/PackController';

export function startPackCheckout(
    branchSlug: string,
    packSlug: string,
    quantity: number,
    onFinish?: () => void,
): void {
    router.post(
        buyPack.url({ branch: branchSlug, pack: packSlug }),
        { quantity },
        {
            onFinish,
        },
    );
}
