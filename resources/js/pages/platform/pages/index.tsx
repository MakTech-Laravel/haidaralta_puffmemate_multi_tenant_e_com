import { Head, Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { AppDateTime } from '@/components/app-datetime';

type PageRow = {
    id: number;
    slug: string;
    title: string;
    is_published: boolean;
    updated_at: string | null;
};

export default function PlatformPagesIndex({ pages }: { pages: PageRow[] }) {
    return (
        <>
            <Head title="Pages" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Pages"
                    description="Edit storefront static pages. Layout stays the same — content and images are managed here."
                />

                <div className="border-border/80 bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Title</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Updated</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pages.map((page) => (
                                <TableRow key={page.id}>
                                    <TableCell className="font-medium">
                                        {page.title}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        /{page.slug === 'faqs' ? 'faqs' : page.slug}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                page.is_published
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {page.is_published
                                                ? 'Published'
                                                : 'Draft'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {page.updated_at ? (
                                            <AppDateTime
                                                value={page.updated_at}
                                            />
                                        ) : (
                                            '—'
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`/platform/pages/${page.slug}/edit`}
                                            >
                                                <Pencil className="size-3.5" />
                                                Edit
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

PlatformPagesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Pages', href: '/platform/pages' },
    ],
};
