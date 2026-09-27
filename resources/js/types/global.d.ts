import type { SharedNotifications } from '@/components/notifications/types';
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
            application: {
                shortName: string;
                description: string | null;
                logoUrl: string | null;
                faviconUrl: string | null;
                dateFormat: string;
                timeFormat: string;
                themeColor: string;
                backgroundColor: string;
            };
            contacts: {
                phone: string | null;
                fax: string | null;
                email: string | null;
                whatsapp: string | null;
                address: string | null;
                map_embed_url: string | null;
                socials: Array<{
                    key: string;
                    label: string;
                    url: string;
                    icon: string | null;
                }>;
            };
            paymentGateways: Array<{
                id: string;
                label: string;
                title: string;
                description: string;
                badge: string;
            }>;
            auth: Auth;
            portal: Portal | null;
            branch: BranchContext | null;
            sidebarOpen: boolean;
            storefront: {
                navCategories: Array<{
                    label: string;
                    categorySlug: string;
                }>;
                selectedBranchId: string | null;
            };
            catalog: {
                branch_can_create_categories: boolean;
                branch_can_view_categories: boolean;
                show_parent_category: boolean;
                show_product_variants: boolean;
                show_bulk_import: boolean;
                show_branch_pricing: boolean;
                branch_can_manage_catalog_pricing: boolean;
                sku_prefix: string;
            };
            drivers: {
                single_active_branch: boolean;
            };
            shifts: {
                allow_multiple_per_day: boolean;
                show_platform_administrator_shifts: boolean;
            };
            driverSupport: {
                supportPhone: string;
                branchPhone: string;
            } | null;
            notifications: SharedNotifications;
            [key: string]: unknown;
        };
    }
}
