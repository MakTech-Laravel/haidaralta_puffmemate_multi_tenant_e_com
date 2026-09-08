import {
    Building2,
    KeyRound,
    LayoutGrid,
    Radio,
    Shield,
    UserCog,
    Users,
} from 'lucide-react';
import type { NavNode } from '@/components/navigation/types';

export const platformNav: NavNode[] = [
    {
        title: 'Dashboard',
        href: '/platform/dashboard',
        icon: LayoutGrid,
        permissions: ['view-dashboard'],
    },
    {
        title: 'Branches',
        href: '/platform/branches',
        icon: Building2,
        permissions: ['view-branches'],
    },
    {
        title: 'Realtime Console',
        href: '/platform/realtime',
        icon: Radio,
        permissions: ['view-dashboard'],
    },
    {
        title: 'Roles',
        href: '/platform/roles',
        icon: Shield,
        permissions: ['manage-roles'],
    },
    {
        title: 'Permissions',
        href: '/platform/permissions',
        icon: KeyRound,
        permissions: ['manage-roles'],
    },
    {
        title: 'Staff',
        href: '/platform/staff',
        icon: UserCog,
        permissions: ['manage-users'],
    },
    {
        title: 'Branch staff',
        href: '/platform/branch-staff',
        icon: Users,
        permissions: ['manage-users'],
    },
];
