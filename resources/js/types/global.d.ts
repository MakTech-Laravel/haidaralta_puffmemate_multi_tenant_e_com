import type { Auth } from '@/types/auth';
import type { BranchContext, Portal } from '@/types/tenant';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            portal: Portal | null;
            branch: BranchContext | null;
            sidebarOpen: boolean;
            catalog: {
                branch_can_create_categories: boolean;
                branch_can_view_categories: boolean;
                show_parent_category: boolean;
                show_product_variants: boolean;
                sku_prefix: string;
            };
            [key: string]: unknown;
        };
    }
}
