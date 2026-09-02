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
            [key: string]: unknown;
        };
    }
}
