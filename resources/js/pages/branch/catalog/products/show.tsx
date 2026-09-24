import { Head, Link, setLayoutProps, usePage } from '@inertiajs/react';
import { History, Pencil, ScrollText, Settings2 } from 'lucide-react';
import CategoryController from '@/actions/App/Http/Controllers/Branch/CategoryController';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import PriceHistoryController from '@/actions/App/Http/Controllers/Branch/Reports/PriceHistoryController';
import StockTransactionController from '@/actions/App/Http/Controllers/Branch/Reports/StockTransactionController';
import StockController from '@/actions/App/Http/Controllers/Branch/StockController';
import { BranchProductSummary } from '@/components/branch/branch-product-summary';
import { PRODUCT_TAG_OPTIONS } from '@/components/platform/product-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type ColorPickerValue } from '@/lib/color-picker';
import { stockPageUrl } from '@/lib/stock-query';

type Branch = { id: number; name: string; slug: string };

type ProductShow = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    brand: string | null;
    pack_label: string | null;
    pieces_per_pack: number | null;
    pack_unit: string | null;
    pack_summary: string | null;
    description: string | null;
    tags: string[];
    specifications: { label: string; value: string }[];
    status: boolean;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    categories: { id: number; name: string; slug: string }[];
    category_name: string;
    gallery?: {
        id: number;
        url: string;
        path?: string;
        sort_order?: number;
    }[];
    created_at: string | null;
    updated_at: string | null;
    created_by_name: string | null;
};

function tagLabel(value: string): string {
    return (
        PRODUCT_TAG_OPTIONS.find((option) => option.value === value)?.label ??
        value
    );
}

export default function BranchProductShow({
    branch,
    product,
}: {
    branch: Branch;
    product: ProductShow;
}) {
    const { catalog } = usePage().props;
    const categoryHref = catalog.branch_can_view_categories
        ? (slug: string) =>
              CategoryController.show.url({
                  branch: branch.slug,
                  category: slug,
              })
        : undefined;

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Products',
                href: ProductController.index.url(branch.slug),
            },
            {
                title: product.name,
                href: ProductController.show.url({
                    branch: branch.slug,
                    product: product.slug,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={product.name} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0 space-y-3">
                        <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                            Catalog product
                        </p>
                        <div className="space-y-2">
                            <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                                {product.name}
                            </h1>
                            <div className="flex flex-wrap items-center gap-1.5">
                                <Badge
                                    variant={
                                        product.status ? 'default' : 'secondary'
                                    }
                                >
                                    {product.status
                                        ? 'Active in catalog'
                                        : 'Inactive'}
                                </Badge>
                                {product.brand ? (
                                    <Badge variant="outline">
                                        {product.brand}
                                    </Badge>
                                ) : null}
                                {product.sku ? (
                                    <Badge
                                        variant="outline"
                                        className="font-mono font-normal"
                                    >
                                        {product.sku}
                                    </Badge>
                                ) : null}
                                {product.tags.map((tag) => (
                                    <Badge key={tag} variant="secondary">
                                        {tagLabel(tag)}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={ProductController.index.url(branch.slug)}
                            >
                                All products
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={stockPageUrl(
                                    StockController.manage.url(branch.slug),
                                    {
                                        productId: product.id,
                                        branchId: branch.id,
                                    },
                                )}
                            >
                                <Settings2 className="size-4" />
                                Manage stock
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={stockPageUrl(
                                    StockTransactionController.index.url(
                                        branch.slug,
                                    ),
                                    {
                                        productId: product.id,
                                        branchId: branch.id,
                                    },
                                )}
                            >
                                <ScrollText className="size-4" />
                                Stock transactions
                            </Link>
                        </Button>
                        {catalog.show_branch_pricing ? (
                            <Button variant="outline" asChild>
                                <Link
                                    href={stockPageUrl(
                                        PriceHistoryController.index.url(
                                            branch.slug,
                                        ),
                                        {
                                            productId: product.id,
                                            branchId: branch.id,
                                        },
                                    )}
                                >
                                    <History className="size-4" />
                                    Price history
                                </Link>
                            </Button>
                        ) : null}
                        <Button asChild>
                            <Link
                                href={ProductController.edit.url({
                                    branch: branch.slug,
                                    product: product.slug,
                                })}
                            >
                                <Pencil className="size-4" />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </div>

                <BranchProductSummary
                    name={product.name}
                    sku={product.sku}
                    slug={product.slug}
                    brand={product.brand}
                    packLabel={product.pack_label}
                    piecesPerPack={product.pieces_per_pack}
                    packUnit={product.pack_unit}
                    packSummary={product.pack_summary}
                    description={product.description}
                    status={product.status}
                    imageUrl={product.image_url}
                    colorCode={product.color_code}
                    gallery={product.gallery ?? []}
                    categories={product.categories}
                    categoryName={product.category_name}
                    tags={product.tags}
                    specifications={product.specifications}
                    createdAt={product.created_at}
                    updatedAt={product.updated_at}
                    createdByName={product.created_by_name}
                    categoryHref={categoryHref}
                />
            </div>
        </>
    );
}
