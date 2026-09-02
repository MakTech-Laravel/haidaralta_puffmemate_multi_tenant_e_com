import type { LucideIcon } from 'lucide-react';
import type { PermissionKey } from '@/types/permissions';

export type NavNode = {
    title: string;
    href: string;
    icon?: LucideIcon;
    permissions?: PermissionKey[];
    items?: NavNode[];
};
