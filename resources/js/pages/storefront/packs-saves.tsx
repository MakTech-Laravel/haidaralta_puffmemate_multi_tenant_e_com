import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { FeaturedPack } from '@/components/storefront/packs-saves/featured-pack';
import { PacksGrid } from '@/components/storefront/packs-saves/packs-grid';
import { PacksSavesHero } from '@/components/storefront/packs-saves/packs-saves-hero';
import { PacksToolbar } from '@/components/storefront/packs-saves/packs-toolbar';
import { storefrontFeatures } from '@/lib/storefront-features';
import { packsSaves } from '@/routes';
import type {
    StorefrontFeaturedPack,
    StorefrontPack,
} from '@/types/storefront';

type PacksSavesPageProps = {
    packs: StorefrontPack[];
    featuredPack: StorefrontFeaturedPack | null;
    filters: {
        search: string;
        sort: string | null;
    };
};

type VisitOptions = {
    search?: string;
    sort?: string | null;
};

export default function StorefrontPacksSaves({
    packs,
    featuredPack,
    filters,
}: PacksSavesPageProps) {
    const [search, setSearch] = useState(filters.search);
    const [sort, setSort] = useState(filters.sort);
    const [updating, setUpdating] = useState(false);
    const debounceRef = useRef<number>(undefined);
    const visitId = useRef(0);

    useEffect(() => {
        window.clearTimeout(debounceRef.current);
        setSearch(filters.search);
        setSort(filters.sort);
    }, [filters.search, filters.sort]);

    const visit = (next: VisitOptions = {}) => {
        const nextSearch = next.search ?? search;
        const nextSort = next.sort !== undefined ? next.sort : sort;

        const id = visitId.current + 1;
        visitId.current = id;
        setUpdating(true);
        router.cancelAll();
        router.get(
            packsSaves.url(),
            {
                ...(nextSearch ? { search: nextSearch } : {}),
                ...(nextSort ? { sort: nextSort } : {}),
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                async: true,
                showProgress: false,
                only: ['packs', 'filters'],
                onFinish: () => {
                    if (visitId.current === id) {
                        setUpdating(false);
                    }
                },
            },
        );
    };

    const updateSearch = (next: string) => {
        setSearch(next);
        window.clearTimeout(debounceRef.current);
        debounceRef.current = window.setTimeout(() => {
            visit({ search: next });
        }, 300);
    };

    return (
        <>
            <Head title="Packs & Saves" />

            <div className="container mx-auto flex flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10">
                <PacksSavesHero maxSavings={featuredPack?.savings} />

                {featuredPack ? (
                    <FeaturedPack key={featuredPack.id} pack={featuredPack} />
                ) : null}

                <PacksToolbar
                    search={search}
                    sort={sort}
                    onSearchChange={updateSearch}
                    onSearchSubmit={() => {
                        window.clearTimeout(debounceRef.current);
                        visit({ search });
                    }}
                    onSortChange={(nextSort) => {
                        setSort(nextSort);
                        visit({ sort: nextSort });
                    }}
                />

                <PacksGrid packs={packs} updating={updating} />
            </div>

            <FeaturesBar features={storefrontFeatures} />
        </>
    );
}
