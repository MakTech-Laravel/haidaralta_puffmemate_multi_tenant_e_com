import type { LucideIcon } from 'lucide-react';
import {
    CheckCircle2,
    MapPin,
    MapPinned,
    Package,
    PackageCheck,
    ShieldCheck,
    ShoppingBag,
    ShoppingCart,
    Store,
    Truck,
    UserRound,
    Wallet,
    Wrench,
} from 'lucide-react';

import { resolveIconKey } from '@/lib/icons';

/** Lucide kebab-case keys → components (storefront CMS sections). */
const iconMap: Record<string, LucideIcon> = {
    'map-pin': MapPin,
    store: Store,
    'shopping-bag': ShoppingBag,
    'shopping-cart': ShoppingCart,
    wallet: Wallet,
    package: Package,
    'user-round': UserRound,
    truck: Truck,
    'check-circle-2': CheckCircle2,
    'map-pinned': MapPinned,
    'package-check': PackageCheck,
    'shield-check': ShieldCheck,
    wrench: Wrench,
};

/** Whitelist for LucideIconPicker on CMS admin forms (must be kebab-case). */
export const CMS_ICON_KEYS = Object.keys(iconMap);

export function resolveLucideIcon(
    name: string | null | undefined,
    fallback: LucideIcon = Package,
): LucideIcon {
    if (!name) {
        return fallback;
    }

    const key = resolveIconKey(name, 'package');

    return iconMap[key] ?? fallback;
}
