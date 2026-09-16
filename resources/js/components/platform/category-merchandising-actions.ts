import { router } from '@inertiajs/react';
import { ListOrdered, PanelTop, Power, PowerOff, Star, StarOff } from 'lucide-react';
import type { DataTableActionItem } from '@/components/data-table';

export type CategoryMerchandisingRow = {
    slug: string;
    status: boolean;
    featured: boolean;
    show_in_nav: boolean;
    nav_sort: number | null;
    deleted_at?: string | null;
};

export function categoryMerchandisingItems(
    category: CategoryMerchandisingRow,
    urls: { status: string; featured: string; nav: string },
    onSetPosition: () => void,
): DataTableActionItem[] {
    if (category.deleted_at) {
        return [];
    }

    const put = (url: string): void => {
        router.put(url, {}, { preserveScroll: true });
    };

    return [
        {
            label: category.featured ? 'Remove featured' : 'Make featured',
            icon: category.featured ? StarOff : Star,
            onSelect: () => put(urls.featured),
        },
        {
            label: category.show_in_nav ? 'Hide from nav' : 'Available on nav',
            icon: PanelTop,
            onSelect: () => put(urls.nav),
        },
        {
            label: category.status ? 'Deactivate' : 'Activate',
            icon: category.status ? PowerOff : Power,
            onSelect: () => put(urls.status),
        },
        ...(category.show_in_nav
            ? [
                  {
                      label: 'Set nav position',
                      icon: ListOrdered,
                      onSelect: onSetPosition,
                  },
              ]
            : []),
    ];
}
